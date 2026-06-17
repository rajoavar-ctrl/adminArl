// ============================================
// VEHÍCULOS
// ============================================
window.cargarVehiculos = function(page = 1) {
    const search = document.getElementById('searchVehiculo')?.value || '';
    const empresa_id = document.getElementById('empresaFiltroVehiculo')?.value || '';
    const conductor_id = document.getElementById('conductorFiltroVehiculo')?.value || '';
    
    let url = `/orange-proyect/public/vehiculos?page=${page}&limit=5`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (empresa_id) url += `&empresa_id=${empresa_id}`;
    if (conductor_id) url += `&conductor_id=${conductor_id}`;
    
    fetch(url)
    .then(res => res.json())
    .then(res => {
        const tbody = document.querySelector('#tablaVehiculos');
        if (!tbody) return;
        
        const data = res.data || [];
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay vehículos registrados</td></tr>';
            document.querySelector('#totalVehiculos').textContent = '0';
            return;
        }
        
        let html = '';
        for (let i = 0; i < data.length; i++) {
            const v = data[i];
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(v.placa) + '</strong></td>';
            html += '<td>' + escapeHtml(v.marca) + '</td>';
            html += '<td>' + escapeHtml(v.modelo) + '</td>';
            html += '<td>' + escapeHtml(v.empresa) + '</td>';
            html += '<td>' + escapeHtml(v.conductor_nombre || 'Sin asignar') + '</td>';
            html += '<td class="text-center">';
            html += '<button class="btn btn-sm btn-warning" onclick=\'editarVehiculo(' + JSON.stringify(v) + ')\'>✏️ Editar</button>';
            html += '<button class="btn btn-sm btn-danger" onclick="eliminarVehiculo(' + v.id + ')">🗑 Eliminar</button>';
            html += '</td>';
            html += '</tr>';
        }
        tbody.innerHTML = html;
        
        document.querySelector('#totalVehiculos').textContent = res.total || data.length;
    })
    .catch(error => {
        console.error('Error:', error);
        const tbody = document.querySelector('#tablaVehiculos');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center">Error al cargar datos</td></tr>';
    });
};

window.aplicarFiltrosVehiculos = function() { cargarVehiculos(1); };

