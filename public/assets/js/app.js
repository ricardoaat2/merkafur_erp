// ============================================================================
// ARCHIVO: public/assets/js/app.js
// DESCRIPCIÓN: Motor interactivo Frontend (Fetch API, Partida Doble y Cálculos)
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initPartidaDobleCalculator();
    initComprasCalculator();
    initCierrePosCalculator();
    initMermaCalculator();
});

/**
 * 1. Sistema de Pestañas (Tabs) sin recargar la página
 */
function initTabs() {
    const tabButtons = document.querySelectorAll('.mk-tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-tab');
            const parentContainer = btn.closest('.erp-content') || document;

            parentContainer.querySelectorAll('.mk-tab-btn').forEach(b => b.classList.remove('active'));
            parentContainer.querySelectorAll('.mk-tab-panel').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) targetPanel.classList.add('active');
        });
    });
}

/**
 * 2. Envío genérico de formularios por Fetch API con alertas claras
 */
async function enviarFormularioAjax(formElement, urlDestino, recargarAlTerminar = true) {
    const submitBtn = formElement.querySelector('button[type="submit"]');
    const textoOriginal = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Procesando...';
    }

    try {
        const formData = new FormData(formElement);
        const resp = await fetch(urlDestino, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();

        alert((data.ok ? '✅ ' : '⚠️ ') + data.mensaje);
        if (data.ok && recargarAlTerminar) {
            window.location.reload();
        }
    } catch (err) {
        alert('❌ Error de comunicación con el servidor: ' + err.message);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
    }
}

/**
 * 3. Sincronización de Tasa BCV con la API DolarVzla (Header x-dolarvzla-key)
 */
