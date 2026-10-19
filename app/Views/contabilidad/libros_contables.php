<!-- Navegación por Pestañas -->
<div class="mk-tabs reveal stagger-2 no-print">
    <button type="button" class="mk-tab-btn active" data-tab="tab-diario">
        Libro Diario General
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-nuevo-asiento">
        Registrar Asiento Manual (Partida Doble)
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-mayor">
        Libro Mayor Analítico
    </button>
</div>

<!-- ==================== PESTAÑA 1: LIBRO DIARIO ==================== -->
<div id="tab-diario" class="mk-tab-panel active">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Libro Diario Legal — Asientos Mayorizados</div>
            <form method="GET" action="<?= BASE_URL ?>contabilidad/libros" class="no-print" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="date" name="inicio" value="<?= htmlspecialchars($fechaInicio) ?>" class="mk-input mono">
                <input type="date" name="fin" value="<?= htmlspecialchars($fechaFin) ?>" class="mk-input mono">
                <button type="submit" class="btn btn-primary">Filtrar Fechas</button>
                <button type="button" class="btn btn-outline" onclick="window.print()">Imprimir Diario</button>
            </form>
        </div>

        <?php if (empty($asientosDiario)): ?>
            <p style="padding:2rem; text-align:center; color:var(--mk-text-muted);">
                No existen asientos contables registrados en el rango de fechas seleccionado.
            </p>
        <?php else: ?>
            <?php foreach ($asientosDiario as $asi): ?>
                <div style="border:1px solid var(--mk-border); border-radius:var(--radius-md); margin-bottom:1.25rem; overflow:hidden;">
                    <div style="background:var(--mk-surface-alt); padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--mk-border);">
                        <div>
                            <span class="code-badge"><?= htmlspecialchars($asi['numero_asiento']) ?></span>
                            <strong style="margin-left:0.5rem;"><?= htmlspecialchars($asi['glosa_concepto']) ?></strong>
                        </div>
                        <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--mk-text-muted);">
                            <?= $asi['fecha_asiento'] ?> | Origen: <strong><?= $asi['origen_modulo'] ?></strong> | Ref: <?= htmlspecialchars($asi['documento_referencia'] ?? 'N/A') ?>
                        </div>
                    </div>

                    <table class="mk-table">
                        <thead>
                            <tr>
                                <th style="width:140px;">Código</th>
                                <th>Cuenta Contable</th>
                                <th>Descripción del Apunte</th>
                                <th class="num-col" style="width:160px;">Debe (Bs)</th>
                                <th class="num-col" style="width:160px;">Haber (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asi['renglones'] as $r): ?>
                            <tr>
                                <td><span class="code-badge"><?= htmlspecialchars($r['codigo_cuenta']) ?></span></td>
                                <td><strong><?= htmlspecialchars($r['nombre_cuenta']) ?></strong></td>
                                <td style="color:var(--mk-text-muted);"><?= htmlspecialchars($r['descripcion_linea']) ?></td>
                                <td class="num-col"><?= $r['debe_bs'] > 0 ? number_format($r['debe_bs'], 2) : '—' ?></td>
                                <td class="num-col"><?= $r['haber_bs'] > 0 ? number_format($r['haber_bs'], 2) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="background:#F8FAFC; font-weight:700;">
                                <td colspan="3" style="text-align:right;">TOTALES IGUALES DEL ASIENTO:</td>
                                <td class="num-col" style="color:var(--mk-debe-text);"><?= number_format($asi['total_debe_bs'], 2) ?> Bs</td>
                                <td class="num-col" style="color:var(--mk-haber-text);"><?= number_format($asi['total_haber_bs'], 2) ?> Bs</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== PESTAÑA 2: NUEVO ASIENTO MANUAL ==================== -->