window.crearVehiculo = function() {
    const placa = document.getElementById('placa')?.value;
    const marca = document.getElementById('marca')?.value;
    const modelo = document.getElementById('modelo')?.value;
    const contratista_id = document.getElementById('contratista')?.value;
    const conductor_id = document.getElementById('conductor')?.value;
    
    if (!placa || !marca || !modelo || !contratista_id) { 
        mostrarError('Placa, marca, modelo y empresa son obligatorios'); 
        return; 
    }
    
    mostrarCargando('Registrando vehículo...');
    fetch('/orange-proyect/public/vehiculos', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ 
            placa: placa.toUpperCase(), 
            marca, 
            modelo, 
            contratista_id: parseInt(contratista_id), 
            conductor_id: conductor_id || null 
        }) 
    })
    .then(res => res.json())
    .then(data => { 
        cerrarCargando(); 
        if (data.error) mostrarError(data.error); 
        else { 
            mostrarExito('Vehículo creado correctamente'); 
            document.getElementById('placa').value = ''; 
            document.getElementById('marca').value = ''; 
            document.getElementById('modelo').value = ''; 
            document.getElementById('contratista').value = ''; 
            document.getElementById('conductor').innerHTML = '<option value="">Seleccione un conductor (opcional)</option>'; 
            cargarVehiculos(1); 
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error de conexión'); });
};

window.editarVehiculo = function(vehiculo) {
    idEditar = vehiculo.id;
    document.getElementById('edit_placa').value = vehiculo.placa;
    document.getElementById('edit_marca').value = vehiculo.marca;
    document.getElementById('edit_modelo').value = vehiculo.modelo;
    
    fetch('/orange-proyect/public/contratistas?limit=999')
        .then(res => res.json())
        .then(res => {
            const data = res.data || res;
            const select = document.getElementById('edit_contratista');
            if (select) {
                select.innerHTML = data.map(c => `<option value="${c.id}" ${c.id == vehiculo.contratista_id ? 'selected' : ''}>${escapeHtml(c.nombre)}</option>`).join('');
            }
        });
    
    fetch(`/orange-proyect/public/conductores/por-contratista?contratista_id=${vehiculo.contratista_id}`)
        .then(res => res.json())
        .then(conductores => {
            const select = document.getElementById('edit_conductor');
            if (select) {
                select.innerHTML = '<option value="">Sin conductor</option>' + conductores.map(c => `<option value="${c.id}" ${c.id == vehiculo.conductor_id ? 'selected' : ''}>${escapeHtml(c.nombre)}</option>`).join('');
            }
        });
    
    new bootstrap.Modal(document.getElementById('modalEditarVehiculo')).show();
};

window.guardarEdicionVehiculo = function() {
    const data = { 
        id: idEditar, 
        placa: document.getElementById('edit_placa').value.toUpperCase(), 
        marca: document.getElementById('edit_marca').value, 
        modelo: document.getElementById('edit_modelo').value, 
        contratista_id: document.getElementById('edit_contratista').value, 
        conductor_id: document.getElementById('edit_conductor').value || null 
    };
    if (!data.placa || !data.marca || !data.modelo || !data.contratista_id) { 
        mostrarError('Todos los campos son obligatorios'); 
        return; 
    }
    
    mostrarCargando('Actualizando...');
    fetch('/orange-proyect/public/vehiculos/actualizar', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(data) 
    })
    .then(res => res.json())
    .then(res => { 
        cerrarCargando(); 
        if (res.error) mostrarError(res.error); 
        else { 
            mostrarExito('Vehículo actualizado'); 
            cargarVehiculos(paginaActual.vehiculos); 
            bootstrap.Modal.getInstance(document.getElementById('modalEditarVehiculo')).hide(); 
        } 
    })
    .catch(e => { cerrarCargando(); mostrarError('Error al actualizar'); });
};

window.eliminarVehiculo = async function(id) {
    if (!await confirmarEliminacion('¿Eliminar este vehículo?', 'Eliminar Vehículo')) return;
    mostrarCargando('Eliminando...');
    fetch('/orange-proyect/public/vehiculos/eliminar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })
        .then(res => res.json())
        .then(data => { 
            cerrarCargando(); 
            if (data.error) mostrarError(data.error); 
            else { 
                mostrarExito('Vehículo eliminado'); 
                cargarVehiculos(1); 
            } 
        })
        .catch(e => { cerrarCargando(); mostrarError('Error al eliminar'); });
};

//FILTROS
window.activarFiltroConductores = function() {

    const selectContratista =
        document.querySelector('#contratista');

    if (!selectContratista) {
        console.log('No existe #contratista');
        return;
    }

    selectContratista.onchange = function() {

        const selectConductor =
            document.querySelector('#conductor');

        if (!this.value) {

            selectConductor.innerHTML =
                '<option value="">Seleccione un conductor</option>';

            return;
        }

        fetch(
            `/orange-proyect/public/conductores/por-contratista?contratista_id=${this.value}`
        )
        .then(res => res.json())
        .then(data => {

            selectConductor.innerHTML =
                '<option value="">Seleccione un conductor</option>' +
                data.map(c =>
                    `<option value="${c.id}">
                        ${escapeHtml(c.nombre)}
                    </option>`
                ).join('');

        })
        .catch(console.error);

    };

};

window.cargarSelectConductoresFiltro = function() {
    fetch('/orange-proyect/public/conductores?limit=999')
        .then(res => res.json())
        .then(res => {
            const data = res.data || res;
            const select = document.querySelector('#conductorFiltroVehiculo');
            if (select) {
                select.innerHTML = '<option value="">Todos los conductores</option>' + data.map(x => `<option value="${x.id}">${escapeHtml(x.nombre)}</option>`).join('');
            }
        })
        .catch(e => console.error(e));
};

window.inicializarFiltrosVehiculos = function() {

    const buscar =
        document.getElementById('searchVehiculo');

    const empresa =
        document.getElementById('empresaFiltroVehiculo');

    const conductor =
        document.getElementById('conductorFiltroVehiculo');

    if (buscar) {

        buscar.addEventListener('input', function() {

            cargarVehiculos(1);

        });

    }

    if (empresa) {

        empresa.addEventListener('change', () => {

            cargarVehiculos(1);

        });

    }

    if (conductor) {

        conductor.addEventListener('change', () => {

            cargarVehiculos(1);

        });

    }

};

window.filtrarConductoresPorEmpresa = function() {

    const empresa =
        document.querySelector('#empresaFiltroVehiculo');

    const conductor =
        document.querySelector('#conductorFiltroVehiculo');

    if (!empresa || !conductor) return;

    empresa.addEventListener('change', async function() {

        conductor.innerHTML =
            '<option value="">Todos los conductores</option>';

        if (!this.value) {

            cargarSelectConductoresFiltro();
            return;
        }

        try {

            const res = await fetch(
                `/orange-proyect/public/conductores/por-contratista?contratista_id=${this.value}`
            );

            const data = await res.json();

            conductor.innerHTML =
                '<option value="">Todos los conductores</option>' +
                data.map(c =>
                    `<option value="${c.id}">
                        ${escapeHtml(c.nombre)}
                    </option>`
                ).join('');

        } catch(err) {

            console.error(err);

        }

    });

};