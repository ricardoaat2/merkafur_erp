<div class="mk-tabs reveal stagger-2 no-print">
    <button type="button" class="mk-tab-btn active" data-tab="tab-nuevo-z">
        Registrar Cierre de Caja POS (Reporte Z y Arqueo)
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-libro-ventas">
        Libro de Ventas Fiscal
    </button>
</div>

<div id="tab-nuevo-z" class="mk-tab-panel active">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Cierre Diario de Caja Fiscal (Reporte Z) y Cuadre de Medios de Pago</div>
            <span class="code-badge">Tasa BCV Aplicada: <strong id="pos_tasa_bcv_display"><?= number_format($tasaActual, 4) ?></strong> Bs/USD</span>
        </div>

        <form id="formCierrePosZ" onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>ventas_pos/registrarCierreZ');">
            <!-- Pasamos la tasa actual oculta para que JavaScript haga los cálculos -->
            <input type="hidden" id="pos_tasa_bcv_val" value="<?= $tasaActual ?>">

            <h4 style="margin-bottom:0.75rem; color:var(--mk-cobalt-800);">1. Datos de la Impresora Fiscal y Facturación del Turno (Reporte Z)</h4>
            <div class="form-grid" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label>Terminal de Caja</label>
                    <input type="text" name="numero_caja" class="mk-input mono" placeholder="Ej: CAJA-01" value="CAJA-01" required>
                </div>
                <div class="form-group">
                    <label>Serial Máquina Fiscal</label>
                    <input type="text" name="serial_maquina_fiscal" class="mk-input mono" placeholder="Ej: Z1B8049210" value="Z1B8049210" required>
                </div>
                <div class="form-group">
                    <label>Número de Reporte Z</label>
                    <input type="text" name="numero_reporte_z" class="mk-input mono" placeholder="Ej: 0418" required>
                </div>
                <div class="form-group">
                    <label>Fecha de Operación</label>
                    <input type="date" name="fecha_operacion" class="mk-input mono" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Ventas Exentas de IVA (Bs)</label>
                    <input type="number" step="0.01" id="pos_exentas" name="ventas_exentas_bs" class="mk-input mono" placeholder="Ej: 1500.00" value="0.00">
                </div>
                <div class="form-group">
                    <label>Base Imponible Gravada 16% (Bs)</label>
                    <input type="number" step="0.01" id="pos_base" name="base_imponible_bs" class="mk-input mono" placeholder="Ej: 2000.00" value="0.00">
                    <span class="helper-hint">Monto sujeto a IVA 16%</span>
                </div>
                <div class="form-group">
                    <label>IVA 16% Calculado Automático (Bs)</label>
                    <!-- NOTA: Añadimos name="iva_cobrado_bs" para que el formulario lo envíe directamente -->
                    <input type="number" step="0.01" id="pos_iva_auto" name="iva_cobrado_bs" class="mk-input mono" value="0.00" readonly style="background:#E2E8F0; font-weight:700;">
                    <span class="helper-hint">16% calculado de la Base Imponible</span>
                </div>
                <div class="form-group">
                    <label>IGTF 3% Percibido en Divisas (Bs)</label>
                    <input type="number" step="0.01" id="pos_igtf" name="igtf_cobrado_bs" class="mk-input mono" placeholder="0.00" value="0.00">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Costo de Venta del Turno (Descarga de Kardex Bs)</label>
                    <input type="number" step="0.01" id="pos_costo_venta" name="costo_venta_total_bs" class="mk-input mono" placeholder="Ej: 2400.00" value="0.00">
                </div>
            </div>

            <h4 style="margin-bottom:0.75rem; color:var(--mk-cobalt-800);">2. Arqueo Físico de Medios de Pago Entregados por el Cajero</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>Efectivo Moneda Nacional (Bs)</label>
                    <input type="number" step="0.01" id="pos_efectivo" name="cobrado_efectivo_bs" class="mk-input mono" placeholder="Ej: 500.00" value="0.00">
                </div>

                <!-- CAMBIO CLAVE: Ingreso en DÓLARES directos y conversión automática a Bs -->
                <div class="form-group" style="background:#FEF9C3; padding:0.65rem; border-radius:8px; border:1px solid #F59E0B;">
                    <label style="color:#92400E;">Efectivo Divisas Físico ($ USD)</label>
                    <input type="number" step="0.01" id="pos_divisas_usd" name="divisas_usd" class="mk-input mono" placeholder="Ej: 10.00" value="0.00" style="font-weight:700; border-color:#F59E0B;">
                    <span class="helper-hint" style="color:#B45309;">
                        Equivalente BCV: <strong id="lbl_divisas_convertidas_bs">0.00 Bs</strong>
                    </span>
                </div>

                <div class="form-group">
                    <label>Puntos de Venta TDD/TDC y Biopago (Bs)</label>
                    <input type="number" step="0.01" id="pos_punto_venta" name="cobrado_punto_venta_bs" class="mk-input mono" placeholder="Ej: 1200.00" value="0.00">
                </div>
                <div class="form-group">
                    <label>Pago Móvil y Transferencias (Bs)</label>
                    <input type="number" step="0.01" id="pos_pago_movil" name="cobrado_pago_movil_bs" class="mk-input mono" placeholder="Ej: 800.00" value="0.00">
                </div>
            </div>

            <!-- Barra de estado y cuadre de arqueo en tiempo real -->
            <div class="partida-doble-bar balanced" style="margin:1.25rem 0; flex-wrap:wrap; gap:1rem;">
                <span>Total Facturado Máquina: <strong id="pos_preview_sistema">0.00 Bs</strong></span>
                <span>Total Arqueo Físico Contado: <strong id="pos_preview_contado">0.00 Bs</strong></span>
                <span>Diferencia de Caja: <span id="pos_preview_diferencia" class="badge-ok">0.00 Bs (Arqueo Exacto)</span></span>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:0.85rem 1.5rem; font-size:1rem;">
                Contabilizar Cierre Z en Libro Diario y Libro de Ventas
            </button>
        </form>
    </div>
