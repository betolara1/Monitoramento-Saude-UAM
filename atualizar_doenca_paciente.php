<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');


// Recebe os dados do formulário
$id = intval($_POST['id']);
$tipo_doenca = $_POST['tipo_doenca'];
$historico_familiar = $_POST['historico_familiar'];
$estado_civil = $_POST['estado_civil'];
$profissao = $_POST['profissao'];

// Atualiza os dados no banco de dados
$sql = "UPDATE pacientes SET tipo_doenca = ?, historico_familiar = ?, estado_civil = ?, profissao = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $tipo_doenca, $historico_familiar, $estado_civil, $profissao, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar os dados']);
}

$conn->close();
?>