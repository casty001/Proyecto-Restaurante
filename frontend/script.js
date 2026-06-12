const API_URL = '../api';
let sesionId = localStorage.getItem('sesion_id') || 'cliente_' + Date.now();
localStorage.setItem('sesion_id', sesionId);

let productos = [];
let carrito = [];

// Cargar productos al iniciar
document.addEventListener('DOMContentLoaded', () => {
    cargarProductos();
    cargarCarrito();
});

// Cargar productos desde API
async function cargarProductos() {
    try {
        const response = await fetch(`${API_URL}/productos.php`);
        productos = await response.json();
        mostrarProductos(productos);
        cargarCategorias();
    } catch (error) {
        document.getElementById('products').innerHTML = '<div class="error">Error al cargar productos</div>';
    }
}

// Mostrar productos en grid
function mostrarProductos(productosMostrar) {
    const grid = document.getElementById('products');
    
    if (!productosMostrar || productosMostrar.length === 0) {
        grid.innerHTML = '<div class="empty">No hay productos disponibles</div>';
        return;
    }
    
    grid.innerHTML = productosMostrar.map(p => `
        <div class="product-card">
            <div class="product-image">
                <i class="fas fa-tattoo"></i>
            </div>
            <div class="product-info">
                <h3 class="product-title">${p.nombre_producto}</h3>
                <span class="product-category">${p.nombre_categoria || 'Sin categoría'}</span>
                <div class="product-price">$${parseFloat(p.precio).toFixed(2)}</div>
                <div class="product-stock">
                    <i class="fas fa-box"></i> Stock: ${p.stock}
                </div>
                <button class="btn-add-cart" onclick="agregarAlCarrito(${p.id_producto})">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            </div>
        </div>
    `).join('');
}

// Cargar categorías para filtros
async function cargarCategorias() {
    try {
        const response = await fetch(`${API_URL}/categorias.php`);
        const categorias = await response.json();
        
        const container = document.getElementById('categorias');
        categorias.forEach(cat => {
            const btn = document.createElement('button');
            btn.textContent = cat.nombre_categoria;
            btn.setAttribute('data-categoria', cat.nombre_categoria);
            btn.onclick = () => filtrarPorCategoria(cat.nombre_categoria);
            container.appendChild(btn);
        });
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
}

// Filtrar por categoría
function filtrarPorCategoria(categoria) {
    document.querySelectorAll('.category-buttons button').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === categoria || (categoria === 'todos' && btn.textContent === 'Todos')) {
            btn.classList.add('active');
        }
    });
    
    if (categoria === 'todos') {
        mostrarProductos(productos);
    } else {
        const filtrados = productos.filter(p => p.nombre_categoria === categoria);
        mostrarProductos(filtrados);
    }
}

// Filtrar por búsqueda
function filtrarProductos() {
    const search = document.getElementById('search').value.toLowerCase();
    const filtrados = productos.filter(p => 
        p.nombre_producto.toLowerCase().includes(search) ||
        (p.descripcion && p.descripcion.toLowerCase().includes(search))
    );
    mostrarProductos(filtrados);
}

// Scroll a productos
function scrollToProducts() {
    document.querySelector('.filters').scrollIntoView({ behavior: 'smooth' });
}

// Agregar al carrito
async function cargarCarrito() {
    try {
        const response = await fetch(`${API_URL}/carrito.php?sesion_id=${sesionId}`);
        const data = await response.json();
        
        console.log('Respuesta del carrito:', data); // Para depurar
        
        if (data.success !== false) {
            carrito = data.items || [];
            document.getElementById('cart-count').textContent = carrito.reduce((sum, i) => sum + i.cantidad, 0);
        } else {
            console.error('Error en respuesta:', data.error);
            carrito = [];
        }
    } catch (error) {
        console.error('Error cargando carrito:', error);
        carrito = [];
    }
}

// Modificar agregarAlCarrito
async function agregarAlCarrito(id_producto) {
    try {
        const response = await fetch(`${API_URL}/carrito.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                sesion_id: sesionId,
                id_producto: id_producto,
                cantidad: 1
            })
        });
        
        const data = await response.json();
        console.log('Respuesta agregar:', data);
        
        if (data.success) {
            await cargarCarrito();
            mostrarNotificacion('Producto agregado al carrito');
        } else {
            mostrarNotificacion(data.message || 'Error al agregar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al agregar al carrito', 'error');
    }
}

// Cargar carrito
// Modificar la función cargarCarrito
async function cargarCarrito() {
    try {
        const response = await fetch(`${API_URL}/carrito.php?sesion_id=${sesionId}`);
        const data = await response.json();
        
        console.log('Respuesta del carrito:', data); // Para depurar
        
        if (data.success !== false) {
            carrito = data.items || [];
            document.getElementById('cart-count').textContent = carrito.reduce((sum, i) => sum + i.cantidad, 0);
        } else {
            console.error('Error en respuesta:', data.error);
            carrito = [];
        }
    } catch (error) {
        console.error('Error cargando carrito:', error);
        carrito = [];
    }
}

// Modificar agregarAlCarrito
async function agregarAlCarrito(id_producto) {
    try {
        const response = await fetch(`${API_URL}/carrito.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                sesion_id: sesionId,
                id_producto: id_producto,
                cantidad: 1
            })
        });
        
        const data = await response.json();
        console.log('Respuesta agregar:', data);
        
        if (data.success) {
            await cargarCarrito();
            mostrarNotificacion('Producto agregado al carrito');
        } else {
            mostrarNotificacion(data.message || 'Error al agregar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al agregar al carrito', 'error');
    }
}