</div>

<div id="tab-libro-ventas" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Libro de Ventas Fiscal (Consolidado por Reportes Z)</div>
            <button type="button" class="btn btn-outline no-print" onclick="window.print()">🖨️ Imprimir Libro de Ventas</button>
        </div>

        <div class="table-responsive">
            <table class="mk-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Caja / Serial Fiscal</th>
                        <th>Reporte Z</th>
                        <th class="num-col">Ventas Exentas</th>
                        <th class="num-col">Base Imponible</th>
                        <th class="num-col">IVA Débito (16%)</th>
                        <th class="num-col">IGTF (3%)</th>
                        <th class="num-col">Total Facturado</th>
                        <th class="num-col">Dif. Arqueo</th>
                        <th>Asiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cierres)): ?>
                        <tr><td colspan="10" style="text-align:center; padding:1.5rem; color:var(--mk-text-muted);">No hay cierres registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cierres as $z): ?>
                        <tr>
                            <td class="mono"><?= $z['fecha_operacion'] ?></td>
                            <td><strong><?= htmlspecialchars($z['numero_caja']) ?></strong><br><small><?= htmlspecialchars($z['serial_maquina_fiscal']) ?></small></td>
                            <td><span class="code-badge">Z-<?= htmlspecialchars($z['numero_reporte_z']) ?></span></td>
                            <td class="num-col"><?= number_format($z['ventas_exentas_bs'], 2) ?></td>
                            <td class="num-col"><?= number_format($z['base_imponible_bs'], 2) ?></td>
                            <td class="num-col"><?= number_format($z['iva_cobrado_bs'], 2) ?></td>
                            <td class="num-col"><?= number_format($z['igtf_cobrado_bs'], 2) ?></td>
                            <td class="num-col" style="font-weight:700;"><?= number_format($z['total_ventas_sistema_bs'], 2) ?></td>
                            <td class="num-col">
                                <?php if (abs($z['diferencia_caja_bs']) < 0.01): ?>
                                    <span class="badge-ok">0.00</span>
                                <?php elseif ($z['diferencia_caja_bs'] < 0): ?>
                                    <span class="badge-err"><?= number_format($z['diferencia_caja_bs'], 2) ?></span>
                                <?php else: ?>
                                    <span class="badge-acreedora">+<?= number_format($z['diferencia_caja_bs'], 2) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="code-badge"><?= $z['numero_asiento'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>