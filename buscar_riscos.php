<?php
require_once 'conexao.php';

$paciente_id = $_GET['paciente_id'];

$query = "SELECT * FROM riscos_saude WHERE paciente_id = ? ORDER BY data_calculo DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$result = $stmt->get_result();

$riscos = array();
while ($risco = $result->fetch_assoc()) {
    $riscos[] = $risco;
}

header('Content-Type: application/json');
echo json_encode($riscos);
?> 