<div id="tab-nuevo-asiento" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Nuevo Asiento Contable Manual / Ajuste</div>
            <span class="badge-acreedora">Validación en Vivo: Σ Debe = Σ Haber</span>
        </div>

        <div class="form-grid" style="margin-bottom:1.25rem;">
            <div class="form-group">
                <label>Fecha Contable</label>
                <input type="date" id="manual_fecha" class="mk-input mono" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Documento de Referencia</label>
                <input type="text" id="manual_referencia" class="mk-input mono" placeholder="Ej: AJU-0015 o ACTA-SOCIOS">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Glosa o Concepto General del Asiento</label>
                <input type="text" id="manual_glosa" class="mk-input" placeholder="Ej: Registro de aporte inicial de capital o ajuste de depreciación mensual">
            </div>
        </div>

        <div class="table-responsive">
            <table class="mk-table" id="tablaRenglonesManual">
                <thead>
                    <tr>
                        <th style="width:35%;">Cuenta Contable (Nivel 4)</th>
                        <th>Detalle de la Línea</th>
                        <th class="num-col" style="width:170px;">Debe (Bs)</th>
                        <th class="num-col" style="width:170px;">Haber (Bs)</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 2; $i++): ?>
                    <tr>
                        <td>
                            <select class="mk-select select-cuenta">
                                <?php foreach ($cuentasImputables as $cta): ?>
                                    <option value="<?= $cta['id_cuenta'] ?>">
                                        <?= $cta['codigo_cuenta'] ?> — <?= htmlspecialchars($cta['nombre_cuenta']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="mk-input input-desc-linea" placeholder="Ej: Detalle específico del cargo o abono">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="mk-input mono num-col input-debe" value="0.00">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="mk-input mono num-col input-haber" value="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger" onclick="eliminarRenglonAsiento(this)">🗑️</button>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div id="barraEstadoPartidaDoble" class="partida-doble-bar unbalanced">
            <div id="lblEstadoCuadre">⚠️ Ingrese montos iguales en Debe y Haber</div>
            <div style="display:flex; gap:1.5rem;">
                <span>Σ DEBE: <strong id="lblTotalDebe">0.00 Bs</strong></span>
                <span>Σ HABER: <strong id="lblTotalHaber">0.00 Bs</strong></span>
                <span>DIF: <strong id="lblDiferenciaAsiento">0.00 Bs</strong></span>
            </div>
        </div>

        <div style="margin-top:1.25rem; display:flex; justify-content:space-between;">
            <button type="button" class="btn btn-outline" onclick="agregarRenglonAsiento()">
                ➕ Agregar Renglón Contable
            </button>
            <button type="button" id="btnMayorizarAsiento" class="btn btn-primary" disabled
                    onclick="enviarAsientoManualJson('<?= BASE_URL ?>contabilidad/guardarAsientoManual')">
                Mayorizar Asiento en Libro Diario
            </button>
        </div>
    </div>
</div>

<!-- ==================== PESTAÑA 3: LIBRO MAYOR ANALÍTICO ==================== -->
<div id="tab-mayor" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">
                Libro Mayor Analítico: 
                <span class="code-badge"><?= htmlspecialchars($datosMayor['cuenta']['codigo_cuenta']) ?></span>
                <?= htmlspecialchars($datosMayor['cuenta']['nombre_cuenta']) ?>
            </div>

            <form method="GET" action="<?= BASE_URL ?>contabilidad/libros" class="no-print" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <select name="id_cuenta" class="mk-select" style="width:280px;">
                    <?php foreach ($cuentasImputables as $cta): ?>
                        <option value="<?= $cta['id_cuenta'] ?>" <?= $cta['id_cuenta'] == $idCuentaMayor ? 'selected' : '' ?>>
                            <?= $cta['codigo_cuenta'] ?> - <?= htmlspecialchars($cta['nombre_cuenta']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="inicio" value="<?= htmlspecialchars($fechaInicio) ?>" class="mk-input mono" style="width:145px;">
                <input type="date" name="fin" value="<?= htmlspecialchars($fechaFin) ?>" class="mk-input mono" style="width:145px;">
                <button type="submit" class="btn btn-primary">🔍 Consultar Mayor</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="mk-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Asiento</th>
                        <th>Módulo / Ref.</th>
                        <th>Concepto de la Línea</th>
                        <th class="num-col">Debe (Bs)</th>
                        <th class="num-col">Haber (Bs)</th>
                        <th class="num-col">Saldo Acumulado (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background:#FEF9C3; font-weight:700;">
                        <td colspan="6">SALDO INICIAL PREVIO AL <?= htmlspecialchars($fechaInicio) ?>:</td>
                        <td class="num-col"><?= number_format($datosMayor['saldo_inicial'], 2) ?> Bs</td>
                    </tr>
                    <?php foreach ($datosMayor['movimientos'] as $m): ?>
                    <tr>
                        <td class="mono"><?= $m['fecha_asiento'] ?></td>
                        <td><span class="code-badge"><?= htmlspecialchars($m['numero_asiento']) ?></span></td>
                        <td><?= htmlspecialchars($m['origen_modulo']) ?> (<?= htmlspecialchars($m['documento_referencia'] ?? '-') ?>)</td>
                        <td><?= htmlspecialchars($m['descripcion_linea']) ?></td>
                        <td class="num-col"><?= $m['debe_bs'] > 0 ? number_format($m['debe_bs'], 2) : '—' ?></td>
                        <td class="num-col"><?= $m['haber_bs'] > 0 ? number_format($m['haber_bs'], 2) : '—' ?></td>
                        <td class="num-col" style="font-weight:700;"><?= number_format($m['saldo_acumulado_bs'], 2) ?> Bs</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--mk-navy-900); color:#FFF; font-weight:700;">
                        <td colspan="4" style="text-align:right;">SUMAS Y SALDO FINAL DE LA CUENTA:</td>
                        <td class="num-col"><?= number_format($datosMayor['total_debe'], 2) ?> Bs</td>
                        <td class="num-col"><?= number_format($datosMayor['total_haber'], 2) ?> Bs</td>
                        <td class="num-col" style="color:var(--mk-gold-400);"><?= number_format($datosMayor['saldo_final'], 2) ?> Bs</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>