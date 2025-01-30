<?php
require_once 'conexao.php';
session_start();

if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    
    $query = "SELECT * FROM riscos_saude WHERE paciente_id = ? ORDER BY data_calculo DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $riscos = [];
    while ($risco = $result->fetch_assoc()) {
        $riscos[] = $risco;
    }
    
    echo json_encode($riscos);
}
?> 