<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

switch($method) {
    case 'GET':
        if($id) {
            // Obtener un producto específico
            $stmt = $conexion->prepare("
                SELECT p.*, c.nombre_categoria 
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE p.id_producto = ?
            ");
            $stmt->execute([$id]);
            $producto = $stmt->fetch();
            
            if($producto) {
                echo json_encode($producto);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Producto no encontrado"]);
            }
        } else {
            // Obtener todos los productos
            $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
            
            if($categoria) {
                $stmt = $conexion->prepare("
                    SELECT p.*, c.nombre_categoria 
                    FROM producto p
                    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                    WHERE c.nombre_categoria = ?
                ");
                $stmt->execute([$categoria]);
            } else {
                $stmt = $conexion->query("
                    SELECT p.*, c.nombre_categoria 
                    FROM producto p
                    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                ");
            }
            
            $productos = $stmt->fetchAll();
            echo json_encode($productos);
        }
        break;
        
    case 'POST':
        // Crear nuevo producto
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conexion->prepare("
            INSERT INTO producto (nombre_producto, descripcion, precio, id_categoria, stock) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['nombre_producto'],
            $data['descripcion'],
            $data['precio'],
            $data['id_categoria'],
            $data['stock'] ?? 0
        ]);
        
        if($result) {
            $id_producto = $conexion->lastInsertId();
            echo json_encode([
                "message" => "Producto creado exitosamente",
                "id_producto" => $id_producto
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear producto"]);
        }
        break;
        
    case 'PUT':
        if($id) {
            // Actualizar producto
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $conexion->prepare("
                UPDATE producto 
                SET nombre_producto = ?, descripcion = ?, precio = ?, id_categoria = ?, stock = ?
                WHERE id_producto = ?
            ");
            
            $result = $stmt->execute([
                $data['nombre_producto'],
                $data['descripcion'],
                $data['precio'],
                $data['id_categoria'],
                $data['stock'],
                $id
            ]);
            
            if($result) {
                echo json_encode(["message" => "Producto actualizado exitosamente"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al actualizar producto"]);
            }
        }
        break;
        
    case 'DELETE':
        if($id) {
            $stmt = $conexion->prepare("DELETE FROM producto WHERE id_producto = ?");
            $result = $stmt->execute([$id]);
            
            if($result) {
                echo json_encode(["message" => "Producto eliminado exitosamente"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar producto"]);
            }
        }
        break;
}
?>