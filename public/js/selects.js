// ============================================
// SELECTS
// ============================================

window.cargarSelectEmpresas = function() {
    fetch('/orange-proyect/public/contratistas?limit=999')
        .then(res => res.json())
        .then(res => {
            const data = res.data || res;
            
            const selectConductores = document.querySelector('#contratista');
            if (selectConductores) {
                selectConductores.innerHTML = '<option value="">Seleccione una empresa</option>' + data.map(x => `<option value="${x.id}">${escapeHtml(x.nombre)}</option>`).join('');
            }
            
            const selectVehiculos = document.querySelector('#contratista_vehiculo');
            if (selectVehiculos) {
                selectVehiculos.innerHTML = '<option value="">Seleccione una empresa</option>' + data.map(x => `<option value="${x.id}">${escapeHtml(x.nombre)}</option>`).join('');
            }
            
            const selectEmpresaFiltro = document.querySelector('#empresaFiltro');
            if (selectEmpresaFiltro) {
                selectEmpresaFiltro.innerHTML = '<option value="">Todas las empresas</option>' + data.map(x => `<option value="${x.id}">${escapeHtml(x.nombre)}</option>`).join('');
            }
            
            const selectEmpresaVehiculo = document.querySelector('#empresaFiltroVehiculo');
            if (selectEmpresaVehiculo) {
                selectEmpresaVehiculo.innerHTML = '<option value="">Todas las empresas</option>' + data.map(x => `<option value="${x.id}">${escapeHtml(x.nombre)}</option>`).join('');
            }
        })
        .catch(e => console.error(e));
};

window.cargarContratistasSelect = async function() {

    const res = await fetch('/orange-proyect/public/contratistas?limit=999');
    const json = await res.json();

    const data = json.data || json;

    const selectConductores =
        document.querySelector('#contratista');

    if (selectConductores) {

        selectConductores.innerHTML =
            '<option value="">Seleccione una empresa</option>' +
            data.map(x =>
                `<option value="${x.id}">
                    ${escapeHtml(x.nombre)}
                </option>`
            ).join('');
    }

    const selectEmpresaVehiculo =
        document.querySelector('#empresaFiltroVehiculo');

    if (selectEmpresaVehiculo) {

        selectEmpresaVehiculo.innerHTML =
            '<option value="">Todas las empresas</option>' +
            data.map(x =>
                `<option value="${x.id}">
                    ${escapeHtml(x.nombre)}
                </option>`
            ).join('');
    }
};