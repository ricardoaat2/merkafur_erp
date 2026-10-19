<div class="form-grid reveal stagger-2">

    <!-- Panel 1: Tasa BCV (API DolarVzla + Respaldo Manual) -->
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Tasa Oficial BCV (API + Contingencia)</div>
        </div>

        <p style="font-size:0.86rem; color:var(--mk-text-muted); margin-bottom:1rem;">
            El sistema consulta automáticamente la API con tu header <code>x-dolarvzla-key</code>. 
            Si no hay conexión a internet en la tienda, puedes fijar la tasa manualmente aquí:
        </p>

        <form onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>auth/actualizarTasa');">
            <input type="hidden" name="modo" value="manual">
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Valor Manual de Contingencia (Bs/USD)</label>
                <input type="number" step="0.0001" name="tasa_bcv" class="mk-input mono" 
                       placeholder="Ej: 50.2500" value="<?= number_format($tasaActual, 4, '.', '') ?>" required>
                <span class="helper-hint">Ejemplo: 50.4500 (Use punto para separar decimales)</span>
            </div>

            <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <button type="button" class="btn btn-gold" onclick="sincronizarTasaBcvApi('<?= BASE_URL ?>auth/actualizarTasa')">
                    Sincronizar desde API DolarVzla
                </button>
                <button type="submit" class="btn btn-outline">
                    Guardar Tasa Manual
                </button>
            </div>
        </form>
    </div>

    <!-- Panel 2: Control de Periodos Fiscales (Exclusivo Contador) -->
    <div class="mk-panel">
        <div class="mk-panel-header">
            <div class="mk-panel-title">Apertura y Cierre de Periodo Fiscal</div>
            <span class="badge-acreedora">Exclusivo Contador</span>
        </div>

        <form onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>auth/cambiarPeriodo');">
            <div class="form-grid" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label>Año Fiscal</label>
                    <input type="number" name="anio_fiscal" class="mk-input mono" placeholder="Ej: 2026" value="<?= date('Y') ?>" required>
                </div>
                <div class="form-group">
                    <label>Mes Fiscal (1 al 12)</label>
                    <input type="number" min="1" max="12" name="mes_fiscal" class="mk-input mono" placeholder="Ej: 11" value="<?= date('n') ?>" required>
                </div>
                <div class="form-group">
                    <label>Tasa BCV de Apertura</label>
                    <input type="number" step="0.0001" name="tasa_bcv" class="mk-input mono" placeholder="Ej: 50.0000" value="<?= number_format($tasaActual, 4, '.', '') ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Cerrar Mes Anterior y Abrir Nuevo Periodo
            </button>
        </form>
    </div>

</div>

<!-- Panel 3: Gestión de Usuarios (Administrador y Contador) -->
<div class="mk-panel reveal stagger-3">
    <div class="mk-panel-header">
        <div class="mk-panel-title">Usuarios Autorizados del ERP</div>
        <span class="code-badge">Opción B: Tabla Roles Relacional</span>
    </div>

    <form onsubmit="event.preventDefault(); enviarFormularioAjax(this, '<?= BASE_URL ?>auth/guardarUsuario');" style="margin-bottom:1.5rem;">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre_completo" class="mk-input" placeholder="Ej: Lic. María Fernanda Gómez" required>
            </div>
            <div class="form-group">
                <label>Nombre de Usuario (Login)</label>
                <input type="text" name="username" class="mk-input mono" placeholder="Ej: mgomez_cont" required>
            </div>
            <div class="form-group">
                <label>Rol en el ERP</label>
                <select name="id_rol" class="mk-select" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contraseña Inicial</label>
                <input type="password" name="password" class="mk-input mono" placeholder="Mínimo 6 caracteres" required>
            </div>
        </div>
        <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary">➕ Registrar Nuevo Usuario</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="mk-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Rol Asignado</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="num-col">#<?= $u['id_usuario'] ?></td>
                    <td><strong><?= htmlspecialchars($u['nombre_completo']) ?></strong></td>
                    <td><span class="code-badge"><?= htmlspecialchars($u['username']) ?></span></td>
                    <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
                    <td>
                        <span class="<?= $u['activo'] ? 'badge-ok' : 'badge-err' ?>">
                            <?= $u['activo'] ? '● Activo' : '○ Inactivo' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>