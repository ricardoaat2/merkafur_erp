<?php
// ============================================================================
// ARCHIVO: app/Controllers/AuthController.php
// DESCRIPCIÓN: Controlador de Autenticación, Usuarios, Periodos y Tasa BCV
// ============================================================================

class AuthController extends Controller
{
    private AuthModel $authModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
    }

    /**
     * Muestra el formulario de Login (sin el layout lateral del ERP)
     */
    public function login(): void
    {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect('contabilidad/index');
        }
        $this->render('auth/login', ['titulo' => 'Iniciar Sesión - Merkafur ERP'], false);
    }

    /**
     * Procesa las credenciales enviadas por POST
     */
    public function autenticar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuario = $this->authModel->obtenerUsuarioPorUsername($username);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = (int) $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_user']   = $usuario['username'];
            $_SESSION['usuario_rol']    = $usuario['nombre_rol']; // 'Administrador' o 'Contador'

            $this->authModel->autoSincronizarTasaDiaria();
            $this->redirect('contabilidad/index');
        } else {
            $this->render('auth/login', [
                'titulo' => 'Iniciar Sesión - Merkafur ERP',
                'error'  => 'Usuario o contraseña incorrectos, o cuenta inactiva.'
            ], false);
        }
    }

    /**
     * Cierra la sesión activa
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        $this->redirect('auth/login');
    }

    /**
     * Vista integrada de Configuración (Usuarios, Tasa BCV y Periodos Contables)
     */
    public function configuracion(): void
    {
        $this->render('auth/configuracion', [
            'titulo'   => 'Configuración del Sistema y Periodos Fiscales',
            'usuarios' => $this->authModel->listarUsuarios(),
            'roles'    => $this->authModel->listarRoles(),
            'periodos' => $this->authModel->listarPeriodos()
        ]);
    }

    /**
     * Guarda un nuevo usuario (Exclusivo Administrador)
     */
    public function guardarUsuario(): void
    {
        $this->requireRole(['Administrador']);

        try {
            $idRol    = (int) ($_POST['id_rol'] ?? 0);
            $nombre   = trim($_POST['nombre_completo'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($idRol <= 0 || empty($nombre) || empty($username) || strlen($password) < 6) {
                throw new Exception("Todos los campos son obligatorios y la clave debe tener mínimo 6 caracteres.");
            }

            $this->authModel->crearUsuario($idRol, $nombre, $username, $password);
            $this->jsonResponse(true, "Usuario '{$username}' creado correctamente.");
        } catch (Exception $e) {
            $this->jsonResponse(false, "Error al crear usuario: " . $e->getMessage(), [], 400);
        }
    }

    /**
     * Actualiza la tasa de cambio oficial BCV (Permitido a Administrador y Contador)
     */
    public function actualizarTasa(): void
    {
        try {
            $modo = $_POST['modo'] ?? 'api'; // Por defecto usa la API

            if ($modo === 'api') {
                $resultado = $this->authModel->sincronizarTasaDesdeApi();
                $this->jsonResponse(
                    true,
                    "Tasa BCV sincronizada automáticamente desde la API: " . number_format($resultado['tasa_bcv'], 4) . " Bs/USD.",
                    $resultado
                );
            } else {
                // Modo manual de contingencia (si no hay internet en el supermercado)
                $nuevaTasa = (float) ($_POST['tasa_bcv'] ?? 0);
                $this->authModel->actualizarTasaPeriodoAbierto($nuevaTasa);
                $this->jsonResponse(
                    true,
                    "Tasa BCV actualizada manualmente a " . number_format($nuevaTasa, 4) . " Bs/USD.",
                    ['tasa_bcv' => $nuevaTasa, 'fecha' => date('d/m/Y h:i A')]
                );
            }
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage(), [], 400);
        }
    }

    /**
     * Cierra el mes en curso y abre uno nuevo (Exclusivo Contador)
     */
    public function cambiarPeriodo(): void
    {
        $this->requireRole(['Contador']);

        try {
            $anio = (int) ($_POST['anio_fiscal'] ?? date('Y'));
            $mes  = (int) ($_POST['mes_fiscal'] ?? date('m'));
            $tasa = (float) ($_POST['tasa_bcv'] ?? 50.00);

            if ($mes < 1 || $mes > 12) {
                throw new Exception("El mes fiscal debe estar entre 1 y 12.");
            }

            $this->authModel->abrirNuevoPeriodo($anio, $mes, $tasa);
            $this->jsonResponse(true, "Periodo {$mes}/{$anio} abierto exitosamente. El periodo anterior fue cerrado.");
        } catch (Exception $e) {
            $this->jsonResponse(false, "Error al gestionar el periodo: " . $e->getMessage(), [], 400);
        }
    }
}