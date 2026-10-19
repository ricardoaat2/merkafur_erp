<?php
class ComprasController extends Controller
{
    private ComprasModel $comprasModel;
    private MotorContable $motor;

    public function __construct()
    {
        $this->comprasModel = new ComprasModel();
        $this->motor        = new MotorContable();
    }

    public function index(): void
    {
        $this->render('compras/compras_tesoreria', [
            'titulo'      => 'Gestión de Compras, Proveedores y Tesorería',
            'proveedores' => $this->comprasModel->listarProveedores(),
            'facturas'    => $this->comprasModel->listarFacturas()
        ]);
    }

    /**
     * Registra una Factura de Compra mixta y ejecuta el MotorContable para generar
     * el asiento de inventario, crédito fiscal, retención de IVA y cuenta por pagar.
     */
    public function registrarFactura(): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $idProv   = (int)($_POST['id_proveedor'] ?? 0);
            $numFac   = trim($_POST['numero_factura'] ?? '');
            $numCtrl  = trim($_POST['numero_control'] ?? '');
            $fecha    = $_POST['fecha_recepcion'] ?? date('Y-m-d');
            $exento   = round((float)($_POST['monto_exento_bs'] ?? 0), 2);
            $baseImp  = round((float)($_POST['base_imponible_bs'] ?? 0), 2);
            $montoIva = round($baseImp * (IVA_GENERAL / 100), 2);
            $totalFac = round($exento + $baseImp + $montoIva, 2);

            // Obtener datos del proveedor y retención
            $stmtProv = $db->prepare("SELECT * FROM proveedores WHERE id_proveedor = :id");
            $stmtProv->execute([':id' => $idProv]);
            $prov = $stmtProv->fetch();
            if (!$prov) throw new Exception("Proveedor no encontrado.");

            $porcRet = (float)$prov['porcentaje_retencion_iva'];
            $ivaRetenido = round($montoIva * ($porcRet / 100), 2);
            $netoPagar   = round($totalFac - $ivaRetenido, 2);
            $numCompRet  = 'RET-' . date('Ymd') . '-' . rand(1000, 9999);

            // Determinar cuenta de inventario (por defecto Víveres Gravados/Exentos o mixta)
            // Para simplificar la factura global, distribuimos el inventario entre exento y gravado
            $lineasAsiento = [];

            if ($exento > 0) {
                $lineasAsiento[] = [
                    'id_cuenta'   => 42, // Inventario Víveres Exentos
                    'descripcion' => "Compra mercancia exenta - Fac: {$numFac}",
                    'debe_bs'     => $exento,
                    'haber_bs'    => 0.00
                ];
            }
            if ($baseImp > 0) {
                $lineasAsiento[] = [
                    'id_cuenta'   => 43, // Inventario Víveres Gravados
                    'descripcion' => "Compra mercancia gravada - Fac: {$numFac}",
                    'debe_bs'     => $baseImp,
                    'haber_bs'    => 0.00
                ];
            }
            if ($montoIva > 0) {
                $lineasAsiento[] = [
                    'id_cuenta'   => CTA_CREDITO_FISCAL_IVA,
                    'descripcion' => "Crédito Fiscal IVA 16% - Fac: {$numFac}",
                    'debe_bs'     => $montoIva,
                    'haber_bs'    => 0.00
                ];
            }

            // Haberes
            $lineasAsiento[] = [
                'id_cuenta'   => CTA_CXP_MERCANCIA,
                'descripcion' => "CxP Proveedor: {$prov['razon_social']} - Fac: {$numFac}",
                'debe_bs'     => 0.00,
                'haber_bs'    => $netoPagar
            ];

            if ($ivaRetenido > 0) {
                $lineasAsiento[] = [
                    'id_cuenta'   => CTA_RET_IVA_POR_ENTERAR,
                    'descripcion' => "Retención IVA ({$porcRet}%) Compra Fac: {$numFac}",
                    'debe_bs'     => 0.00,
                    'haber_bs'    => $ivaRetenido
                ];
            }

            // Generar Asiento Contable Automático
            $idAsiento = $this->motor->registrarAsiento(
                $fecha,
                'Compras',
                "Causación Factura de Compra #{$numFac} - {$prov['razon_social']}",
                $lineasAsiento,
                $numFac
            );

            // Registrar Factura en la tabla facturas_compra
            $sqlFac = "INSERT INTO facturas_compra 
                        (id_proveedor, id_asiento, numero_factura, numero_control, fecha_recepcion, 
                         monto_exento_bs, base_imponible_bs, monto_iva_bs, total_factura_bs, 
                         numero_comprobante_ret, iva_retenido_bs, saldo_pendiente_bs, estado_pago)
                       VALUES 
                        (:prov, :asiento, :fac, :ctrl, :fecha, :ex, :base, :iva, :tot, :numret, :ivaret, :saldo, 'Pendiente')";
            $stmtFac = $db->prepare($sqlFac);
            $stmtFac->execute([
                ':prov'   => $idProv,
                ':asiento'=> $idAsiento,
                ':fac'    => $numFac,
                ':ctrl'   => $numCtrl,
                ':fecha'  => $fecha,
                ':ex'     => $exento,
                ':base'   => $baseImp,
                ':iva'    => $montoIva,
                ':tot'    => $totalFac,
                ':numret' => $numCompRet,
                ':ivaret' => $ivaRetenido,
                ':saldo'  => $netoPagar
            ]);

            $db->commit();
            $this->jsonResponse(true, "Factura de compra registrada y contabilizada correctamente.");
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(false, "Error al registrar factura: " . $e->getMessage(), [], 400);
        }
    }
}