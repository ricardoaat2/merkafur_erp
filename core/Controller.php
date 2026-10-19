<?php
// ============================================================================
// ARCHIVO: core/Controller.php
// DESCRIPCIÓN: Controlador Base con carga de vistas, JSON y seguridad de roles
// ============================================================================

abstract class Controller
{
    /**
     * Renderiza una vista inyectándola dentro del layout principal del ERP
     */
    protected function render(string $viewPath, array $data = [], bool $useLayout = true): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../app/Views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            die("Error: No se encontró la vista '{$viewPath}'.");
        }

        if ($useLayout) {
            require_once __DIR__ . '/../app/Views/layouts/main_layout.php';
        } else {
            require_once $viewFile;
        }
    }

    /**
     * Envía una respuesta JSON limpia para peticiones asíncronas (Fetch API)
     */
    protected function jsonResponse(bool $ok, string $mensaje, array $extra = [], int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'ok'      => $ok,
            'mensaje' => $mensaje
        ], $extra));
        exit;
    }

    /**
     * Verifica que el usuario autenticado tenga uno de los roles permitidos
     * Ejemplo de uso: $this->requireRole(['Contador']);
     */
    protected function requireRole(array $rolesPermitidos): void
    {
        $rolActual = $_SESSION['usuario_rol'] ?? '';

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            // Si la petición viene por Fetch/AJAX, responder JSON 403
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
                (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
                $this->jsonResponse(
                    false,
                    "Acceso denegado: Esta operación requiere el rol de " . implode(' o ', $rolesPermitidos) . ".",
                    [],
                    403
                );
            }

            http_response_code(403);
            die("<h2>Acceso Restringido (Rol: {$rolActual})</h2>
                 <p>Esta función está reservada para el perfil de: <strong>" . implode(' / ', $rolesPermitidos) . "</strong>.</p>
                 <a href='" . BASE_URL . "'>Regresar al panel principal</a>");
        }
    }

    /**
     * Redirige a una ruta interna del sistema
     */
    protected function redirect(string $route = ''): void
    {
        header('Location: ' . BASE_URL . ltrim($route, '/'));
        exit;
    }
}