<div class="mk-tabs reveal stagger-2 no-print">
    <button type="button" class="mk-tab-btn active" data-tab="tab-comprobacion">
        Balance de Comprobación
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-resultados">
        Estado de Resultados (Ganancias y Pérdidas)
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-general">
        Balance General (Situación Financiera)
    </button>
</div>

<!-- 1. BALANCE DE COMPROBACIÓN -->
<div id="tab-comprobacion" class="mk-tab-panel active">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Balance de Comprobación de Sumas y Saldos al <?= htmlspecialchars($fechaCorte) ?></div>
            <button type="button" class="btn btn-outline no-print" onclick="window.print()">Imprimir Reporte</button>
        </div>
        <div class="table-responsive">
            <table class="mk-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cuenta Contable</th>
                        <th>Naturaleza</th>
                        <th class="num-col">Suma Debe (Bs)</th>
                        <th class="num-col">Suma Haber (Bs)</th>
                        <th class="num-col">Saldo Deudor (Bs)</th>
                        <th class="num-col">Saldo Acreedor (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totSDebe = 0; $totSHaber = 0; $totDeudor = 0; $totAcreedor = 0;
                    foreach ($balanceComprobacion as $b): 
                        $totSDebe += $b['suma_debe'];
                        $totSHaber += $b['suma_haber'];
                        $totDeudor += $b['saldo_deudor'];
                        $totAcreedor += $b['saldo_acreedor'];
                    ?>
                    <tr>
                        <td><span class="code-badge"><?= $b['codigo_cuenta'] ?></span></td>
                        <td><strong><?= htmlspecialchars($b['nombre_cuenta']) ?></strong></td>
                        <td><?= $b['naturaleza'] ?></td>
                        <td class="num-col"><?= number_format($b['suma_debe'], 2) ?></td>
                        <td class="num-col"><?= number_format($b['suma_haber'], 2) ?></td>
                        <td class="num-col"><?= $b['saldo_deudor'] > 0 ? number_format($b['saldo_deudor'], 2) : '—' ?></td>
                        <td class="num-col"><?= $b['saldo_acreedor'] > 0 ? number_format($b['saldo_acreedor'], 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--mk-navy-900); color:#FFF; font-weight:700;">
                        <td colspan="3" style="text-align:right;">SUMAS IGUALES DE COMPROBACIÓN:</td>
                        <td class="num-col"><?= number_format($totSDebe, 2) ?> Bs</td>
                        <td class="num-col"><?= number_format($totSHaber, 2) ?> Bs</td>
                        <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($totDeudor, 2) ?> Bs</td>
                        <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($totAcreedor, 2) ?> Bs</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. ESTADO DE RESULTADOS -->
<div id="tab-resultados" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Estado de Resultados — Merkafur Supermercados</div>
            <button type="button" class="btn btn-outline no-print" onclick="window.print()">Imprimir</button>
        </div>

        <table class="mk-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Rubro / Cuenta de Resultado</th>
                    <th class="num-col">Monto (Bs)</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#EFF6FF; font-weight:700;"><td colspan="3">4. INGRESOS OPERATIVOS Y VENTAS POS</td></tr>
                <?php foreach ($estadoResultados['ingresos'] as $i): ?>
                <tr>
                    <td><span class="code-badge"><?= $i['codigo_cuenta'] ?></span></td>
                    <td><?= htmlspecialchars($i['nombre_cuenta']) ?></td>
                    <td class="num-col"><?= number_format($i['monto_presentacion'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:700;">
                    <td colspan="2" style="text-align:right;">TOTAL INGRESOS NETOS:</td>
                    <td class="num-col"><?= number_format($estadoResultados['total_ingresos'], 2) ?> Bs</td>
                </tr>

                <tr style="background:#FEF3C7; font-weight:700;"><td colspan="3">5. COSTO DE MERCANCÍA VENDIDA (KARDEX)</td></tr>
                <?php foreach ($estadoResultados['costos'] as $c): ?>
                <tr>
                    <td><span class="code-badge"><?= $c['codigo_cuenta'] ?></span></td>
                    <td><?= htmlspecialchars($c['nombre_cuenta']) ?></td>
                    <td class="num-col">(<?= number_format($c['monto_presentacion'], 2) ?>)</td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#ECFDF5; font-weight:800;">
                    <td colspan="2" style="text-align:right;">UTILIDAD BRUTA EN VENTAS:</td>
                    <td class="num-col"><?= number_format($estadoResultados['utilidad_bruta'], 2) ?> Bs</td>
                </tr>

                <tr style="background:#FEE2E2; font-weight:700;"><td colspan="3">6. GASTOS OPERATIVOS, MERMAS Y FINANCIEROS</td></tr>
                <?php foreach ($estadoResultados['gastos'] as $g): ?>
                <tr>
                    <td><span class="code-badge"><?= $g['codigo_cuenta'] ?></span></td>
                    <td><?= htmlspecialchars($g['nombre_cuenta']) ?></td>
                    <td class="num-col">(<?= number_format($g['monto_presentacion'], 2) ?>)</td>
                </tr>
                <?php endforeach; ?>

                <tr style="background:var(--mk-navy-900); color:#FFF; font-weight:800; font-size:1rem;">
                    <td colspan="2" style="text-align:right;">RESULTADO NETO DEL EJERCICIO (UTILIDAD / PÉRDIDA):</td>
                    <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($estadoResultados['utilidad_neta'], 2) ?> Bs</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. BALANCE GENERAL -->
<div id="tab-general" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Balance General (Activo = Pasivo + Patrimonio)</div>
            <span class="<?= $balanceGeneral['ecuacion_cuadrada'] ? 'badge-ok' : 'badge-err' ?>">
                <?= $balanceGeneral['ecuacion_cuadrada'] ? '✓ Ecuación Patrimonial Cuadrada' : '⚠ Descuadre Patrimonial' ?>
            </span>
        </div>

        <div class="form-grid">
            <!-- Columna Activos -->
            <div>
                <h4 style="margin-bottom:0.75rem; font-family:var(--font-display);">1. ACTIVOS DE MERKAFUR</h4>
                <table class="mk-table">
                    <?php foreach ($balanceGeneral['activos'] as $a): ?>
                    <tr>
                        <td><span class="code-badge"><?= $a['codigo_cuenta'] ?></span></td>
                        <td><?= htmlspecialchars($a['nombre_cuenta']) ?></td>
                        <td class="num-col"><?= number_format($a['monto_presentacion'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--mk-navy-900); color:#FFF; font-weight:700;">
                        <td colspan="2">TOTAL ACTIVOS:</td>
                        <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($balanceGeneral['total_activos'], 2) ?> Bs</td>
                    </tr>
                </table>
            </div>

            <!-- Columna Pasivos + Patrimonio -->
            <div>
                <h4 style="margin-bottom:0.75rem; font-family:var(--font-display);">2. PASIVOS Y 3. PATRIMONIO</h4>
                <table class="mk-table">
                    <?php foreach ($balanceGeneral['pasivos'] as $p): ?>
                    <tr>
                        <td><span class="code-badge"><?= $p['codigo_cuenta'] ?></span></td>
                        <td><?= htmlspecialchars($p['nombre_cuenta']) ?></td>
                        <td class="num-col"><?= number_format($p['monto_presentacion'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($balanceGeneral['patrimonio'] as $pt): ?>
                    <tr>
                        <td><span class="code-badge"><?= $pt['codigo_cuenta'] ?></span></td>
                        <td><?= htmlspecialchars($pt['nombre_cuenta']) ?></td>
                        <td class="num-col"><?= number_format($pt['monto_presentacion'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:#FEF9C3; font-weight:700;">
                        <td><span class="code-badge">3.01.01.004</span></td>
                        <td>Resultado del Ejercicio Actual</td>
                        <td class="num-col"><?= number_format($balanceGeneral['resultado_ejercicio'], 2) ?></td>
                    </tr>
                    <tr style="background:var(--mk-navy-900); color:#FFF; font-weight:700;">
                        <td colspan="2">TOTAL PASIVO + PATRIMONIO:</td>
                        <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($balanceGeneral['total_pasivo_patrimonio'], 2) ?> Bs</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>