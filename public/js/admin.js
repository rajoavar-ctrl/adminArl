// ============================================
// VARIABLES GLOBALES
// ============================================
let idEditar = null;
let paginaActual = {
    contratistas: 1,
    conductores: 1,
    vehiculos: 1
};

// ============================================
// NAVEGACIÓN
// ============================================
window.cargarVista = function(vista) {
    document.getElementById('titulo').innerText = vista.toUpperCase();
    fetch(`/orange-proyect/public/views/${vista}.html`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('contenido').innerHTML = html;
            setTimeout(() => initVista(vista), 50);
        });
};

const vistas = {

      dashboard() {
        cargarDashboard();
    },

    contratistas() {
        cargarContratistas();
        inicializarFiltrosContratistas();
    },

    conductores() {
        cargarContratistasSelect();
        cargarSelectEmpresas();
        cargarConductores(1);
        inicializarFiltrosConductores();

    },

    vehiculos() {
        cargarContratistasSelect()
            .then(() => {
                cargarVehiculos(1);
                activarFiltroConductores();
                cargarSelectConductoresFiltro();
                filtrarConductoresPorEmpresa();
                 inicializarFiltrosVehiculos();
            });
    },

    solicitudes() {
        cargarSolicitudes();
    }

};

function initVista(vista) {
    vistas[vista]?.();
}

// ============================================
// INICIO
// ============================================
cargarVista('dashboard');



