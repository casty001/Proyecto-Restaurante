<?php
require_once '../config/database.php';

// Obtener ID de venta
$id_venta = isset($_GET['id_venta']) ? $_GET['id_venta'] : null;

if (!$id_venta) {
    die("ID de venta no proporcionado");
}

try {
    // Obtener datos de la venta
    $stmt = $conexion->prepare("
        SELECT v.*, 
               v.nombre_cliente,
               v.identificacion_cliente,
               v.email_cliente,
               v.telefono_cliente,
               v.numero_factura
        FROM venta v
        WHERE v.id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch();

    if (!$venta) {
        die("Venta no encontrada");
    }

    // Obtener productos
    $stmt = $conexion->prepare("
        SELECT dv.*, p.nombre_producto
        FROM detalle_venta dv
        JOIN producto p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $productos = $stmt->fetchAll();

    if (count($productos) == 0) {
        die("No hay productos en esta venta");
    }

    // Calcular total
    $total = 0;
    foreach($productos as $producto) {
        $total += $producto['cantidad'] * $producto['precio_unitario'];
    }

    // Configurar cabeceras para mostrar como HTML
    header('Content-Type: text/html; charset=utf-8');
    
    // Generar HTML de la factura
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Factura FOM Supply Tattoo</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                background: #f0f0f0;
                padding: 20px;
            }
            .factura-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .factura-header {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .factura-header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            .factura-header h1 span {
                color: #ff6b6b;
            }
            .factura-header p {
                margin: 5px 0;
                font-size: 12px;
                opacity: 0.9;
            }
            .factura-body {
                padding: 30px;
            }
            .titulo-factura {
                text-align: center;
                font-size: 24px;
                font-weight: bold;
                color: #ff6b6b;
                margin-bottom: 30px;
                padding-bottom: 10px;
                border-bottom: 2px solid #ff6b6b;
            }
            .info-section {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .info-section h3 {
                color: #1a1a2e;
                margin-bottom: 10px;
                font-size: 14px;
            }
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .info-item {
                font-size: 12px;
            }
            .info-item strong {
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th {
                background: #1a1a2e;
                color: white;
                padding: 12px;
                text-align: left;
                font-size: 12px;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
                font-size: 12px;
            }
            .text-right {
                text-align: right;
            }
            .totales {
                margin-top: 20px;
                text-align: right;
                padding-top: 10px;
                border-top: 2px solid #ddd;
            }
            .total-final {
                font-size: 18px;
                font-weight: bold;
                color: #ff6b6b;
            }
            .factura-footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                font-size: 11px;
                color: #666;
            }
            .btn-imprimir {
                display: block;
                width: 200px;
                margin: 20px auto;
                padding: 12px;
                background: #ff6b6b;
                color: white;
                text-align: center;
                text-decoration: none;
                border-radius: 5px;
                cursor: pointer;
                border: none;
                font-size: 14px;
            }
            .btn-imprimir:hover {
                background: #ff5252;
            }
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                .btn-imprimir {
                    display: none;
                }
                .factura-container {
                    box-shadow: none;
                    border-radius: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="factura-container">
            <div class="factura-header">
                <h1>FOM <span>Supply Tattoo</span></h1>
                <p>Calle Principal #123 - Ciudad</p>
                <p>Tel: 300 123 4567 | Email: info@fomsupply.com</p>
                <p>NIT: 123456789-0</p>
            </div>
            
            <div class="factura-body">
                <div class="titulo-factura">
                    FACTURA ELECTRÓNICA
                </div>
                
                <div class="info-section">
                    <h3>DATOS DE LA FACTURA</h3>
                    <div class="info-grid">
                        <div class="info-item"><strong>Factura No:</strong> <?php echo $venta['numero_factura'] ?? 'VEN-' . str_pad($venta['id_venta'], 8, '0', STR_PAD_LEFT); ?></div>
                        <div class="info-item"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></div>
                        <div class="info-item"><strong>Método Pago:</strong> <?php echo $venta['metodo_pago']; ?></div>
                        <div class="info-item"><strong>Estado:</strong> <?php echo $venta['estado']; ?></div>
                    </div>
                </div>
                
                <?php if (!empty($venta['nombre_cliente'])): ?>
                <div class="info-section">
                    <h3>DATOS DEL CLIENTE</h3>
                    <div class="info-grid">
                        <div class="info-item"><strong>Nombre:</strong> <?php echo htmlspecialchars($venta['nombre_cliente']); ?></div>
                        <?php if (!empty($venta['identificacion_cliente'])): ?>
                        <div class="info-item"><strong>Identificación:</strong> <?php echo htmlspecialchars($venta['identificacion_cliente']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($venta['email_cliente'])): ?>
                        <div class="info-item"><strong>Email:</strong> <?php echo htmlspecialchars($venta['email_cliente']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($venta['telefono_cliente'])): ?>
                        <div class="info-item"><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['telefono_cliente']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Cant.</th>
                            <th>Descripción</th>
                            <th class="text-right">Precio</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $producto): 
                            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];
                        ?>
                        <tr>
                            <td><?php echo $producto['cantidad']; ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                            <td class="text-right">$<?php echo number_format($producto['precio_unitario'], 2); ?></td>
                            <td class="text-right">$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="totales">
                    <p><strong>SUBTOTAL:</strong> $<?php echo number_format($total, 2); ?></p>
                    <p><strong>IVA (19%):</strong> $<?php echo number_format($total * 0.19, 2); ?></p>
                    <p class="total-final"><strong>TOTAL:</strong> $<?php echo number_format($total * 1.19, 2); ?></p>
                </div>
            </div>
            
            <div class="factura-footer">
                <p>¡Gracias por tu compra!</p>
                <p>Esta factura es un comprobante válido para soporte contable</p>
                <p>www.fomsupplytattoo.com</p>
            </div>
        </div>
        
        <button class="btn-imprimir" onclick="window.print();">
            🖨️ Imprimir / Guardar PDF
        </button>
        
        <script>
            // Auto-imprimir (opcional - descomentar si quieres que imprima automáticamente)
            // setTimeout(function() { window.print(); }, 500);
        </script>
    </body>
    </html>
    <?php

} catch(Exception $e) {
    echo "Error al generar la factura: " . $e->getMessage();
}
?>