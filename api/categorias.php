<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

switch($method) {
    case 'GET':
        if($id) {
            $stmt = $conexion->prepare("SELECT * FROM categoria WHERE id_categoria = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
        } else {
            $stmt = $conexion->query("SELECT * FROM categoria");
            echo json_encode($stmt->fetchAll());
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conexion->prepare("INSERT INTO categoria (nombre_categoria) VALUES (?)");
        $result = $stmt->execute([$data['nombre_categoria']]);
        
        if($result) {
            echo json_encode([
                "message" => "Categoría creada exitosamente",
                "id_categoria" => $conexion->lastInsertId()
            ]);
        }
        break;
}
?>