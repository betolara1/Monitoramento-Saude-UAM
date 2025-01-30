<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    
    try {
        $query = "SELECT * FROM riscos_saude WHERE paciente_id = ? ORDER BY data_calculo DESC LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $riscos = [];
        while ($risco = $result->fetch_assoc()) {
            $riscos[] = $risco;
        }
        
        echo json_encode([
            'success' => true,
            'riscos' => $riscos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar riscos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID do paciente não fornecido'
    ]);
}
?> 