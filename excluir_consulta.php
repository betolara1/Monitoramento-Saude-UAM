<?php
session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['consulta_id'])) {
            throw new Exception('ID da consulta não fornecido');
        }

        $consulta_id = intval($_POST['consulta_id']);
        
        $query = "DELETE FROM consultas WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception('Erro na preparação da query: ' . $conn->error);
        }

        $stmt->bind_param("i", $consulta_id);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao excluir consulta: ' . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Consulta excluída com sucesso'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?> 