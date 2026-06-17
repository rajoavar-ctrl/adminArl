// ============================================
// NOTIFICACIONES
// ============================================
window.mostrarExito = function(mensaje, titulo = "¡Éxito!") {
    Swal.fire({ icon: 'success', title: titulo, text: mensaje, confirmButtonColor: 
        '#f97316', background: '#111827', color: 'white', confirmButtonText: 
        '👍 Entendido', timer: 3000, backdrop: 'rgba(0,0,0,0.5)' });
}

window.mostrarError = function(mensaje, titulo = "¡Error!") {
    Swal.fire({ icon: 'error', title: titulo, text: mensaje, confirmButtonColor: 
        '#f97316', background: '#111827', color: 'white', confirmButtonText: '😓 Entendido' });
}

window.confirmarEliminacion = async function(mensaje, titulo = "¿Estás seguro?") {
    const result = await Swal.fire({ title: titulo, text: mensaje, icon: 
        'question', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: 
        '#6c757d', confirmButtonText: '🗑 Sí, eliminar', cancelButtonText: 
        '❌ Cancelar', background: '#111827', color: 'white', reverseButtons: true });
    return result.isConfirmed;
}

window.mostrarCargando = function(mensaje = "Procesando...") {
    Swal.fire({ title: 'Cargando', text: mensaje, allowOutsideClick: false, 
        allowEscapeKey: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); }, 
        background: '#111827', color: 'white' });
}

window.cerrarCargando = function(){ Swal.close(); }

window.escapeHtml = function(str) { if (!str) return ''; 
    return str.replace(/[&<>]/g, function(m) { if (m === '&') 
        return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') 
            return '&gt;'; return m; }); }


    // ============================================
// PAGINACIÓN
// ============================================
window.actualizarPaginacion = function(tabla, paginaActualNum, totalPaginas) {
    const container = document.getElementById(`paginacion-${tabla}`);
    if (!container) return;
    if (totalPaginas <= 1) { container.innerHTML = ''; return; }
    
    let html = `<ul class="pagination pagination-sm justify-content-center">`;
    
    if (paginaActualNum > 1) {
        html += `<li class="page-item"><button class="page-link bg-dark text-white border-secondary" onclick="cargar${tabla.charAt(0).toUpperCase() + tabla.slice(1)}(${paginaActualNum - 1})">« Anterior</button></li>`;
    }
    
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaActualNum - 1 && i <= paginaActualNum + 1)) {
            html += `<li class="page-item ${i === paginaActualNum ? 'active' : ''}"><button class="page-link ${i === paginaActualNum ? 'bg-warning text-dark border-warning' : 'bg-dark text-white border-secondary'}" onclick="cargar${tabla.charAt(0).toUpperCase() + tabla.slice(1)}(${i})">${i}</button></li>`;
        } else if (i === paginaActualNum - 2 || i === paginaActualNum + 2) {
            html += `<li class="page-item disabled"><span class="page-link bg-dark text-muted border-secondary">...</span></li>`;
        }
    }
    
    if (paginaActualNum < totalPaginas) {
        html += `<li class="page-item"><button class="page-link bg-dark text-white border-secondary" onclick="cargar${tabla.charAt(0).toUpperCase() + tabla.slice(1)}(${paginaActualNum + 1})">Siguiente »</button></li>`;
    }
    
    html += `</ul>`;
    container.innerHTML = html;
}


