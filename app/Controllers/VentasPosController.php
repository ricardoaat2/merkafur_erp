<?php
class VentasPosController extends Controller
{
    private VentasPosModel $posModel;
    private MotorContable $motor;

    public function __construct()
    {
        $this->posModel = new VentasPosModel();
        $this->motor    = new MotorContable();
    }

    public function index(): void
    {
        $this->render('ventas_pos/cierres_pos', [
            'titulo'    => 'Cierres de Caja POS (Reportes Z) y Libro de Ventas',
            'cierres'   => $this->posModel->listarCierresZ()
        ]);
    }

    /**
     * Registra un Cierre de Caja Z consolidado y genera el asiento contable dual:
     * 1. Ingreso de medios de pago (Caja Bs, Caja USD, POS por liquidar, Pago Móvil) vs Ventas e Impuestos.
     * 2. Costo de Venta vs Inventario.
     */
    public function registrarCierreZ(): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $numCaja   = trim($_POST['numero_caja'] ?? 'CAJA-01');
            $serial    = trim($_POST['serial_maquina_fiscal'] ?? 'Z1B8049210');
            $numZ      = trim($_POST['numero_reporte_z'] ?? '');
            $fecha     = $_POST['fecha_operacion'] ?? date('Y-m-d');
            
            // 1. Obtener la tasa de cambio vigente del periodo contable abierto
            $periodo = $this->motor->obtenerPeriodoAbierto();
            $tasaBcv = (float) $periodo['tasa_bcv_vigente'];

            // Facturación de la Máquina Fiscal
            $vExentas   = round((float)($_POST['ventas_exentas_bs'] ?? 0), 2);
            $baseImp    = round((float)($_POST['base_imponible_bs'] ?? 0), 2);
            
            // IVA: Si viene del POST se usa, de lo contrario se calcula de la Base Imponible (16%)
            $ivaPost    = round((float)($_POST['iva_cobrado_bs'] ?? 0), 2);
            $ivaCobrado = $ivaPost > 0 ? $ivaPost : round($baseImp * (IVA_GENERAL / 100), 2);
            
            // CÁLCULO AUTOMÁTICO EN BACKEND DEL IVA 16%
            $ivaCobrado = round($baseImp * (IVA_GENERAL / 100), 2);

            // 3. Arqueo de Medios de Pago Contados
            $cobEfectivoBs  = round((float)($_POST['cobrado_efectivo_bs'] ?? 0), 2);
            
            // Si el usuario ingresó dólares ($ USD), el backend lo convierte con la tasa BCV oficial
            $dolaresFisicos = (float)($_POST['divisas_usd'] ?? 0);
            if ($dolaresFisicos > 0) {
                $cobDivisasBs = round($dolaresFisicos * $tasaBcv, 2);
            } else {
                $cobDivisasBs = round((float)($_POST['cobrado_divisas_bs'] ?? 0), 2);
            }

            $cobPosBs       = round((float)($_POST['cobrado_punto_venta_bs'] ?? 0), 2);
            $cobPagoMovilBs = round((float)($_POST['cobrado_pago_movil_bs'] ?? 0), 2);
            $costoVenta     = round((float)($_POST['costo_venta_total_bs'] ?? 0), 2);

            // CÁLCULO AUTOMÁTICO DEL IGTF 3% (Si se ingresó 0 o vacío, se calcula sobre las divisas)
            $igtfPost = (float)($_POST['igtf_cobrado_bs'] ?? 0);
            if ($igtfPost > 0) {
                $igtfCobrado = round($igtfPost, 2);
            } elseif ($cobDivisasBs > 0) {
                $igtfCobrado = round($cobDivisasBs * (IGTF_DIVISAS / 100), 2);
            } else {
                $igtfCobrado = 0.00;
            }

            // Totales y Diferencia de Arqueo
            $totalSistema = round($vExentas + $baseImp + $ivaCobrado + $igtfCobrado, 2);
            $totalContado = round($cobEfectivoBs + $cobDivisasBs + $cobPosBs + $cobPagoMovilBs, 2);
            $diferencia   = round($totalContado - $totalSistema, 2);

            // Construir apuntes del asiento contable
            $lineasAsiento = [];

