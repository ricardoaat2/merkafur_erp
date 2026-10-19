<?php
// ============================================================================
// ARCHIVO: core/Router.php
// DESCRIPCIÓN: Enrutador MVC que mapea URLs hacia Controladores y Métodos
// ============================================================================

class Router
{
    private string $controller = 'ContabilidadController';
    private string $method     = 'index';
    private array  $params     = [];

    public function dispatch(): void
    {
        $url = $this->parseUrl();

        // Si no hay sesión activa, forzar el paso por AuthController
        if (!isset($_SESSION['usuario_id'])) {
            $this->controller = 'AuthController';
            $this->method = (isset($url[1]) && $url[1] === 'autenticar') ? 'autenticar' : 'login';
            $controllerInstance = new $this->controller();
            call_user_func([$controllerInstance, $this->method]);
            return;
        }

        // 1. Resolver el Controlador a partir del primer segmento de la URL
        // Ejemplo: "ventas_pos" -> "VentasPosController" | "compras" -> "ComprasController"
        if (!empty($url[0])) {
            $formattedName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($url[0]))));
            $candidateController = $formattedName . 'Controller';

            if (file_exists(__DIR__ . '/../app/Controllers/' . $candidateController . '.php')) {
                $this->controller = $candidateController;
                unset($url[0]);
            } else {
                $this->abort(404, "El módulo solicitado ({$candidateController}) no existe.");
            }
        }

        $controllerObject = new $this->controller();

        // 2. Resolver el Método a partir del segundo segmento de la URL
        if (isset($url[1])) {
            if (method_exists($controllerObject, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                $this->abort(404, "La acción solicitada ({$url[1]}) no existe en {$this->controller}.");
            }
        }

        // 3. Extraer parámetros restantes y ejecutar
        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$controllerObject, $this->method], $this->params);
    }

    private function parseUrl(): array
    {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        die("<h2>Error {$code} - Merkafur ERP</h2><p>{$message}</p><a href='" . BASE_URL . "'>Volver al inicio</a>");
    }
}