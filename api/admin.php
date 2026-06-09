<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Autenticación básica (puedes mejorarla con sesiones)
function verificarAdmin() {
    // Por ahora, autenticación simple
    // Puedes implementar con $_SESSION o JWT después
    return true;
}

switch($method) {
    case 'GET':
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch($action) {
            case 'dashboard_stats':
                // Obtener estadísticas del dashboard
                try {
                    // Total ventas
                    $stmt = $conexion->query("SELECT COUNT(*) as total FROM venta");
                    $total_ventas = $stmt->fetch();
                    
                    // Ingresos totales
                    $stmt = $conexion->query("SELECT SUM(total) as ingresos FROM venta");
                    $ingresos = $stmt->fetch();
                    
                    // Productos con bajo stock
                    $stmt = $conexion->query("SELECT COUNT(*) as bajo_stock FROM producto WHERE stock < 10");
                    $bajo_stock = $stmt->fetch();
                    
                    // Ventas hoy
                    $stmt = $conexion->query("SELECT COUNT(*) as hoy FROM venta WHERE DATE(fecha_venta) = CURDATE()");
                    $ventas_hoy = $stmt->fetch();
                    
                    echo json_encode([
                        "success" => true,
                        "total_ventas" => $total_ventas['total'],
                        "ingresos_totales" => $ingresos['ingresos'] ?? 0,
                        "productos_bajo_stock" => $bajo_stock['bajo_stock'],
                        "ventas_hoy" => $ventas_hoy['hoy']
                    ]);
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            case 'ventas':
                // Obtener todas las ventas con filtros
                $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
                $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
                
                $sql = "
                    SELECT v.*, 
                           COUNT(dv.id_detalle_venta) as total_productos,
                           v.numero_factura,
                           v.nombre_cliente
                    FROM venta v
                    LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                ";
                
                if ($fecha_inicio && $fecha_fin) {
                    $sql .= " WHERE DATE(v.fecha_venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
                }
                
                $sql .= " GROUP BY v.id_venta ORDER BY v.fecha_venta DESC";
                
                $stmt = $conexion->query($sql);
                $ventas = $stmt->fetchAll();
                
                // Obtener detalles de cada venta
                foreach($ventas as &$venta) {
                    $stmt = $conexion->prepare("
                        SELECT dv.*, p.nombre_producto
                        FROM detalle_venta dv
                        JOIN producto p ON dv.id_producto = p.id_producto
                        WHERE dv.id_venta = ?
                    ");
                    $stmt->execute([$venta['id_venta']]);
                    $venta['detalles'] = $stmt->fetchAll();
                }
                
                echo json_encode(["success" => true, "data" => $ventas]);
                break;
                
            case 'productos':
                // Obtener todos los productos
                $stmt = $conexion->query("
                    SELECT p.*, c.nombre_categoria 
                    FROM producto p
                    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                    ORDER BY p.id_producto DESC
                ");
                $productos = $stmt->fetchAll();
                echo json_encode(["success" => true, "data" => $productos]);
                break;
                
            case 'categorias':
                // Obtener todas las categorías
                $stmt = $conexion->query("SELECT * FROM categoria ORDER BY nombre_categoria");
                $categorias = $stmt->fetchAll();
                echo json_encode(["success" => true, "data" => $categorias]);
                break;
                
            case 'inventario':
                // Obtener inventario con alertas
                $stmt = $conexion->query("
                    SELECT p.*, c.nombre_categoria,
                           CASE 
                               WHEN p.stock = 0 THEN 'Agotado'
                               WHEN p.stock < 10 THEN 'Bajo stock'
                               ELSE 'Normal'
                           END as estado_stock
                    FROM producto p
                    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                    ORDER BY p.stock ASC
                ");
                $inventario = $stmt->fetchAll();
                echo json_encode(["success" => true, "data" => $inventario]);
                break;
                
            case 'producto':
                // Obtener un producto específico
                $id = isset($_GET['id']) ? $_GET['id'] : null;
                if ($id) {
                    $stmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto = ?");
                    $stmt->execute([$id]);
                    $producto = $stmt->fetch();
                    echo json_encode(["success" => true, "data" => $producto]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "ID de producto requerido"]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["error" => "Acción no válida"]);
        }
        break;
        
    case 'POST':
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'crear_producto':
                // Crear nuevo producto
                try {
                    $stmt = $conexion->prepare("
                        INSERT INTO producto (nombre_producto, descripcion, precio, id_categoria, stock) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $result = $stmt->execute([
                        $data['nombre_producto'],
                        $data['descripcion'],
                        $data['precio'],
                        $data['id_categoria'],
                        $data['stock']
                    ]);
                    
                    if ($result) {
                        $id_producto = $conexion->lastInsertId();
                        echo json_encode([
                            "success" => true,
                            "message" => "Producto creado exitosamente",
                            "id_producto" => $id_producto
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al crear producto"]);
                    }
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            case 'actualizar_stock':
                // Actualizar stock de producto
                try {
                    $stmt = $conexion->prepare("
                        UPDATE producto 
                        SET stock = ? 
                        WHERE id_producto = ?
                    ");
                    
                    $result = $stmt->execute([$data['stock'], $data['id_producto']]);
                    
                    if ($result) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Stock actualizado exitosamente"
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al actualizar stock"]);
                    }
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["error" => "Acción no válida"]);
        }
        break;
        
    case 'PUT':
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'actualizar_producto':
                // Actualizar producto completo
                try {
                    $stmt = $conexion->prepare("
                        UPDATE producto 
                        SET nombre_producto = ?, 
                            descripcion = ?, 
                            precio = ?, 
                            id_categoria = ?, 
                            stock = ?
                        WHERE id_producto = ?
                    ");
                    
                    $result = $stmt->execute([
                        $data['nombre_producto'],
                        $data['descripcion'],
                        $data['precio'],
                        $data['id_categoria'],
                        $data['stock'],
                        $data['id_producto']
                    ]);
                    
                    if ($result) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Producto actualizado exitosamente"
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al actualizar producto"]);
                    }
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            case 'actualizar_venta':
                // Actualizar estado de venta
                try {
                    $stmt = $conexion->prepare("
                        UPDATE venta 
                        SET estado = ? 
                        WHERE id_venta = ?
                    ");
                    
                    $result = $stmt->execute([$data['estado'], $data['id_venta']]);
                    
                    if ($result) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Venta actualizada exitosamente"
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al actualizar venta"]);
                    }
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["error" => "Acción no válida"]);
        }
        break;
        
    case 'DELETE':
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        switch($action) {
            case 'eliminar_producto':
                // Eliminar producto
                if (!$id) {
                    http_response_code(400);
                    echo json_encode(["error" => "ID de producto requerido"]);
                    break;
                }
                
                try {
                    // Verificar si el producto tiene ventas
                    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_venta WHERE id_producto = ?");
                    $stmt->execute([$id]);
                    $ventas = $stmt->fetch();
                    
                    if ($ventas['total'] > 0) {
                        http_response_code(400);
                        echo json_encode(["error" => "No se puede eliminar el producto porque tiene ventas asociadas"]);
                        break;
                    }
                    
                    $stmt = $conexion->prepare("DELETE FROM producto WHERE id_producto = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Producto eliminado exitosamente"
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al eliminar producto"]);
                    }
                } catch(Exception $e) {
                    http_response_code(500);
                    echo json_encode(["error" => $e->getMessage()]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["error" => "Acción no válida"]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
}
?>