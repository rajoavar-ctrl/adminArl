// ============================================
// CONDUCTORES
// ============================================
window.cargarConductores = function(page = 1) {
    const search = document.getElementById('searchConductor')?.value || '';
    const empresa_id = document.getElementById('empresaFiltro')?.value || '';
    
    let url = `/orange-proyect/public/conductores?page=${page}&limit=5`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (empresa_id) url += `&empresa_id=${empresa_id}`;
    
    fetch(url)
    .then(res => res.json())
    .then(res => {
        const tbody = document.querySelector('#tablaConductores');
        if (!tbody) return;
        
        const data = res.data || [];
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay conductores registrados</td></tr>';
            document.querySelector('#totalConductores').textContent = '0';
            return;
        }
        
        let html = '';
        for (let i = 0; i < data.length; i++) {
            const c = data[i];
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(c.nombre) + '</strong></td>';
            html += '<td>' + escapeHtml(c.cedula) + '</td>';
            html += '<td>' + escapeHtml(c.email || '') + '</td>';
            html += '<td>' + escapeHtml(c.empresa) + '</td>';
            html += '<td class="text-center">';
            html += '<button class="btn btn-sm btn-warning" onclick=\'editarConductor(' + JSON.stringify(c) + ')\'>✏️ Editar</button>';
            html += '<button class="btn btn-sm btn-danger" onclick="eliminarConductor(' + c.id + ')">🗑 Eliminar</button>';
            html += '</td>';
            html += '</tr>';
        }
        tbody.innerHTML = html;
        
        document.querySelector('#totalConductores').textContent = res.total || data.length;
    })
    .catch(error => {
        console.error('Error:', error);
        const tbody = document.querySelector('#tablaConductores');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center">Error al cargar datos</td></tr>';
    });
};

window.aplicarFiltrosConductores = function() { cargarConductores(1); };

window.crearConductor = function() {
    const nombre = document.getElementById('nombre')?.value;
    const cedula = document.getElementById('cedula')?.value;
    const email = document.getElementById('email')?.value;
    const password = document.getElementById('password')?.value;
    const contratista_id = document.getElementById('contratista')?.value;
    
    if (!nombre || !cedula || !email || !password || !contratista_id) { 
        mostrarError('Todos los campos son obligatorios'); 
        return; 
    }
    
    mostrarCargando('Creando conductor...');
    fetch('/orange-proyect/public/conductores', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ nombre, cedula, email, password, contratista_id: parseInt(contratista_id) }) 
    })
    .then(res => res.json())
    .then(data => { 
        cerrarCargando(); 
        if (data.error) mostrarError(data.error); 
        else { 
            mostrarExito('Conductor creado correctamente'); 
            document.getElementById('nombre').value = ''; 
            document.getElementById('cedula').value = ''; 
            document.getElementById('email').value = ''; 
            document.getElementById('password').value = ''; 
            document.getElementById('contratista').value = ''; 
            cargarConductores(1); 
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error de conexión'); });
};

window.editarConductor = function(conductor) {
    idEditar = conductor.id;
    document.getElementById('edit_nombre').value = conductor.nombre;
    document.getElementById('edit_cedula').value = conductor.cedula;
    document.getElementById('edit_email').value = conductor.email || '';
    
    fetch('/orange-proyect/public/contratistas?limit=999')
        .then(res => res.json())
        .then(res => {
            const data = res.data || res;
            const select = document.getElementById('edit_contratista_conductor');
            if (select) {
                select.innerHTML = data.map(c => `<option value="${c.id}" ${c.id == conductor.contratista_id ? 'selected' : ''}>${escapeHtml(c.nombre)}</option>`).join('');
            }
        });
    
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
};

window.guardarEdicion = function() {
    const data = { 
        id: idEditar, 
        nombre: document.getElementById('edit_nombre').value, 
        cedula: document.getElementById('edit_cedula').value, 
        email: document.getElementById('edit_email').value, 
        contratista_id: document.getElementById('edit_contratista_conductor')?.value 
    };
    if (!data.nombre || !data.cedula) { mostrarError('Nombre y cédula son obligatorios'); return; }
    
    mostrarCargando('Actualizando...');
    fetch('/orange-proyect/public/conductores/actualizar', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(data) 
    })
    .then(res => res.json())
    .then(res => { 
        cerrarCargando(); 
        if (res.error) mostrarError(res.error); 
        else { 
            mostrarExito('Conductor actualizado'); 
            cargarConductores(paginaActual.conductores); 
            bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide(); 
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error al actualizar'); });
};

window.eliminarConductor = async function(id) {
    if (!await confirmarEliminacion('¿Eliminar este conductor?', 'Eliminar Conductor')) return;
    mostrarCargando('Eliminando...');
    fetch('/orange-proyect/public/conductores/eliminar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })
        .then(res => res.json())
        .then(data => { 
            cerrarCargando(); 
            if (data.error) mostrarError(data.error); 
            else { 
                mostrarExito('Conductor eliminado'); 
                cargarConductores(1); 
            } 
        })
        .catch(e => { cerrarCargando(); mostrarError('Error al eliminar'); });
};

window.inicializarFiltrosConductores = function() {

    const buscar =
        document.getElementById('searchConductor');

    const empresa =
        document.getElementById('empresaFiltro');

    if (buscar) {

        buscar.addEventListener('input', function() {

            cargarConductores(1);

        });

    }

    if (empresa) {

        empresa.addEventListener('change', function() {

            cargarConductores(1);

        });

    }

};