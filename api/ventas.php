<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Obtener todas las ventas
        $stmt = $conexion->query("
            SELECT v.*, 
                   COUNT(dv.id_detalle_venta) as total_productos
            FROM venta v
            LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
            GROUP BY v.id_venta
            ORDER BY v.fecha_venta DESC
        ");
        
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
        
        echo json_encode($ventas);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $conexion->beginTransaction();
            
            // Calcular total
            $total = 0;
            foreach($data['items'] as $item) {
                $total += $item['precio_unitario'] * $item['cantidad'];
            }
            
            // Crear venta
            $stmt = $conexion->prepare("
                INSERT INTO venta (total, metodo_pago, estado) 
                VALUES (?, ?, 'completada')
            ");
            $stmt->execute([$total, $data['metodo_pago']]);
            $id_venta = $conexion->lastInsertId();
            
            // Crear detalles de venta y actualizar stock
            foreach($data['items'] as $item) {
                $stmt = $conexion->prepare("
                    INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id_venta,
                    $item['id_producto'],
                    $item['cantidad'],
                    $item['precio_unitario']
                ]);
                
                // Actualizar stock
                $stmt = $conexion->prepare("
                    UPDATE producto 
                    SET stock = stock - ? 
                    WHERE id_producto = ?
                ");
                $stmt->execute([$item['cantidad'], $item['id_producto']]);
            }
            
            // Vaciar carrito
            $stmt = $conexion->prepare("
                DELETE dc FROM detalle_carrito dc
                JOIN carrito c ON dc.id_carrito = c.id_carrito
                WHERE c.sesion_id = ?
            ");
            $stmt->execute([$data['sesion_id']]);
            
            $conexion->commit();
            
            echo json_encode([
                "message" => "Venta completada exitosamente",
                "id_venta" => $id_venta,
                "total" => $total
            ]);
            
        } catch(Exception $e) {
            $conexion->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Error al procesar la venta: " . $e->getMessage()]);
        }
        break;
}
?>