// Ver carrito (modal)
async function verCarrito() {
    await cargarCarrito();
    const modal = document.getElementById('cart-modal');
    const itemsDiv = document.getElementById('cart-items');
    
    if (carrito.length === 0) {
        itemsDiv.innerHTML = '<div class="empty">Carrito vacío</div>';
        document.getElementById('cart-total').textContent = '$0.00';
    } else {
        itemsDiv.innerHTML = carrito.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.nombre_producto}</div>
                    <div>$${parseFloat(item.precio).toFixed(2)} c/u</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="actualizarCantidad(${item.id_producto}, ${item.cantidad - 1})">-</button>
                    <span>${item.cantidad}</span>
                    <button class="quantity-btn" onclick="actualizarCantidad(${item.id_producto}, ${item.cantidad + 1})">+</button>
                    <button class="btn-delete" onclick="eliminarDelCarrito(${item.id_producto})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div>$${(item.precio * item.cantidad).toFixed(2)}</div>
            </div>
        `).join('');
        
        const total = carrito.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
        document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;
    }
    
    modal.style.display = 'block';
}

// Actualizar cantidad
async function actualizarCantidad(id_producto, cantidad) {
    if (cantidad <= 0) {
        await eliminarDelCarrito(id_producto);
    } else {
        try {
            await fetch(`${API_URL}/carrito.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sesion_id: sesionId,
                    id_producto: id_producto,
                    cantidad: cantidad
                })
            });
            await cargarCarrito();
            verCarrito();
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

// Eliminar del carrito
async function eliminarDelCarrito(id_producto) {
    try {
        await fetch(`${API_URL}/carrito.php?sesion_id=${sesionId}&id_producto=${id_producto}`, {
            method: 'DELETE'
        });
        await cargarCarrito();
        verCarrito();
    } catch (error) {
        console.error('Error:', error);
    }
}

// Toggle formulario factura
function toggleFactura() {
    const checkbox = document.getElementById('generar-factura');
    const form = document.getElementById('factura-form');
    form.style.display = checkbox.checked ? 'block' : 'none';
}

// Finalizar compra
async function finalizarCompra() {
    if (carrito.length === 0) {
        mostrarNotificacion('Carrito vacío', 'error');
        return;
    }
    
    const metodo_pago = document.getElementById('metodo-pago').value;
    const generarFactura = document.getElementById('generar-factura').checked;
    
    const items = carrito.map(i => ({
        id_producto: i.id_producto,
        cantidad: i.cantidad,
        precio_unitario: parseFloat(i.precio)
    }));
    
    let body = {
        sesion_id: sesionId,
        metodo_pago: metodo_pago,
        items: items
    };
    
    if (generarFactura) {
        body.generar_factura = true;
        body.cliente = {
            nombre: document.getElementById('cliente-nombre').value,
            identificacion: document.getElementById('cliente-identificacion').value,
            email: document.getElementById('cliente-email').value,
            telefono: document.getElementById('cliente-telefono').value,
            direccion: document.getElementById('cliente-direccion').value
        };
    }
    
    try {
        const response = await fetch(`${API_URL}/ventas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            mostrarNotificacion('Compra realizada con éxito');
            cerrarCarrito();
            await cargarCarrito();
            
            if (generarFactura && data.id_venta) {
                if (confirm('¿Desea descargar la factura?')) {
                    window.open(`${API_URL}/generar_factura_pdf.php?id_venta=${data.id_venta}`, '_blank');
                }
            }
        } else {
            mostrarNotificacion(data.message || 'Error en la compra', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la compra', 'error');
    }
}

// Cerrar modal carrito
function cerrarCarrito() {
    document.getElementById('cart-modal').style.display = 'none';
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'success') {
    const notif = document.getElementById('notification');
    notif.textContent = mensaje;
    notif.style.background = tipo === 'error' ? '#dc3545' : '#28a745';
    notif.style.display = 'block';
    setTimeout(() => {
        notif.style.display = 'none';
    }, 3000);
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('cart-modal');
    if (event.target === modal) {
        cerrarCarrito();
    }
}