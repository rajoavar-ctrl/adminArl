// ============================================
// CONTRATISTAS
// ============================================
window.cargarContratistas = function(page = 1) {
    const search = document.getElementById('searchContratista')?.value || '';
    let url = `/orange-proyect/public/contratistas?page=${page}&limit=5&search=${encodeURIComponent(search)}`;
    
    fetch(url)
    .then(res => res.json())
    .then(res => {
        const tbody = document.querySelector('#tablaContratistas');
        if (!tbody) return;
        
        const data = res.data || [];
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">No hay contratistas registrados</td></tr>';
            document.querySelector('#totalContratistas').textContent = '0';
            return;
        }
        
        let html = '';
        for (let i = 0; i < data.length; i++) {
            const c = data[i];
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(c.nombre) + '</strong></td>';
            html += '<td>' + escapeHtml(c.nit) + '</td>';
            html += '<td class="text-center">';
            html += '<button class="btn btn-sm btn-warning" onclick=\'editarContratista(' + JSON.stringify(c) + ')\'>✏️ Editar</button>';
            html += '<button class="btn btn-sm btn-danger" onclick="eliminarContratista(' + c.id + ')">🗑 Eliminar</button>';
            html += '</td>';
            html += '</tr>';
        }
        tbody.innerHTML = html;
        
        document.querySelector('#totalContratistas').textContent = res.total || data.length;
    })
    .catch(error => {
        console.error('Error:', error);
        const tbody = document.querySelector('#tablaContratistas');
        if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-center">Error al cargar datos</td></tr>';
    });
};

window.aplicarFiltrosContratistas = function() { cargarContratistas(1); };

window.crearContratista = function() {
    const nombre = document.getElementById('nombre')?.value;
    const nit = document.getElementById('nit')?.value;
    if (!nombre || !nit) { mostrarError('Todos los campos son obligatorios'); return; }
    
    mostrarCargando('Creando contratista...');
    fetch('/orange-proyect/public/contratistas', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ nombre, nit }) 
    })
    .then(res => res.json())
    .then(data => { 
        cerrarCargando(); 
        if (data.error) mostrarError(data.error); 
        else { 
            mostrarExito('Contratista creado correctamente'); 
            document.getElementById('nombre').value = ''; 
            document.getElementById('nit').value = ''; 
            cargarContratistas(1); 
            cargarSelectEmpresas();
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error de conexión'); });
};

window.editarContratista = function(contratista) { 
    idEditar = contratista.id; 
    document.getElementById('edit_nombre_contratista').value = contratista.nombre; 
    document.getElementById('edit_nit_contratista').value = contratista.nit; 
    new bootstrap.Modal(document.getElementById('modalEditarContratista')).show(); 
};

window.guardarEdicionContratista = function() {
    const data = { 
        id: idEditar, 
        nombre: document.getElementById('edit_nombre_contratista').value, 
        nit: document.getElementById('edit_nit_contratista').value 
    };
    if (!data.nombre || !data.nit) { mostrarError('Nombre y NIT son obligatorios'); return; }
    
    mostrarCargando('Actualizando...');
    fetch('/orange-proyect/public/contratistas/actualizar', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(data) 
    })
    .then(res => res.json())
    .then(res => { 
        cerrarCargando(); 
        if (res.error) mostrarError(res.error); 
        else { 
            mostrarExito('Contratista actualizado'); 
            cargarContratistas(paginaActual.contratistas); 
            bootstrap.Modal.getInstance(document.getElementById('modalEditarContratista')).hide(); 
            cargarSelectEmpresas();
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error al actualizar'); });
};

window.eliminarContratista = async function(id) {
    if (!await confirmarEliminacion('¿Eliminar este contratista? Se eliminarán TODOS sus conductores y vehículos.', 'Eliminar Contratista')) return;
    mostrarCargando('Eliminando...');
    fetch('/orange-proyect/public/contratistas/eliminar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })
        .then(res => res.json())
        .then(data => { 
            cerrarCargando(); 
            if (data.error) mostrarError(data.error); 
            else { 
                mostrarExito(data.mensaje); 
                cargarContratistas(1); 
                cargarConductores(1); 
                cargarVehiculos(1); 
                cargarSelectEmpresas();
            } 
        })
        .catch(e => { cerrarCargando(); mostrarError('Error al eliminar'); });
};

window.inicializarFiltrosContratistas = function() {

    const buscar =
        document.getElementById('searchContratista');

    if (buscar) {

        buscar.addEventListener('input', function() {

            cargarContratistas(1);

        });

    }

};