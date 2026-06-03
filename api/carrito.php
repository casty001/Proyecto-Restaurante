<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$sesion_id = isset($_GET['sesion_id']) ? $_GET['sesion_id'] : null;

switch($method) {
    case 'GET':
        if($sesion_id) {
            // Obtener carrito
            $stmt = $conexion->prepare("SELECT id_carrito FROM carrito WHERE sesion_id = ?");
            $stmt->execute([$sesion_id]);
            $carrito = $stmt->fetch();
            
            if(!$carrito) {
                // Crear carrito si no existe
                $stmt = $conexion->prepare("INSERT INTO carrito (sesion_id) VALUES (?)");
                $stmt->execute([$sesion_id]);
                $carrito_id = $conexion->lastInsertId();
            } else {
                $carrito_id = $carrito['id_carrito'];
            }
            
            // Obtener items del carrito
            $stmt = $conexion->prepare("
                SELECT dc.*, p.nombre_producto, p.precio, p.stock
                FROM detalle_carrito dc
                JOIN producto p ON dc.id_producto = p.id_producto
                WHERE dc.id_carrito = ?
            ");
            $stmt->execute([$carrito_id]);
            $items = $stmt->fetchAll();
            
            // Calcular total
            $total = 0;
            foreach($items as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            
            echo json_encode([
                "id_carrito" => $carrito_id,
                "items" => $items,
                "total" => $total
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'add') {
            // Agregar al carrito
            $stmt = $conexion->prepare("SELECT id_carrito FROM carrito WHERE sesion_id = ?");
            $stmt->execute([$data['sesion_id']]);
            $carrito = $stmt->fetch();
            
            if(!$carrito) {
                $stmt = $conexion->prepare("INSERT INTO carrito (sesion_id) VALUES (?)");
                $stmt->execute([$data['sesion_id']]);
                $carrito_id = $conexion->lastInsertId();
            } else {
                $carrito_id = $carrito['id_carrito'];
            }
            
            // Verificar si el producto ya existe en el carrito
            $stmt = $conexion->prepare("
                SELECT * FROM detalle_carrito 
                WHERE id_carrito = ? AND id_producto = ?
            ");
            $stmt->execute([$carrito_id, $data['id_producto']]);
            $existente = $stmt->fetch();
            
            if($existente) {
                $stmt = $conexion->prepare("
                    UPDATE detalle_carrito 
                    SET cantidad = cantidad + ? 
                    WHERE id_detalle = ?
                ");
                $result = $stmt->execute([$data['cantidad'], $existente['id_detalle']]);
            } else {
                $stmt = $conexion->prepare("
                    INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad) 
                    VALUES (?, ?, ?)
                ");
                $result = $stmt->execute([$carrito_id, $data['id_producto'], $data['cantidad']]);
            }
            
            if($result) {
                echo json_encode(["message" => "Producto agregado al carrito"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al agregar producto"]);
            }
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conexion->prepare("
            UPDATE detalle_carrito dc
            JOIN carrito c ON dc.id_carrito = c.id_carrito
            SET dc.cantidad = ?
            WHERE c.sesion_id = ? AND dc.id_producto = ?
        ");
        
        $result = $stmt->execute([$data['cantidad'], $data['sesion_id'], $data['id_producto']]);
        
        if($result) {
            echo json_encode(["message" => "Carrito actualizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar carrito"]);
        }
        break;
        
    case 'DELETE':
        if($sesion_id) {
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            
            if($action === 'clear') {
                // Vaciar carrito completo
                $stmt = $conexion->prepare("
                    DELETE dc FROM detalle_carrito dc
                    JOIN carrito c ON dc.id_carrito = c.id_carrito
                    WHERE c.sesion_id = ?
                ");
                $result = $stmt->execute([$sesion_id]);
            } else {
                // Eliminar un producto específico
                $id_producto = isset($_GET['id_producto']) ? $_GET['id_producto'] : null;
                if($id_producto) {
                    $stmt = $conexion->prepare("
                        DELETE dc FROM detalle_carrito dc
                        JOIN carrito c ON dc.id_carrito = c.id_carrito
                        WHERE c.sesion_id = ? AND dc.id_producto = ?
                    ");
                    $result = $stmt->execute([$sesion_id, $id_producto]);
                }
            }
            
            if($result) {
                echo json_encode(["message" => "Carrito actualizado"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al actualizar carrito"]);
            }
        }
        break;
}
?>