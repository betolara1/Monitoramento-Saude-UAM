<?php
include "conexao.php";

$data = json_decode(file_get_contents("php://input"), true);

$pacienteId = intval($data['pacienteId']); // Este é o ID da tabela pacientes, não da usuarios
$profissionalId = intval($data['medicoId']);

// Primeiro, verifica se já existe uma relação
$checkSql = "SELECT id FROM paciente_profissional WHERE paciente_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('i', $pacienteId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Se existe, atualiza
    $sql = "UPDATE paciente_profissional SET profissional_id = ? WHERE paciente_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $profissionalId, $pacienteId);
} else {
    // Se não existe, insere
    $sql = "INSERT INTO paciente_profissional (paciente_id, profissional_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $pacienteId, $pacienteId);
}

$response = ['success' => $stmt->execute(), 'error' => $stmt->error];

header('Content-Type: application/json');
echo json_encode($response);
?>