async function sincronizarTasaBcvApi(urlEndpoint) {
    const btn = document.getElementById('btnSyncBcv');
    if (btn) btn.innerHTML = '🔄 Conectando API BCV...';

    try {
        const formData = new FormData();
        formData.append('modo', 'api');

        const resp = await fetch(urlEndpoint, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        alert((data.ok ? '⚡ ' : '⚠️ ') + data.mensaje);
        if (data.ok) window.location.reload();
    } catch (error) {
        alert('❌ No se pudo contactar la API. Puede usar el ajuste manual de contingencia.');
    } finally {
        if (btn) btn.innerHTML = '⚡ Sincronizar BCV API';
    }
}

/**
 * 4. Calculadora en Vivo de Partida Doble (Libro Diario - Asiento Manual)
 */
function initPartidaDobleCalculator() {
    const tablaAsiento = document.getElementById('tablaRenglonesManual');
    if (!tablaAsiento) return;

    tablaAsiento.addEventListener('input', recalcularCuadreAsiento);
}

function agregarRenglonAsiento() {
    const tbody = document.querySelector('#tablaRenglonesManual tbody');
    const primeraFila = tbody.querySelector('tr');
    const nuevaFila = primeraFila.cloneNode(true);

    nuevaFila.querySelectorAll('input').forEach(inp => {
        if (inp.classList.contains('input-debe') || inp.classList.contains('input-haber')) {
            inp.value = '0.00';
        } else {
            inp.value = '';
        }
    });

    tbody.appendChild(nuevaFila);
    recalcularCuadreAsiento();
}

function eliminarRenglonAsiento(btn) {
    const tbody = document.querySelector('#tablaRenglonesManual tbody');
    if (tbody.querySelectorAll('tr').length <= 2) {
        alert('⚠️ Todo asiento contable requiere como mínimo 2 renglones (Debe y Haber).');
        return;
    }
    btn.closest('tr').remove();
    recalcularCuadreAsiento();
}

function recalcularCuadreAsiento() {
    let sumaDebe = 0;
    let sumaHaber = 0;

    document.querySelectorAll('#tablaRenglonesManual tbody tr').forEach(tr => {
        const debe = parseFloat(tr.querySelector('.input-debe')?.value || 0);
        const haber = parseFloat(tr.querySelector('.input-haber')?.value || 0);
        sumaDebe += isNaN(debe) ? 0 : debe;
        sumaHaber += isNaN(haber) ? 0 : haber;
    });

    const diff = Math.abs(sumaDebe - sumaHaber);
    const cuadrado = diff < 0.009 && sumaDebe > 0;

    document.getElementById('lblTotalDebe').textContent = sumaDebe.toFixed(2) + ' Bs';
    document.getElementById('lblTotalHaber').textContent = sumaHaber.toFixed(2) + ' Bs';
    document.getElementById('lblDiferenciaAsiento').textContent = (sumaDebe - sumaHaber).toFixed(2) + ' Bs';

    const statusBar = document.getElementById('barraEstadoPartidaDoble');
    const estadoTexto = document.getElementById('lblEstadoCuadre');
    const btnGuardar = document.getElementById('btnMayorizarAsiento');

    if (cuadrado) {
        statusBar.className = 'partida-doble-bar balanced';
        estadoTexto.innerHTML = '✅ PARTIDA DOBLE CUADRADA (Debe = Haber)';
        if (btnGuardar) btnGuardar.disabled = false;
    } else {
        statusBar.className = 'partida-doble-bar unbalanced';
        estadoTexto.innerHTML = '⚠️ ASIENTO DESCUADRADO — Revise los montos';
        if (btnGuardar) btnGuardar.disabled = true;
    }
}

async function enviarAsientoManualJson(urlEndpoint) {
    const fecha = document.getElementById('manual_fecha').value;
    const docRef = document.getElementById('manual_referencia').value;
    const glosa = document.getElementById('manual_glosa').value;

    const lineas = [];
    document.querySelectorAll('#tablaRenglonesManual tbody tr').forEach(tr => {
        lineas.push({
            id_cuenta: tr.querySelector('.select-cuenta').value,
            descripcion: tr.querySelector('.input-desc-linea').value || glosa,
            debe_bs: parseFloat(tr.querySelector('.input-debe').value || 0),
            haber_bs: parseFloat(tr.querySelector('.input-haber').value || 0)
        });
    });

    try {
        const resp = await fetch(urlEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                fecha_asiento: fecha,
                documento_referencia: docRef,
                glosa_concepto: glosa,
                lineas: lineas
            })
        });
        const data = await resp.json();
        alert((data.ok ? '✅ ' : '⚠️ ') + data.mensaje);
        if (data.ok) window.location.reload();
    } catch (e) {
        alert('❌ Error al guardar asiento: ' + e.message);
    }
}

/**
 * 5. Calculadora en Vivo para Facturas de Compra + Retención de IVA
 */
function initComprasCalculator() {
    const formCompra = document.getElementById('formRegistrarCompra');
    if (!formCompra) return;

    const calc = () => {
        const selectProv = document.getElementById('compra_id_proveedor');
        const opt = selectProv.options[selectProv.selectedIndex];
        const porcRet = parseFloat(opt?.getAttribute('data-retencion') || 75);

        const exento = parseFloat(document.getElementById('compra_exento').value || 0);
        const base = parseFloat(document.getElementById('compra_base').value || 0);
        const iva = +(base * 0.16).toFixed(2);
        const total = +(exento + base + iva).toFixed(2);
        const retencion = +(iva * (porcRet / 100)).toFixed(2);
        const netoPagar = +(total - retencion).toFixed(2);

        document.getElementById('preview_iva_compra').textContent = iva.toFixed(2) + ' Bs';
        document.getElementById('preview_total_compra').textContent = total.toFixed(2) + ' Bs';
        document.getElementById('preview_ret_compra').textContent = `${retencion.toFixed(2)} Bs (${porcRet}%)`;
        document.getElementById('preview_neto_cxp').textContent = netoPagar.toFixed(2) + ' Bs';
    };

    formCompra.addEventListener('input', calc);
    formCompra.addEventListener('change', calc);
    calc();
}

/**
 * 6. Calculadora en Vivo para Cierre de Caja POS (Reporte Z, Dólares a Tasa BCV y Arqueo)
 */
