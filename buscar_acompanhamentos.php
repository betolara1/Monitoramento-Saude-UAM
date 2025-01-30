<?php
session_start();
include "conexao.php";

$paciente_id = $_GET['paciente_id'];
$get_all = isset($_GET['get_all']) && $_GET['get_all'] === 'true';
$limit = $get_all ? null : (isset($_GET['limit']) ? (int)$_GET['limit'] : 3);

// Primeiro, buscar o total de registros
$sql_total = "SELECT COUNT(*) as total FROM acompanhamento_em_casa WHERE paciente_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param('i', $paciente_id);
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];

// Depois, buscar os registros
$sql = "SELECT 
    a.*,
    DATE_FORMAT(a.data_acompanhamento, '%d/%m/%Y') as data_formatada
    FROM acompanhamento_em_casa a 
    WHERE a.paciente_id = ? 
    ORDER BY a.data_acompanhamento DESC";

// Adicionar LIMIT apenas se não estiver buscando todos os registros
if (!$get_all) {
    $sql .= " LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $paciente_id, $limit);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $paciente_id);
}

$stmt->execute();
$resultado = $stmt->get_result();

$registros = [];
while ($row = $resultado->fetch_assoc()) {
    $registros[] = $row;
}

echo json_encode([
    'success' => true,
    'registros' => $registros,
    'total' => $total_registros
]);
?> 