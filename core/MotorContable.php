<?php
// ============================================================================
// ARCHIVO: core/MotorContable.php
// DESCRIPCIÓN: Motor central de Partida Doble y Asientos Contables Automáticos
// ============================================================================

class MotorContable
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene el periodo contable actualmente ABIERTO y su tasa BCV vigente
     */
    public function obtenerPeriodoAbierto(): array
    {
        $stmt = $this->db->query(
            "SELECT id_periodo, anio_fiscal, mes_fiscal, tasa_bcv_vigente, estado 
             FROM periodos_contables 
             WHERE estado = 'Abierto' 
             ORDER BY anio_fiscal DESC, mes_fiscal DESC 
             LIMIT 1"
        );
        $periodo = $stmt->fetch();

        if (!$periodo) {
            throw new Exception("No existe ningún periodo contable 'Abierto'. El Contador debe abrir el periodo antes de registrar operaciones.");
        }
        return $periodo;
    }

    /**
     * Registra cualquier asiento contable validando estrictamente la Partida Doble (Debe = Haber).
     * Retorna el id_asiento generado para enlazarlo con las tablas operativas.
     *
     * @param string $fechaAsiento        Fecha YYYY-MM-DD
     * @param string $origenModulo        'Manual'|'Compras'|'POS_Ventas'|'Inventario_Merma'|'Tesoreria'
     * @param string $glosaConcepto       Descripción general del asiento
     * @param array  $lineas              Arreglo de apuntes: [['id_cuenta'=>int, 'descripcion'=>str, 'debe_bs'=>float, 'haber_bs'=>float], ...]
     * @param string|null $docReferencia  Ej: 'FAC-0012', 'Z-0045', 'MER-003'
     */
    public function registrarAsiento(
        string $fechaAsiento,
        string $origenModulo,
        string $glosaConcepto,
        array $lineas,
        ?string $docReferencia = null
    ): int {
        if (count($lineas) < 2) {
            throw new Exception("Todo asiento contable requiere al menos 2 líneas (un cargo y un abono).");
        }

        $periodo = $this->obtenerPeriodoAbierto();
        $tasaCambio = (float) $periodo['tasa_bcv_vigente'];

        $totalDebe  = 0.00;
        $totalHaber = 0.00;
        $lineasLimpias = [];

        // Preparar consulta para verificar que cada cuenta sea de Nivel 4 (acepta_movimientos = 1)
        $stmtCuenta = $this->db->prepare(
            "SELECT codigo_cuenta, nombre_cuenta, acepta_movimientos 
             FROM plan_cuentas WHERE id_cuenta = :id_cuenta"
        );

        foreach ($lineas as $idx => $linea) {
            $idCuenta = (int) ($linea['id_cuenta'] ?? 0);
            $debe     = round((float) ($linea['debe_bs'] ?? 0), 2);
            $haber    = round((float) ($linea['haber_bs'] ?? 0), 2);
            $desc     = trim($linea['descripcion'] ?? $glosaConcepto);

            // Ignorar líneas en cero absoluto
            if ($debe == 0.00 && $haber == 0.00) {
                continue;
            }

            if ($debe < 0 || $haber < 0) {
                throw new Exception("Línea #" . ($idx + 1) . ": Los montos contables no pueden ser negativos.");
            }

            if ($debe > 0 && $haber > 0) {
                throw new Exception("Línea #" . ($idx + 1) . ": Un mismo apunte no puede tener Debe y Haber simultáneamente.");
            }

            // Validar que la cuenta exista y sea imputable
            $stmtCuenta->execute([':id_cuenta' => $idCuenta]);
            $ctaInfo = $stmtCuenta->fetch();

            if (!$ctaInfo) {
                throw new Exception("La cuenta contable con ID {$idCuenta} no existe en el Plan de Cuentas.");
            }
            if ((int)$ctaInfo['acepta_movimientos'] !== 1) {
                throw new Exception("La cuenta {$ctaInfo['codigo_cuenta']} ({$ctaInfo['nombre_cuenta']}) es de grupo/totalizadora y no acepta asientos directos.");
            }

            $totalDebe  = round($totalDebe + $debe, 2);
            $totalHaber = round($totalHaber + $haber, 2);

            $lineasLimpias[] = [
                'id_cuenta'   => $idCuenta,
                'descripcion' => $desc,
                'debe_bs'     => $debe,
                'haber_bs'    => $haber
            ];
        }

        // Validación Innegociable de Partida Doble
        if (abs($totalDebe - $totalHaber) > 0.001 || $totalDebe <= 0) {
            throw new Exception(
                "Descuadre de Partida Doble: Total Debe (" . number_format($totalDebe, 2) . " Bs) " .
                "es distinto de Total Haber (" . number_format($totalHaber, 2) . " Bs)."
            );
        }

        // Generar correlativo único del asiento (Ej: ASI-202610-00012)
        $numeroAsiento = $this->generarNumeroCorrelativo($periodo['anio_fiscal'], $periodo['mes_fiscal']);
        $idUsuario     = $_SESSION['usuario_id'] ?? 1;

        // 1. Insertar Cabecera del Asiento en Libro Diario
        $stmtCabecera = $this->db->prepare(
            "INSERT INTO asientos_contables 
                (id_periodo, id_usuario, numero_asiento, fecha_asiento, origen_modulo, documento_referencia, glosa_concepto, tasa_cambio, total_debe_bs, total_haber_bs, estado)
             VALUES 
                (:id_periodo, :id_usuario, :numero_asiento, :fecha_asiento, :origen_modulo, :doc_ref, :glosa, :tasa, :debe, :haber, 'Mayorizado')"
        );

        $stmtCabecera->execute([
            ':id_periodo'     => $periodo['id_periodo'],
            ':id_usuario'     => $idUsuario,
            ':numero_asiento' => $numeroAsiento,
            ':fecha_asiento'  => $fechaAsiento,
            ':origen_modulo'  => $origenModulo,
            ':doc_ref'        => $docReferencia,
            ':glosa'          => $glosaConcepto,
            ':tasa'           => $tasaCambio,
            ':debe'           => $totalDebe,
            ':haber'          => $totalHaber
        ]);

        $idAsiento = (int) $this->db->lastInsertId();

        // 2. Insertar Renglones en detalles_asiento
        $stmtDetalle = $this->db->prepare(
            "INSERT INTO detalles_asiento (id_asiento, id_cuenta, descripcion_linea, debe_bs, haber_bs)
             VALUES (:id_asiento, :id_cuenta, :descripcion, :debe_bs, :haber_bs)"
        );

        foreach ($lineasLimpias as $apunte) {
            $stmtDetalle->execute([
                ':id_asiento'  => $idAsiento,
                ':id_cuenta'   => $apunte['id_cuenta'],
                ':descripcion' => $apunte['descripcion'],
                ':debe_bs'     => $apunte['debe_bs'],
                ':haber_bs'    => $apunte['haber_bs']
            ]);
        }

        return $idAsiento;
    }

    /**
     * Genera el número correlativo secuencial del asiento por periodo
     */
    private function generarNumeroCorrelativo(int $anio, int $mes): string
    {
        $prefijo = sprintf("ASI-%04d%02d-", $anio, $mes);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM asientos_contables WHERE numero_asiento LIKE :prefijo"
        );
        $stmt->execute([':prefijo' => $prefijo . '%']);
        $consecutivo = ((int) $stmt->fetchColumn()) + 1;

        return $prefijo . str_pad((string)$consecutivo, 5, '0', STR_PAD_LEFT);
    }
}