function initCierrePosCalculator() {
    const formPos = document.getElementById('formCierrePosZ');
    if (!formPos) return;

    const calcPos = () => {
        // Lee la tasa BCV oculta en el HTML
        const tasaBcv = parseFloat(document.getElementById('pos_tasa_bcv_val')?.value || 1);

        // 1. Facturación e IVA 16% automático
        const exento = parseFloat(document.getElementById('pos_exentas').value || 0);
        const base   = parseFloat(document.getElementById('pos_base').value || 0);
        
        // Calculamos el IVA y actualizamos el campo visible
        const iva = +(base * 0.16).toFixed(2);
        const inputIvaAuto = document.getElementById('pos_iva_auto');
        if (inputIvaAuto) inputIvaAuto.value = iva.toFixed(2);

        // 2. Divisas USD y su conversión a Bs
        const divisasUsd = parseFloat(document.getElementById('pos_divisas_usd')?.value || 0);
        const divisasEnBs = +(divisasUsd * tasaBcv).toFixed(2);

        // Mostramos el equivalente en Bs
        const lblDivisasBs = document.getElementById('lbl_divisas_convertidas_bs');
        if (lblDivisasBs) lblDivisasBs.textContent = divisasEnBs.toLocaleString('es-VE', {minimumFractionDigits: 2}) + ' Bs';

        // Auto-sugerencia de IGTF 3% sobre divisas en efectivo
        const inputIgtf = document.getElementById('pos_igtf');
        if (inputIgtf && document.activeElement !== inputIgtf) {
            const igtfSugerido = +(divisasEnBs * 0.03).toFixed(2);
            inputIgtf.value = igtfSugerido.toFixed(2);
        }
        const igtf = parseFloat(inputIgtf ? inputIgtf.value : 0) || 0;

        // 3. Arqueo
        const efec   = parseFloat(document.getElementById('pos_efectivo').value || 0);
        const pventa = parseFloat(document.getElementById('pos_punto_venta').value || 0);
        const pmovil = parseFloat(document.getElementById('pos_pago_movil').value || 0);

        const totalSistema = +(exento + base + iva + igtf).toFixed(2);
        const totalContado = +(efec + divisasEnBs + pventa + pmovil).toFixed(2);
        const diferencia   = +(totalContado - totalSistema).toFixed(2);

        // Actualizamos la barra inferior verde
        document.getElementById('pos_preview_sistema').textContent = totalSistema.toFixed(2) + ' Bs';
        document.getElementById('pos_preview_contado').textContent = totalContado.toFixed(2) + ' Bs';

        const badgeDif = document.getElementById('pos_preview_diferencia');
        if (Math.abs(diferencia) < 0.01) {
            badgeDif.className = 'badge-ok';
            badgeDif.textContent = '0.00 Bs (Arqueo Exacto)';
        } else if (diferencia < 0) {
            badgeDif.className = 'badge-err';
            badgeDif.textContent = `${diferencia.toFixed(2)} Bs (FALTANTE)`;
        } else {
            badgeDif.className = 'badge-acreedora';
            badgeDif.textContent = `+${diferencia.toFixed(2)} Bs (SOBRANTE)`;
        }
    };

    // Escuchamos CUALQUIER cambio en el formulario para recalcular
    formPos.addEventListener('input', calcPos);
    calcPos(); // Ejecutamos la primera vez al cargar
}

/**
 * 7. Calculadora de Costo de Merma en Inventario
 */
function initMermaCalculator() {
    const formMerma = document.getElementById('formRegistrarMerma');
    if (!formMerma) return;

    const calcMerma = () => {
        const sel = document.getElementById('merma_id_producto');
        const opt = sel.options[sel.selectedIndex];
        const costoUnit = parseFloat(opt?.getAttribute('data-costo') || 0);
        const cant = parseFloat(document.getElementById('merma_cantidad').value || 0);
        const total = +(costoUnit * cant).toFixed(2);

        document.getElementById('merma_preview_costo').textContent = total.toFixed(2) + ' Bs';
    };

    formMerma.addEventListener('input', calcMerma);
    formMerma.addEventListener('change', calcMerma);
    calcMerma();
}