            // Entradas de Dinero (DEBE)
            if ($cobEfectivoBs > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_CAJA_BS, 'descripcion' => "Efectivo Bs en Caja - Z: {$numZ}", 'debe_bs' => $cobEfectivoBs, 'haber_bs' => 0.00];
            }
            if ($cobDivisasBs > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_CAJA_USD, 'descripcion' => "Divisas USD en Caja ($ {$dolaresFisicos} a {$tasaBcv}) - Z: {$numZ}", 'debe_bs' => $cobDivisasBs, 'haber_bs' => 0.00];
            }
            if ($cobPosBs > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_POS_POR_LIQUIDAR, 'descripcion' => "Puntos de Venta por Liquidar - Z: {$numZ}", 'debe_bs' => $cobPosBs, 'haber_bs' => 0.00];
            }
            if ($cobPagoMovilBs > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_BANCO_BS, 'descripcion' => "Pago Móvil / Transferencia - Z: {$numZ}", 'debe_bs' => $cobPagoMovilBs, 'haber_bs' => 0.00];
            }

            // Manejo de Diferencia de Caja (Faltante o Sobrante)
            if ($diferencia < 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_FALTANTE_CAJA, 'descripcion' => "Faltante en Cierre Z: {$numZ}", 'debe_bs' => abs($diferencia), 'haber_bs' => 0.00];
            } elseif ($diferencia > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_SOBRANTE_CAJA, 'descripcion' => "Sobrante en Cierre Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $diferencia];
            }

            // Ingresos por Ventas e Impuestos (HABER)
            if ($vExentas > 0) {
                $lineasAsiento[] = ['id_cuenta' => 64, 'descripcion' => "Ventas Víveres Exentos - Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $vExentas];
            }
            if ($baseImp > 0) {
                $lineasAsiento[] = ['id_cuenta' => 65, 'descripcion' => "Ventas Víveres Gravados - Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $baseImp];
            }
            if ($ivaCobrado > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_DEBITO_FISCAL_IVA, 'descripcion' => "Débito Fiscal IVA 16% - Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $ivaCobrado];
            }
            if ($igtfCobrado > 0) {
                $lineasAsiento[] = ['id_cuenta' => CTA_IGTF_POR_ENTERAR, 'descripcion' => "IGTF Percibido Divisas 3% - Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $igtfCobrado];
            }

            // Bloque de Costo de Venta vs Inventario
            if ($costoVenta > 0) {
                $lineasAsiento[] = ['id_cuenta' => 74, 'descripcion' => "Costo de Venta del Turno - Z: {$numZ}", 'debe_bs' => $costoVenta, 'haber_bs' => 0.00];
                $lineasAsiento[] = ['id_cuenta' => 42, 'descripcion' => "Descarga Inventario por Ventas - Z: {$numZ}", 'debe_bs' => 0.00, 'haber_bs' => $costoVenta];
            }

            // Registrar asiento contable en el Libro Diario
            $idAsiento = $this->motor->registrarAsiento(
                $fecha,
                'POS_Ventas',
                "Cierre de Caja Z #{$numZ} ({$numCaja})",
                $lineasAsiento,
                "Z-{$numZ}"
            );

            // Guardar en cierres_caja_z
            $sqlZ = "INSERT INTO cierres_caja_z 
                        (id_asiento, numero_caja, serial_maquina_fiscal, numero_reporte_z, fecha_operacion, 
                         ventas_exentas_bs, base_imponible_bs, iva_cobrado_bs, igtf_cobrado_bs, 
                         total_ventas_sistema_bs, cobrado_efectivo_bs, cobrado_divisas_bs, 
                         cobrado_punto_venta_bs, cobrado_pago_movil_bs, diferencia_caja_bs, costo_venta_total_bs)
                     VALUES 
                        (:asiento, :caja, :serial, :numz, :fecha, :vex, :base, :iva, :igtf, :totsis, :efec, :div, :pos, :pagoM, :dif, :costo)";
            $stmtZ = $db->prepare($sqlZ);
            $stmtZ->execute([
                ':asiento' => $idAsiento,
                ':caja'    => $numCaja,
                ':serial'  => $serial,
                ':numz'    => $numZ,
                ':fecha'   => $fecha,
                ':vex'     => $vExentas,
                ':base'    => $baseImp,
                ':iva'     => $ivaCobrado,
                ':igtf'    => $igtfCobrado,
                ':totsis'  => $totalSistema,
                ':efec'    => $cobEfectivoBs,
                ':div'     => $cobDivisasBs,
                ':pos'     => $cobPosBs,
                ':pagoM'   => $cobPagoMovilBs,
                ':dif'     => $diferencia,
                ':costo'   => $costoVenta
            ]);

            $db->commit();
            $this->jsonResponse(true, "Cierre de Caja Z registrado exitosamente. IVA: {$ivaCobrado} Bs | Divisas: {$cobDivisasBs} Bs.");
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(false, "Error al procesar Cierre Z: " . $e->getMessage(), [], 400);
        }
    }
}