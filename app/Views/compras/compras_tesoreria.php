<div class="mk-tabs reveal stagger-2 no-print">
    <button type="button" class="mk-tab-btn active" data-tab="tab-nueva-compra">
        Registrar Factura de Compra + Retención IVA
    </button>
    <button type="button" class="mk-tab-btn" data-tab="tab-libro-compras">
        Libro de Compras y Cuentas por Pagar
    </button>
</div>

<div id="tab-nueva-compra" class="mk-tab-panel active">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Recepción de Factura de Proveedor (Genera Asiento y Retención Automática)</div>
        </div>

        <form id="formRegistrarCompra" onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>compras/registrarFactura');">
            <div class="form-grid">
                <div class="form-group">
                    <label>Proveedor Registrado</label>
                    <select id="compra_id_proveedor" name="id_proveedor" class="mk-select" required>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id_proveedor'] ?>" data-retencion="<?= $p['porcentaje_retencion_iva'] ?>">
                                <?= htmlspecialchars($p['rif']) ?> — <?= htmlspecialchars($p['razon_social']) ?> (Ret. <?= $p['porcentaje_retencion_iva'] ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de Factura</label>
                    <input type="text" name="numero_factura" class="mk-input mono" placeholder="Ej: FAC-009841" required>
                </div>
                <div class="form-group">
                    <label>Número de Control Fiscal</label>
                    <input type="text" name="numero_control" class="mk-input mono" placeholder="Ej: 00-0485912" required>
                </div>
                <div class="form-group">
                    <label>Fecha de Recepción Contable</label>
                    <input type="date" name="fecha_recepcion" class="mk-input mono" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Compras Exentas de IVA (Bs)</label>
                    <input type="number" step="0.01" id="compra_exento" name="monto_exento_bs" class="mk-input mono" placeholder="Ej: 20000.00" value="0.00">
                    <span class="helper-hint">Alimentos de canasta básica sin IVA</span>
                </div>
                <div class="form-group">
                    <label>Base Imponible Gravada 16% (Bs)</label>
                    <input type="number" step="0.01" id="compra_base" name="base_imponible_bs" class="mk-input mono" placeholder="Ej: 30000.00" value="0.00">
                    <span class="helper-hint">Víveres gravados, limpieza, otros</span>
                </div>
            </div>

            <!-- Resumen Previo del Asiento Automático -->
            <div class="partida-doble-bar balanced" style="margin:1.25rem 0;">
                <span>IVA 16% Crédito Fiscal: <strong id="preview_iva_compra">0.00 Bs</strong></span>
                <span>Total Factura: <strong id="preview_total_compra">0.00 Bs</strong></span>
                <span>Retención IVA SENIAT: <strong id="preview_ret_compra">0.00 Bs</strong></span>
                <span>Neto a Cuentas por Pagar: <strong id="preview_neto_cxp">0.00 Bs</strong></span>
            </div>

            <button type="submit" class="btn btn-primary">
                Contabilizar Compra y Emitir Comprobante de Retención
            </button>
        </form>
    </div>
</div>

<div id="tab-libro-compras" class="mk-tab-panel">
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Libro de Compras Fiscal y Control de Cuentas por Pagar</div>
            <button type="button" class="btn btn-outline no-print" onclick="window.print()">Imprimir Libro de Compras</button>
        </div>

        <div class="table-responsive">
            <table class="mk-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>RIF / Proveedor</th>
                        <th>Factura / Control</th>
                        <th>Comprobante Ret.</th>
                        <th class="num-col">Exento (Bs)</th>
                        <th class="num-col">Base Imp. (Bs)</th>
                        <th class="num-col">IVA 16% (Bs)</th>
                        <th class="num-col">IVA Retenido</th>
                        <th class="num-col">Saldo CxP (Bs)</th>
                        <th>Asiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $f): ?>
                    <tr>
                        <td class="mono"><?= $f['fecha_recepcion'] ?></td>
                        <td><strong><?= htmlspecialchars($f['razon_social']) ?></strong><br><small><?= $f['rif'] ?></small></td>
                        <td class="mono"><?= htmlspecialchars($f['numero_factura']) ?><br><small>Ctrl: <?= htmlspecialchars($f['numero_control']) ?></small></td>
                        <td><span class="code-badge"><?= htmlspecialchars($f['numero_comprobante_ret'] ?? 'N/A') ?></span></td>
                        <td class="num-col"><?= number_format($f['monto_exento_bs'], 2) ?></td>
                        <td class="num-col"><?= number_format($f['base_imponible_bs'], 2) ?></td>
                        <td class="num-col"><?= number_format($f['monto_iva_bs'], 2) ?></td>
                        <td class="num-col" style="color:var(--mk-haber-text);"><?= number_format($f['iva_retenido_bs'], 2) ?></td>
                        <td class="num-col" style="font-weight:700;"><?= number_format($f['saldo_pendiente_bs'], 2) ?></td>
                        <td><span class="code-badge"><?= $f['numero_asiento'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>