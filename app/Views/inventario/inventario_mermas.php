<div class="mk-panel reveal stagger-2 no-print">
    <div class="mk-panel-header">
        <div class="mk-panel-title">Registrar Acta de Merma (Desincorporación por Perecederos, Rotura o Vencimiento)</div>
    </div>

    <form id="formRegistrarMerma" onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>inventario/registrarMerma');">
        <div class="form-grid">
            <div class="form-group">
                <label>Producto Afectado (Stock y Costo Promedio)</label>
                <select id="merma_id_producto" name="id_producto" class="mk-select" required>
                    <?php foreach ($productos as $pr): ?>
                        <option value="<?= $pr['id_producto'] ?>" data-costo="<?= $pr['costo_promedio_bs'] ?>">
                            <?= htmlspecialchars($pr['codigo_barras']) ?> — <?= htmlspecialchars($pr['nombre_producto']) ?> 
                            (Stock: <?= $pr['stock_actual'] ?> <?= $pr['unidad_medida'] ?> | Costo: <?= number_format($pr['costo_promedio_bs'], 2) ?> Bs)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cantidad a Desincorporar</label>
                <input type="number" step="0.001" id="merma_cantidad" name="cantidad" class="mk-input mono" placeholder="Ej: 5.250" required>
                <span class="helper-hint">Permite decimales para carnes/fruver en KG</span>
            </div>
            <div class="form-group">
                <label>Motivo del Acta de Merma</label>
                <input type="text" name="motivo" class="mk-input" placeholder="Ej: Falla eléctrica en cava cuarto de carnicería" required>
            </div>
            <div class="form-group">
                <label>Fecha del Acta</label>
                <input type="date" name="fecha" class="mk-input mono" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="partida-doble-bar unbalanced" style="margin:1.1rem 0;">
            <span>Imputación Automática al Costo Promedio (Gasto por Merma vs Inventario):</span>
            <strong id="merma_preview_costo" style="font-size:1.1rem;">0.00 Bs</strong>
        </div>

        <button type="submit" class="btn btn-primary">
            Procesar Merma y Generar Asiento en Libro Diario
        </button>
    </form>
</div>

<div class="mk-panel reveal stagger-3">
    <div class="mk-panel-header">
        <div class="mk-panel-title">Maestro de Artículos y Kardex Valorizado de Merkafur</div>
        <button type="button" class="btn btn-outline no-print" onclick="window.print()">🖨️ Imprimir Inventario</button>
    </div>

    <div class="table-responsive">
        <table class="mk-table">
            <thead>
                <tr>
                    <th>Código EAN</th>
                    <th>Producto</th>
                    <th>Departamento Contable</th>
                    <th>Unidad</th>
                    <th class="num-col">Stock Actual</th>
                    <th class="num-col">Costo Promedio (Bs)</th>
                    <th class="num-col">Precio Venta (USD)</th>
                    <th class="num-col">Valor Total Contable (Bs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): 
                    $valorTotal = $p['stock_actual'] * $p['costo_promedio_bs'];
                ?>
                <tr>
                    <td><span class="code-badge"><?= htmlspecialchars($p['codigo_barras']) ?></span></td>
                    <td><strong><?= htmlspecialchars($p['nombre_producto']) ?></strong></td>
                    <td><?= htmlspecialchars($p['nombre_departamento']) ?></td>
                    <td><?= $p['unidad_medida'] ?></td>
                    <td class="num-col"><?= number_format($p['stock_actual'], 3) ?></td>
                    <td class="num-col"><?= number_format($p['costo_promedio_bs'], 4) ?></td>
                    <td class="num-col">$<?= number_format($p['precio_venta_usd'], 2) ?></td>
                    <td class="num-col" style="font-weight:700;"><?= number_format($valorTotal, 2) ?> Bs</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>