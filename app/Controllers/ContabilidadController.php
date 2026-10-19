<?php
// ============================================================================
// ARCHIVO: app/Controllers/ContabilidadController.php
// DESCRIPCIÓN: Controlador de Plan de Cuentas, Libro Diario, Mayor y Balances
// ============================================================================

class ContabilidadController extends Controller
{
    private ContabilidadModel $contabilidadModel;
    private MotorContable $motorContable;

    public function __construct()
    {
        $this->contabilidadModel = new ContabilidadModel();
        $this->motorContable     = new MotorContable();
    }

    /**
     * Acción por defecto al entrar al ERP: Muestra el Libro Diario y Libro Mayor
     */
    public function index(): void
    {
        $this->libros();
    }

    /**
     * Vista del Plan de Cuentas de 4 Niveles
     */
    public function planCuentas(): void
    {
        $this->render('contabilidad/plan_cuentas', [
            'titulo'  => 'Plan de Cuentas Contable - Merkafur',
            'cuentas' => $this->contabilidadModel->listarPlanCuentas(false),
            'resumen' => $this->contabilidadModel->obtenerResumenEjecutivo()
        ]);
    }

    /**
     * Crea una nueva cuenta en el catálogo (Exclusivo Contador)
     */
    public function guardarCuenta(): void
    {
        $this->requireRole(['Contador']);

        try {
            if (empty($_POST['codigo_cuenta']) || empty($_POST['nombre_cuenta'])) {
                throw new Exception("El código y el nombre de la cuenta son obligatorios.");
            }

            $this->contabilidadModel->crearCuentaContable($_POST);
            $this->jsonResponse(true, "Cuenta contable registrada exitosamente en el catálogo.");
        } catch (Exception $e) {
            $this->jsonResponse(false, "Error al guardar cuenta: " . $e->getMessage(), [], 400);
        }
    }

    /**
     * Vista integrada: Pestaña 1 (Libro Diario + Asiento Manual) y Pestaña 2 (Libro Mayor)
     */
    public function libros(): void
    {
        $fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fin'] ?? date('Y-m-d');
        $idCuentaMayor = isset($_GET['id_cuenta']) ? (int)$_GET['id_cuenta'] : CTA_CAJA_BS;

        $this->render('contabilidad/libros_contables', [
            'titulo'            => 'Libro Diario y Libro Mayor Analítico',
            'fechaInicio'       => $fechaInicio,
            'fechaFin'          => $fechaFin,
            'idCuentaMayor'     => $idCuentaMayor,
            'asientosDiario'    => $this->contabilidadModel->obtenerLibroDiario($fechaInicio, $fechaFin),
            'datosMayor'        => $this->contabilidadModel->obtenerLibroMayorPorCuenta($idCuentaMayor, $fechaInicio, $fechaFin),
            'cuentasImputables' => $this->contabilidadModel->listarPlanCuentas(true),
            'resumen'           => $this->contabilidadModel->obtenerResumenEjecutivo()
        ]);
    }

    /**
     * Registra un Asiento Manual o de Ajuste en el Libro Diario (Exclusivo Contador)
     * Recibe payload JSON con la glosa y los renglones de Debe y Haber
     */
    public function guardarAsientoManual(): void
    {
        $this->requireRole(['Contador']);

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!$payload) {
                throw new Exception("Formato de datos inválido.");
            }

            $fecha = $payload['fecha_asiento'] ?? date('Y-m-d');
            $glosa = trim($payload['glosa_concepto'] ?? '');
            $doc   = trim($payload['documento_referencia'] ?? 'AJUSTE-MAN');
            $lineas = $payload['lineas'] ?? [];

            if (empty($glosa)) {
                throw new Exception("Debe especificar la glosa o concepto del asiento contable.");
            }

            $idAsiento = $this->motorContable->registrarAsiento(
                $fecha,
                'Manual',
                $glosa,
                $lineas,
                $doc
            );

            $db->commit();
            $this->jsonResponse(true, "Asiento contable manual registrado y mayorizado con éxito.", [
                'id_asiento' => $idAsiento
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(false, $e->getMessage(), [], 400);
        }
    }

    /**
     * Vista de Estados Financieros (Balance de Comprobación, Estado de Resultados y Balance General)
     */
    public function estadosFinancieros(): void
    {
        $fechaCorte = $_GET['corte'] ?? date('Y-m-d');
        $eeff = $this->contabilidadModel->obtenerEstadosFinancieros($fechaCorte);

        $this->render('contabilidad/estados_financieros', [
            'titulo'              => 'Estados Financieros y Balance de Comprobación',
            'fechaCorte'          => $fechaCorte,
            'balanceComprobacion' => $this->contabilidadModel->obtenerBalanceComprobacion($fechaCorte),
            'estadoResultados'    => $eeff['estado_resultados'],
            'balanceGeneral'      => $eeff['balance_general'],
            'resumen'             => $this->contabilidadModel->obtenerResumenEjecutivo()
        ]);
    }
}