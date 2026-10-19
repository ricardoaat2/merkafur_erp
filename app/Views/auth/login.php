<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Merkafur ERP</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
</head>
<body>
<div class="login-shell">

    <!-- Columna Izquierda: Identidad Corporativa Merkafur -->
    <div class="login-hero">
        <div class="brand-box" style="max-width: 300px;">
            <img src="<?= BASE_URL ?>assets/img/logo_merkafur.png" alt="Merkafur Logo" style="height: 52px; max-width: 100%; object-fit: contain;">
            <div>
                <div class="brand-title">merkafur</div>
                <span class="brand-subtitle">Cadena de Supermercados</span>
            </div>
        </div>

        <div class="reveal stagger-1">
            <h2 style="font-family:var(--font-display); font-size:2.4rem; line-height:1.12; margin-bottom:1rem;">
                Control Contable, Fiscal e Inventario en Tiempo Real.
            </h2>
            <p style="color:rgba(255,255,255,0.8); font-size:1.02rem; max-width:520px; line-height:1.6;">
                Plataforma ERP de Partida Doble automatizada para la gestión de Libros Contables, 
                Cierres de Caja POS (Reporte Z), Retenciones Tributarias y Kardex Valorizado.
            </p>
        </div>

        <div style="font-family:var(--font-mono); font-size:0.78rem; color:var(--mk-gold-400);">
            Sincronización Automática BCV API · Arquitectura MVC + SQL
        </div>
    </div>

    <!-- Columna Derecha: Formulario de Inicio de Sesión -->
    <div class="login-form-side">
        <div class="login-card reveal stagger-2">
            <h3 style="font-family:var(--font-display); font-size:1.55rem; margin-bottom:0.35rem;">
                Iniciar Sesión
            </h3>
            <p style="color:var(--mk-text-muted); font-size:0.88rem; margin-bottom:1.5rem;">
                Ingrese sus credenciales de Administrador o Contador.
            </p>

            <?php if (!empty($error)): ?>
                <div class="partida-doble-bar unbalanced" style="margin-bottom:1.2rem; font-size:0.83rem;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>auth/autenticar" method="POST">
                <div class="form-group" style="margin-bottom:1.1rem;">
                    <label for="username">Usuario del Sistema</label>
                    <input type="text" id="username" name="username" class="mk-input mono" 
                           placeholder="Ej: admin o contador" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label for="password">Contraseña de Acceso</label>
                    <input type="password" id="password" name="password" class="mk-input mono" 
                           placeholder="Ej: 123456" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; font-size:0.96rem;">
                    Entrar al ERP de Merkafur
                </button>
            </form>

            <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px dashed var(--mk-border); font-size:0.78rem; color:var(--mk-text-muted);">
                <strong>Cuentas Iniciales de Prueba (Clave: <code>123456</code>):</strong><br>
                • Rol Administrador: <code>admin</code> | • Rol Contador: <code>contador</code>
            </div>
        </div>
    </div>

</div>
</body>
</html>