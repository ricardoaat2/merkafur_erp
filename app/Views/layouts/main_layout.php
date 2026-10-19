<?php
// ============================================================================
// ARCHIVO: app/Views/layouts/main_layout.php
// DESCRIPCIÓN: Layout Maestro con Identidad Merkafur, Ticker BCV y KPIs
// ============================================================================

if (!isset($resumen)) {
    $contModelTemp = new ContabilidadModel();
    $resumen = $contModelTemp->obtenerResumenEjecutivo();
}
$periodoActivo = $resumen['periodo'];
$tasaActual    = (float)($periodoActivo['tasa_bcv_vigente'] ?? 50.00);
$urlActual     = $_GET['url'] ?? 'contabilidad/libros';
$rolUsuario    = $_SESSION['usuario_rol'] ?? 'Contador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
</head>
<body>
<div class="erp-wrapper">

    <!-- ==================== SIDEBAR MERKAFUR ==================== -->
    <aside class="erp-sidebar">
        <div>
            <!-- LOGO OFICIAL MERKAFUR (SVG fiel al isotipo adjunto) -->
            <!-- LOGO OFICIAL MERKAFUR -->
                <div class="brand-box">
                    <img src="<?= BASE_URL ?>assets/img/logo_merkafur.png" alt="Merkafur Logo" style="height: 48px; max-width: 100%; object-fit: contain;">
                    <div>
                        <div class="brand-title">merkafur</div>
                        <span class="brand-subtitle">ERP Contable SQL</span>
                    </div>
                </div>
        <div class="nav-section-label">Núcleo Financiero</div>
            <ul class="erp-nav">
                <li>
                    <a href="<?= BASE_URL ?>contabilidad/libros" class="<?= str_contains($urlActual, 'libros') ? 'active' : '' ?>">
                        <span class="nav-icon">📖</span> Libro Diario y Mayor
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>contabilidad/planCuentas" class="<?= str_contains($urlActual, 'planCuentas') ? 'active' : '' ?>">
                        <span class="nav-icon">🗂️</span> Plan de Cuentas
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>contabilidad/estadosFinancieros" class="<?= str_contains($urlActual, 'estadosFinancieros') ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span> Estados Financieros
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Operación Supermercado</div>
            <ul class="erp-nav">
                <li>
                    <a href="<?= BASE_URL ?>compras/index" class="<?= str_contains($urlActual, 'compras') ? 'active' : '' ?>">
                        <span class="nav-icon">🛒</span> Compras y Retenciones
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>inventario/index" class="<?= str_contains($urlActual, 'inventario') ? 'active' : '' ?>">
                        <span class="nav-icon">📦</span> Inventario y Mermas
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>ventas_pos/index" class="<?= str_contains($urlActual, 'ventas_pos') ? 'active' : '' ?>">
                        <span class="nav-icon">🏧</span> Cierres POS (Reporte Z)
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Sistema y Fiscal</div>
            <ul class="erp-nav">
                <li>
                    <a href="<?= BASE_URL ?>auth/configuracion" class="<?= str_contains($urlActual, 'configuracion') ? 'active' : '' ?>">
                        <span class="nav-icon">⚙️</span> Periodos, Tasa y Usuarios
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tarjeta de Sesión y Rol Activo -->
        <div class="sidebar-user-card">
            <span class="role-pill">🛡️ <?= htmlspecialchars($rolUsuario) ?></span>
            <div style="font-weight:700; font-size:0.9rem; margin-bottom:0.6rem;">
                <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario Merkafur') ?>
            </div>
            <a href="<?= BASE_URL ?>auth/logout" class="btn btn-danger" style="width:100%; padding:0.45rem; font-size:0.8rem;">
                🚪 Cerrar Sesión Segura
            </a>
        </div>
    </aside>

    <!-- ==================== ÁREA PRINCIPAL ==================== -->
    <div class="erp-main">
        <!-- Barra Superior con Tasa BCV en Vivo -->
        <header class="erp-topbar">
            <div class="page-heading">
                <h1><?= htmlspecialchars($titulo ?? 'Panel Contable Merkafur') ?></h1>
            </div>

            <div class="topbar-tickers">
                <span class="code-badge">
                    📅 Periodo Activo: <?= sprintf('%02d/%04d', $periodoActivo['mes_fiscal'], $periodoActivo['anio_fiscal']) ?> (<?= $periodoActivo['estado'] ?? 'Abierto' ?>)
                </span>

                <div class="bcv-badge">
                    <span class="bcv-dot"></span>
                    <span>TASA BCV:</span>
                    <span class="bcv-value"><?= number_format($tasaActual, 4) ?> Bs/USD</span>
                </div>

                <button type="button" id="btnSyncBcv" class="btn btn-gold" 
                        onclick="sincronizarTasaBcvApi('<?= BASE_URL ?>auth/actualizarTasa')">
                    ⚡ Sincronizar BCV API
                </button>
            </div>
        </header>

        <!-- Contenido Dinámico de cada Vista -->
        <main class="erp-content">
            <!-- Cinta de KPIs Financieros en Tiempo Real -->
            <section class="kpi-strip reveal stagger-1 no-print">
                <div class="kpi-card">
                    <div class="kpi-label"><span>📈 Ventas Netas Mes</span><span>Ingreso</span></div>
                    <div class="kpi-amount"><?= number_format($resumen['ventas_netas_bs'], 2) ?> Bs</div>
                </div>
                <div class="kpi-card gold-accent">
                    <div class="kpi-label"><span>💎 Utilidad del Ejercicio</span><span>Resultado</span></div>
                    <div class="kpi-amount"><?= number_format($resumen['utilidad_neta_bs'], 2) ?> Bs</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label"><span>📦 Inventario Valorizado</span><span>Kardex</span></div>
                    <div class="kpi-amount"><?= number_format($resumen['inventario_bs'], 2) ?> Bs</div>
                </div>
                <div class="kpi-card gold-accent">
                    <div class="kpi-label"><span>🧾 Cuentas por Pagar</span><span>Pasivo</span></div>
                    <div class="kpi-amount"><?= number_format($resumen['cxp_pendiente_bs'], 2) ?> Bs</div>
                </div>
            </section>

            <?php require_once $viewFile; ?>
        </main>
    </div>

</div>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>