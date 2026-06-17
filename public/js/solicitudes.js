// ============================================
// SOLICITUDES DE CONTRATISTAS
// ============================================

window.cargarSolicitudes = function() {
    fetch('/orange-proyect/public/contratistas/pendientes')
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector('#tablaSolicitudes');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay solicitudes pendientes</td></tr>';
            document.querySelector('#totalSolicitudes').textContent = '0';
            return;
        }

        let html = '';
        for (let i = 0; i < data.length; i++) {
            const s = data[i];
            html += '<tr>';
            html += '<td>' + s.id + '</td>';
            html += '<td><strong>' + escapeHtml(s.nombre) + '</strong></td>';
            html += '<td>' + escapeHtml(s.nit) + '</td>';
            html += '<td>' + escapeHtml(s.direccion || '-') + '</td>';
            html += '<td>' + escapeHtml(s.telefono || '-') + '</td>';
            html += '<td>' + escapeHtml(s.email) + '</td>';
            html += '<td>' + (s.creado_en ? new Date(s.creado_en).toLocaleDateString() : '-') + '</td>';
            html += '<td class="text-center">';
            html += '<button class="btn btn-sm btn-success me-1" onclick="aprobarContratista(' + s.id + ')">✅ Aprobar</button>';
            html += '<button class="btn btn-sm btn-danger" onclick="rechazarContratista(' + s.id + ')">❌ Rechazar</button>';
            html += '</td>';
            html += '</tr>';
        }
        tbody.innerHTML = html;
        document.querySelector('#totalSolicitudes').textContent = data.length;
    })
    .catch(error => {
        console.error('Error:', error);
        const tbody = document.querySelector('#tablaSolicitudes');
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center">Error al cargar solicitudes</td></tr>';
    });
};

window.aprobarContratista = async function(id) {
    const result = await Swal.fire({
        title: 'Aprobar Contratista',
        text: '¿Aprobar este contratista? Podrá acceder al sistema.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '✅ Sí, aprobar',
        cancelButtonText: '❌ Cancelar',
        background: '#111827',
        color: 'white',
        reverseButtons: true
    });
    
    if (!result.isConfirmed) return;
    
    mostrarCargando('Aprobando...');
    
    fetch('/orange-proyect/public/contratistas/aprobar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        cerrarCargando();
        if (data.error) {
            mostrarError(data.error);
        } else {
            mostrarExito('Contratista aprobado correctamente');
            cargarSolicitudes();
            cargarContratistas();
        }
    })
    .catch(error => {
        cerrarCargando();
        mostrarError('Error al aprobar');
    });
};

window.rechazarContratista = async function(id) {
    const result = await Swal.fire({
        title: 'Rechazar Contratista',
        text: '¿Rechazar este contratista? No podrá acceder al sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '❌ Sí, rechazar',
        cancelButtonText: 'Cancelar',
        background: '#111827',
        color: 'white',
        reverseButtons: true
    });
    
    if (!result.isConfirmed) return;
    
    mostrarCargando('Rechazando...');
    
    fetch('/orange-proyect/public/contratistas/rechazar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        cerrarCargando();
        if (data.error) {
            mostrarError(data.error);
        } else {
            mostrarExito('Contratista rechazado');
            cargarSolicitudes();
        }
    })
    .catch(error => {
        cerrarCargando();
        mostrarError('Error al rechazar');
    });
};