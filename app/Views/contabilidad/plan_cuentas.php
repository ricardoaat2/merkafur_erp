<div class="mk-panel reveal stagger-2 no-print">
    <div class="mk-panel-header">
        <div class="mk-panel-title">➕ Incorporar Cuenta al Catálogo de Merkafur</div>
        <span class="badge-acreedora">Exclusivo Contador</span>
    </div>

    <form onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>contabilidad/guardarCuenta');">
        <div class="form-grid">
            <div class="form-group">
                <label>Código Jerárquico (X.XX.XX.XXX)</label>
                <input type="text" name="codigo_cuenta" class="mk-input mono" placeholder="Ej: 1.01.02.003" required>
            </div>
            <div class="form-group">
                <label>Nombre de la Cuenta Contable</label>
                <input type="text" name="nombre_cuenta" class="mk-input" placeholder="Ej: Banco Provincial - Cuenta Corriente" required>
            </div>
            <div class="form-group">
                <label>Clasificación Financiera</label>
                <select name="tipo_cuenta" class="mk-select">
                    <option value="Activo">1 - Activo</option>
                    <option value="Pasivo">2 - Pasivo</option>
                    <option value="Patrimonio">3 - Patrimonio</option>
                    <option value="Ingreso">4 - Ingreso</option>
                    <option value="Costo">5 - Costo de Venta</option>
                    <option value="Gasto">6 - Gasto Operativo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Naturaleza Contable</label>
                <select name="naturaleza" class="mk-select">
                    <option value="Deudora">Deudora (Aumenta por el Debe)</option>
                    <option value="Acreedora">Acreedora (Aumenta por el Haber)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nivel (1 a 4)</label>
                <input type="number" min="1" max="4" name="nivel" class="mk-input mono" value="4" required>
            </div>
            <div class="form-group">
                <label>¿Acepta Asientos Directos?</label>
                <select name="acepta_movimientos" class="mk-select">
                    <option value="1">Sí (Cuenta Auxiliar Nivel 4)</option>
                    <option value="0">No (Cuenta Carpeta / Totalizadora)</option>
                </select>
            </div>
        </div>
        <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Guardar Cuenta en el Plan</button>
        </div>
    </form>
</div>

<div class="mk-panel reveal stagger-3">
    <div class="mk-panel-header">
        <div class="mk-panel-title">Catálogo Maestro de Cuentas Contables (<?= count($cuentas) ?> Cuentas)</div>
        <button type="button" class="btn btn-outline no-print" onclick="window.print()">Imprimir Plan de Cuentas</button>
    </div>

    <div class="table-responsive">
        <table class="mk-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre de la Cuenta</th>
                    <th>Tipo</th>
                    <th>Naturaleza</th>
                    <th>Nivel</th>
                    <th>Imputable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuentas as $c): 
                    $indent = ($c['nivel'] - 1) * 22;
                    $esGrupo = !$c['acepta_movimientos'];
                ?>
                <tr style="<?= $esGrupo ? 'background:#F8FAFC; font-weight:700;' : '' ?>">
                    <td><span class="code-badge"><?= htmlspecialchars($c['codigo_cuenta']) ?></span></td>
                    <td style="padding-left: <?= 16 + $indent ?>px;">
                        <?= $esGrupo ? '📁 ' : '🔹 ' ?><?= htmlspecialchars($c['nombre_cuenta']) ?>
                    </td>
                    <td><?= htmlspecialchars($c['tipo_cuenta']) ?></td>
                    <td>
                        <span class="<?= $c['naturaleza'] === 'Deudora' ? 'badge-deudora' : 'badge-acreedora' ?>">
                            <?= $c['naturaleza'] ?>
                        </span>
                    </td>
                    <td class="num-col">Nivel <?= $c['nivel'] ?></td>
                    <td>
                        <?= $c['acepta_movimientos'] ? '<span class="badge-ok">✓ Detalle</span>' : '<span style="color:#64748B;">Totalizadora</span>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>