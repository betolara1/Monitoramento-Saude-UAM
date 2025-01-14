<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['medicamento_id'])) {
            throw new Exception('ID do medicamento não fornecido');
        }

        $medicamento_id = intval($_POST['medicamento_id']);
        
        $query = "DELETE FROM medicamentos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $medicamento_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Erro ao excluir medicamento');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?> 