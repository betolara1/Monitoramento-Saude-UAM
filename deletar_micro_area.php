<?php
session_start();
include 'conexao.php';

// Verifica se é um administrador
if (!isset($_SESSION['tipo_usuario']) || !in_array($_SESSION['tipo_usuario'], ['Admin', 'Medico', 'Enfermeiro', 'ACS'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Verifica se a micro área está em uso
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE micro_area = (SELECT nome FROM micro_areas WHERE id = ?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Esta micro área não pode ser deletada pois está associada a usuários']);
        exit;
    }
    
    // Deleta a micro área
    $stmt = $conn->prepare("DELETE FROM micro_areas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar micro área']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
} 