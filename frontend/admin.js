const API_URL = '../api/admin.php';

// Mostrar tab
function mostrarTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(`tab-${tab}`).classList.add('active');
    event.target.classList.add('active');
    
    // Cargar datos según tab
    if (tab === 'ventas') cargarVentas();
    if (tab === 'productos') cargarProductos();
    if (tab === 'inventario') cargarInventario();
}

// Cargar estadísticas
async function cargarEstadisticas() {
    try {
        const response = await fetch(`${API_URL}?action=dashboard_stats`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-ventas').textContent = data.total_ventas;
            document.getElementById('ingresos-totales').textContent = `$${parseFloat(data.ingresos_totales || 0).toFixed(2)}`;
            document.getElementById('ventas-hoy').textContent = data.ventas_hoy;
            document.getElementById('bajo-stock').textContent = data.productos_bajo_stock;
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

// Cargar ventas
async function cargarVentas(fechaInicio = '', fechaFin = '') {
    try {
        let url = `${API_URL}?action=ventas`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('ventas-lista');
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7">No hay ventas registradas</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(v => `
                <tr>
                    <td>${v.id_venta}</td>
                    <td>${new Date(v.fecha_venta).toLocaleString()}</td>
                    <td>${v.nombre_cliente || 'Cliente general'}</td>
                    <td>$${parseFloat(v.total).toFixed(2)}</td>
                    <td>${v.metodo_pago}</td>
                    <td><span class="badge ${v.estado === 'completada' ? 'badge-success' : 'badge-warning'}">${v.estado}</span></td>
                    <td>
                        <button class="btn-pdf" onclick="generarPDF(${v.id_venta})">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error cargando ventas:', error);
    }
}

// Cargar productos
async function cargarProductos() {
    try {
        const response = await fetch(`${API_URL}?action=productos`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('productos-lista');
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No hay productos</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(p => `
                <tr>
                    <td>${p.id_producto}</td>
                    <td>${p.nombre_producto}</td>
                    <td>${p.nombre_categoria || '-'}</td>
                    <td>$${parseFloat(p.precio).toFixed(2)}</td>
                    <td class="${p.stock < 10 ? 'stock-bajo' : ''}">${p.stock}</td>
                    <td>
                        <button class="btn-edit" onclick="editarProducto(${p.id_producto})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="eliminarProducto(${p.id_producto})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
    }
}

// Cargar inventario
async function cargarInventario() {
    try {
        const response = await fetch(`${API_URL}?action=inventario`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('inventario-lista');
            const bajoStock = data.data.filter(p => p.stock < 10).length;
            
            document.getElementById('alert-bajo-stock').innerHTML = bajoStock > 0 
                ? `<i class="fas fa-exclamation-triangle"></i> ${bajoStock} productos con stock bajo (<10 unidades)`
                : '';
            
            tbody.innerHTML = data.data.map(p => `
                <tr>
                    <td>${p.id_producto}</td>
                    <td>${p.nombre_producto}</td>
                    <td>
                        <input type="number" id="stock-${p.id_producto}" value="${p.stock}" class="stock-input" min="0">
                    </td>
                    <td>
                        <span class="estado-stock ${p.estado_stock === 'Bajo stock' ? 'warning' : 'success'}">
                            ${p.estado_stock}
                        </span>
                    </td>
                    <td>
                        <button class="btn-update" onclick="actualizarStock(${p.id_producto})">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error cargando inventario:', error);
    }
}

// Actualizar stock
async function actualizarStock(id_producto) {
    const stock = document.getElementById(`stock-${id_producto}`).value;
    
    try {
        const response = await fetch(`${API_URL}?action=actualizar_stock`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_producto, stock })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('Stock actualizado');
            cargarInventario();
            cargarProductos();
        } else {
            mostrarNotificacion(data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar', 'error');
    }
}

// Abrir modal producto
async function abrirModalProducto(id = null) {
    document.getElementById('modal-titulo').textContent = id ? 'Editar Producto' : 'Nuevo Producto';
    document.getElementById('producto-id').value = '';
    document.getElementById('form-producto').reset();
    
    // Cargar categorías
    await cargarCategoriasSelect();
    
    if (id) {
        const response = await fetch(`${API_URL}?action=producto&id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const p = data.data;
            document.getElementById('producto-id').value = p.id_producto;
            document.getElementById('producto-nombre').value = p.nombre_producto;
            document.getElementById('producto-descripcion').value = p.descripcion || '';
            document.getElementById('producto-precio').value = p.precio;
            document.getElementById('producto-stock').value = p.stock;
            document.getElementById('producto-categoria').value = p.id_categoria;
        }
    }
    
    document.getElementById('modal-producto').style.display = 'block';
}

// Cargar categorías en select
async function cargarCategoriasSelect() {
    const response = await fetch(`${API_URL}?action=categorias`);
    const data = await response.json();
    
    if (data.success) {
        const select = document.getElementById('producto-categoria');
        select.innerHTML = '<option value="">Seleccionar...</option>' +
            data.data.map(c => `<option value="${c.id_categoria}">${c.nombre_categoria}</option>`).join('');
    }
}

// Editar producto
function editarProducto(id) {
    abrirModalProducto(id);
}

// Eliminar producto
async function eliminarProducto(id) {
    if (!confirm('¿Eliminar este producto?')) return;
    
    try {
        const response = await fetch(`${API_URL}?action=eliminar_producto&id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('Producto eliminado');
            cargarProductos();
            cargarInventario();
        } else {
            mostrarNotificacion(data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar', 'error');
    }
}

// Guardar producto
document.getElementById('form-producto')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const id = document.getElementById('producto-id').value;
    const producto = {
        nombre_producto: document.getElementById('producto-nombre').value,
        descripcion: document.getElementById('producto-descripcion').value,
        precio: parseFloat(document.getElementById('producto-precio').value),
        id_categoria: parseInt(document.getElementById('producto-categoria').value),
        stock: parseInt(document.getElementById('producto-stock').value)
    };
    
    try {
        let response;
        if (id) {
            producto.id_producto = parseInt(id);
            response = await fetch(`${API_URL}?action=actualizar_producto`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(producto)
            });
        } else {
            response = await fetch(`${API_URL}?action=crear_producto`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(producto)
            });
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion(id ? 'Producto actualizado' : 'Producto creado');
            cerrarModalProducto();
            cargarProductos();
            cargarInventario();
        } else {
            mostrarNotificacion(data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al guardar', 'error');
    }
});

// Filtrar ventas
function filtrarVentas() {
    const inicio = document.getElementById('fecha-inicio').value;
    const fin = document.getElementById('fecha-fin').value;
    cargarVentas(inicio, fin);
}

// Generar PDF
function generarPDF(id_venta) {
    window.open(`../api/generar_factura_pdf.php?id_venta=${id_venta}`, '_blank');
}

// Cerrar modal producto
function cerrarModalProducto() {
    document.getElementById('modal-producto').style.display = 'none';
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'success') {
    alert(mensaje); // Simple, puedes mejorarlo
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
    cargarVentas();
});