<?php
require_once 'conexao.php';
session_start();

if (!isset($_GET['paciente_id'])) {
    echo json_encode(['error' => 'ID do paciente não fornecido']);
    exit;
}

$paciente_id = $_GET['paciente_id'];

// Uma única query para buscar tanto o total quanto os registros
$query = "SELECT *, (SELECT COUNT(*) FROM acompanhamento_em_casa WHERE paciente_id = ?) as total 
         FROM acompanhamento_em_casa 
         WHERE paciente_id = ? 
         ORDER BY data_acompanhamento DESC 
         LIMIT 3";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $paciente_id, $paciente_id);
$stmt->execute();
$result = $stmt->get_result();

$acompanhamentos = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $total = $row['total']; // O total será o mesmo em todas as linhas
    unset($row['total']); // Remove o campo total antes de adicionar ao array
    $row['data_acompanhamento'] = date('d/m/Y', strtotime($row['data_acompanhamento']));
    $acompanhamentos[] = $row;
}

// Consulta para obter o total de registros
$query_total = "SELECT COUNT(*) as total FROM acompanhamento_em_casa WHERE paciente_id = ?";
$stmt_total = $conn->prepare($query_total);
$stmt_total->bind_param("i", $paciente_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_row = $result_total->fetch_assoc();
$total = $total_row['total'];

// Retornar os dados
echo json_encode([
    'success' => true,
    'registros' => $acompanhamentos,
    'total' => (int)$total // Forçar como inteiro
]);
?> 