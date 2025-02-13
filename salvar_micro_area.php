<?php
session_start();
include 'conexao.php';

// Verifica se é um administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    
    // Verifica se já existe uma micro área com este nome
    $stmt = $conn->prepare("SELECT id FROM micro_areas WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Esta micro área já existe']);
        exit;
    }
    
    // Insere nova micro área
    $stmt = $conn->prepare("INSERT INTO micro_areas (nome) VALUES (?)");
    $stmt->bind_param("s", $nome);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar micro área']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
} 