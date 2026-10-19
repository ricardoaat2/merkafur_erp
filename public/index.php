<?php
// ============================================================================
// ARCHIVO: public/index.php
// DESCRIPCIÓN: Punto de entrada único (Front Controller) del ERP Merkafur
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar configuración global
require_once __DIR__ . '/../config/config.php';

// 2. Cargar las 4 clases del núcleo MVC (core/)
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/MotorContable.php';
require_once __DIR__ . '/../core/Router.php';

// 3. Autocargador simple para Modelos y Controladores de la carpeta app/
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Models/'
    ];
    foreach ($directories as $dir) {
        $file = $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 4. Iniciar el enrutador
$router = new Router();
$router->dispatch();