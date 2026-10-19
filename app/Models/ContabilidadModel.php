<?php
// ============================================================================
// ARCHIVO: app/Models/ContabilidadModel.php
// DESCRIPCIÓN: Consultas SQL de Plan de Cuentas, Libro Diario, Mayor y Balances
// ============================================================================

class ContabilidadModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene el catálogo completo del Plan de Cuentas ordenado jerárquicamente
     */
    public function listarPlanCuentas(bool $soloImputables = false): array
    {
        $where = $soloImputables ? "WHERE acepta_movimientos = 1" : "";
        $sql = "SELECT id_cuenta, id_cuenta_padre, codigo_cuenta, nombre_cuenta, 
                       tipo_cuenta, naturaleza, nivel, acepta_movimientos
                FROM plan_cuentas
                {$where}
                ORDER BY codigo_cuenta ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Crea una nueva cuenta en el Plan de Cuentas (Exclusivo Contador)
     */
    public function crearCuentaContable(array $datos): bool
    {
        $sql = "INSERT INTO plan_cuentas 
                    (id_cuenta_padre, codigo_cuenta, nombre_cuenta, tipo_cuenta, naturaleza, nivel, acepta_movimientos)
                VALUES 
                    (:padre, :codigo, :nombre, :tipo, :naturaleza, :nivel, :imputable)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':padre'      => !empty($datos['id_cuenta_padre']) ? (int)$datos['id_cuenta_padre'] : null,
            ':codigo'     => trim($datos['codigo_cuenta']),
            ':nombre'     => trim($datos['nombre_cuenta']),
            ':tipo'       => $datos['tipo_cuenta'],
            ':naturaleza' => $datos['naturaleza'],
            ':nivel'      => (int)$datos['nivel'],
            ':imputable'  => (int)$datos['acepta_movimientos']
        ]);
    }

    /**
     * Obtiene los asientos del Libro Diario junto con sus líneas de detalle (Debe/Haber)
     */
    public function obtenerLibroDiario(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $params = [];
        $where  = "WHERE 1=1 ";

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $where .= "AND a.fecha_asiento BETWEEN :inicio AND :fin ";
            $params[':inicio'] = $fechaInicio;
            $params[':fin']    = $fechaFin;
        }

        $sqlCabeceras = "SELECT a.id_asiento, a.numero_asiento, a.fecha_asiento, a.origen_modulo,
                                a.documento_referencia, a.glosa_concepto, a.tasa_cambio,
                                a.total_debe_bs, a.total_haber_bs, a.estado, u.username AS creado_por
                         FROM asientos_contables a
                         INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
                         {$where}
                         ORDER BY a.fecha_asiento DESC, a.id_asiento DESC";

        $stmt = $this->db->prepare($sqlCabeceras);
        $stmt->execute($params);
        $asientos = $stmt->fetchAll();

        if (empty($asientos)) {
            return [];
        }

        // Preparar consulta para anexar los renglones de cada asiento
        $sqlDetalles = "SELECT d.id_detalle, d.id_cuenta, c.codigo_cuenta, c.nombre_cuenta,
                               d.descripcion_linea, d.debe_bs, d.haber_bs
                        FROM detalles_asiento d
                        INNER JOIN plan_cuentas c ON d.id_cuenta = c.id_cuenta
                        WHERE d.id_asiento = :id_asiento
                        ORDER BY d.id_detalle ASC";
        $stmtDet = $this->db->prepare($sqlDetalles);

        foreach ($asientos as &$asiento) {
            $stmtDet->execute([':id_asiento' => $asiento['id_asiento']]);
            $asiento['renglones'] = $stmtDet->fetchAll();
        }

        return $asientos;
    }

    /**
     * Consulta el Libro Mayor Analítico de una cuenta contable en un rango de fechas,
     * calculando el Saldo Inicial previo y el Saldo Acumulado renglón por renglón.
     */
    public function obtenerLibroMayorPorCuenta(int $idCuenta, string $fechaInicio, string $fechaFin): array
    {
        // 1. Obtener metadatos de la cuenta
        $stmtCta = $this->db->prepare("SELECT * FROM plan_cuentas WHERE id_cuenta = :id");
        $stmtCta->execute([':id' => $idCuenta]);
        $cuenta = $stmtCta->fetch();

        if (!$cuenta) {
            throw new Exception("La cuenta solicitada no existe.");
        }

        // 2. Calcular el Saldo Inicial acumulado antes de $fechaInicio
        $sqlSaldoPrevio = "SELECT COALESCE(SUM(efecto_saldo_bs), 0) AS saldo_inicial
                           FROM vw_libro_mayor
                           WHERE id_cuenta = :id_cuenta AND fecha_asiento < :inicio";
        $stmtPrev = $this->db->prepare($sqlSaldoPrevio);
        $stmtPrev->execute([':id_cuenta' => $idCuenta, ':inicio' => $fechaInicio]);
        $saldoInicial = (float) $stmtPrev->fetchColumn();

        // 3. Obtener los movimientos del periodo usando la vista vw_libro_mayor
        $sqlMovs = "SELECT numero_asiento, fecha_asiento, origen_modulo, documento_referencia,
                           descripcion_linea, debe_bs, haber_bs, debe_usd, haber_usd, efecto_saldo_bs
                    FROM vw_libro_mayor
                    WHERE id_cuenta = :id_cuenta 
                      AND fecha_asiento BETWEEN :inicio AND :fin
                    ORDER BY fecha_asiento ASC, id_asiento ASC";
        $stmtMovs = $this->db->prepare($sqlMovs);
        $stmtMovs->execute([
            ':id_cuenta' => $idCuenta,
            ':inicio'    => $fechaInicio,
            ':fin'       => $fechaFin
        ]);
        $movimientos = $stmtMovs->fetchAll();

        // 4. Calcular saldo progresivo línea por línea
        $saldoCorriente = $saldoInicial;
        $totalDebe  = 0.00;
        $totalHaber = 0.00;

        foreach ($movimientos as &$mov) {
            $saldoCorriente = round($saldoCorriente + (float)$mov['efecto_saldo_bs'], 2);
            $mov['saldo_acumulado_bs'] = $saldoCorriente;
            $totalDebe  = round($totalDebe + (float)$mov['debe_bs'], 2);
            $totalHaber = round($totalHaber + (float)$mov['haber_bs'], 2);
        }

        return [
            'cuenta'        => $cuenta,
            'saldo_inicial' => $saldoInicial,
            'movimientos'   => $movimientos,
            'total_debe'    => $totalDebe,
            'total_haber'   => $totalHaber,
            'saldo_final'   => $saldoCorriente
        ];
    }

    /**
     * Genera el Balance de Comprobación de Sumas y Saldos
     */
    public function obtenerBalanceComprobacion(?string $fechaFin = null): array
    {
        $fechaCorte = $fechaFin ?: date('Y-m-d');

        $sql = "SELECT 
                    c.id_cuenta,
                    c.codigo_cuenta,
                    c.nombre_cuenta,
                    c.tipo_cuenta,
                    c.naturaleza,
                    COALESCE(SUM(CASE WHEN a.estado = 'Mayorizado' AND a.fecha_asiento <= :corte1 THEN d.debe_bs ELSE 0 END), 0) AS suma_debe,
                    COALESCE(SUM(CASE WHEN a.estado = 'Mayorizado' AND a.fecha_asiento <= :corte2 THEN d.haber_bs ELSE 0 END), 0) AS suma_haber
                FROM plan_cuentas c
                LEFT JOIN detalles_asiento d ON c.id_cuenta = d.id_cuenta
                LEFT JOIN asientos_contables a ON d.id_asiento = a.id_asiento
                WHERE c.acepta_movimientos = 1
                GROUP BY c.id_cuenta, c.codigo_cuenta, c.nombre_cuenta, c.tipo_cuenta, c.naturaleza
                HAVING suma_debe > 0 OR suma_haber > 0
                ORDER BY c.codigo_cuenta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':corte1' => $fechaCorte, ':corte2' => $fechaCorte]);
        $filas = $stmt->fetchAll();

        foreach ($filas as &$f) {
            $debe  = (float) $f['suma_debe'];
            $haber = (float) $f['suma_haber'];

            if ($f['naturaleza'] === 'Deudora') {
                $saldo = round($debe - $haber, 2);
                $f['saldo_deudor']   = $saldo >= 0 ? $saldo : 0.00;
                $f['saldo_acreedor'] = $saldo < 0 ? abs($saldo) : 0.00;
            } else {
                $saldo = round($haber - $debe, 2);
                $f['saldo_acreedor'] = $saldo >= 0 ? $saldo : 0.00;
                $f['saldo_deudor']   = $saldo < 0 ? abs($saldo) : 0.00;
            }
            $f['saldo_neto'] = $saldo;
        }

        return $filas;
    }

    /**
     * Construye el Estado de Resultados (Ingresos, Costos y Gastos) y el
     * Balance General (Activos, Pasivos y Patrimonio + Resultado del Ejercicio)
     */
    public function obtenerEstadosFinancieros(?string $fechaFin = null): array
    {
        $cuentasConSaldo = $this->obtenerBalanceComprobacion($fechaFin);

        $ingresos   = [];
        $costos     = [];
        $gastos     = [];
        $activos    = [];
        $pasivos    = [];
        $patrimonio = [];

        $totalIngresos   = 0.00;
        $totalCostos     = 0.00;
        $totalGastos     = 0.00;
        $totalActivos    = 0.00;
        $totalPasivos    = 0.00;
        $totalPatrimonio = 0.00;

        foreach ($cuentasConSaldo as $cta) {
            // Valor efectivo de la cuenta según su tipo para los estados financieros
            $debe  = (float)$cta['suma_debe'];
            $haber = (float)$cta['suma_haber'];

            switch ($cta['tipo_cuenta']) {
                case 'Ingreso':
                    $monto = round($haber - $debe, 2); // Cuentas de devolución (Deudoras) restan automáticamente
                    $cta['monto_presentacion'] = $monto;
                    $ingresos[] = $cta;
                    $totalIngresos = round($totalIngresos + $monto, 2);
                    break;

                case 'Costo':
                    $monto = round($debe - $haber, 2);
                    $cta['monto_presentacion'] = $monto;
                    $costos[] = $cta;
                    $totalCostos = round($totalCostos + $monto, 2);
                    break;

                case 'Gasto':
                    $monto = round($debe - $haber, 2);
                    $cta['monto_presentacion'] = $monto;
                    $gastos[] = $cta;
                    $totalGastos = round($totalGastos + $monto, 2);
                    break;

                case 'Activo':
                    // La depreciación acumulada (Acreedora) resta al activo automáticamente
                    $monto = round($debe - $haber, 2);
                    $cta['monto_presentacion'] = $monto;
                    $activos[] = $cta;
                    $totalActivos = round($totalActivos + $monto, 2);
                    break;

                case 'Pasivo':
                    $monto = round($haber - $debe, 2);
                    $cta['monto_presentacion'] = $monto;
                    $pasivos[] = $cta;
                    $totalPasivos = round($totalPasivos + $monto, 2);
                    break;

                case 'Patrimonio':
                    $monto = round($haber - $debe, 2);
                    $cta['monto_presentacion'] = $monto;
                    $patrimonio[] = $cta;
                    $totalPatrimonio = round($totalPatrimonio + $monto, 2);
                    break;
            }
        }

        $utilidadBruta         = round($totalIngresos - $totalCostos, 2);
        $utilidadNetaEjercicio = round($utilidadBruta - $totalGastos, 2);
        $totalPasivoMasPatrimonio = round($totalPasivos + $totalPatrimonio + $utilidadNetaEjercicio, 2);

        return [
            'estado_resultados' => [
                'ingresos'       => $ingresos,
                'costos'         => $costos,
                'gastos'         => $gastos,
                'total_ingresos' => $totalIngresos,
                'total_costos'   => $totalCostos,
                'utilidad_bruta' => $utilidadBruta,
                'total_gastos'   => $totalGastos,
                'utilidad_neta'  => $utilidadNetaEjercicio
            ],
            'balance_general' => [
                'activos'                  => $activos,
                'pasivos'                  => $pasivos,
                'patrimonio'               => $patrimonio,
                'total_activos'            => $totalActivos,
                'total_pasivos'            => $totalPasivos,
                'total_patrimonio'         => $totalPatrimonio,
                'resultado_ejercicio'      => $utilidadNetaEjercicio,
                'total_pasivo_patrimonio'  => $totalPasivoMasPatrimonio,
                'ecuacion_cuadrada'        => abs($totalActivos - $totalPasivoMasPatrimonio) < 0.01
            ]
        ];
    }

    /**
     * Obtiene los KPIs resumidos para la barra superior / resumen ejecutivo
     */
    public function obtenerResumenEjecutivo(): array
    {
        $motor = new MotorContable();
        $periodo = $motor->obtenerPeriodoAbierto();
        $eeff = $this->obtenerEstadosFinancieros();

        // Saldo pendiente total en Cuentas por Pagar
        $cxpPendiente = (float) $this->db->query(
            "SELECT COALESCE(SUM(saldo_pendiente_bs), 0) FROM facturas_compra WHERE estado_pago = 'Pendiente'"
        )->fetchColumn();

        // Valor total del inventario actual valorizado a costo promedio
        $valorInventario = (float) $this->db->query(
            "SELECT COALESCE(SUM(stock_actual * costo_promedio_bs), 0) FROM productos"
        )->fetchColumn();

        return [
            'periodo'          => $periodo,
            'ventas_netas_bs'  => $eeff['estado_resultados']['total_ingresos'],
            'utilidad_neta_bs' => $eeff['estado_resultados']['utilidad_neta'],
            'cxp_pendiente_bs' => round($cxpPendiente, 2),
            'inventario_bs'    => round($valorInventario, 2)
        ];
    }
}