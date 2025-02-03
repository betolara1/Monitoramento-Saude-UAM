<?php
include 'conexao.php';
include 'verificar_login.php';

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Validar dados recebidos
        if (!isset($_POST['paciente_id']) || empty($_POST['paciente_id'])) {
            throw new Exception("ID do paciente não fornecido");
        }
        
        if (!isset($_POST['profissional_id']) || empty($_POST['profissional_id'])) {
            throw new Exception("ID do profissional não fornecido");
        }
        
        $paciente_id = intval($_POST['paciente_id']);
        $profissional_id = intval($_POST['profissional_id']);
        $tipo_profissional = 'Medico'; // Definindo tipo fixo como 'Medico'
        
        // Verificar se já existe uma atribuição para este paciente
        $check_sql = "SELECT id FROM paciente_profissional WHERE paciente_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $paciente_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Se já existe, atualiza
            $sql = "UPDATE paciente_profissional SET profissional_id = ? WHERE paciente_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $profissional_id, $paciente_id);
        } else {
            // Se não existe, insere
            $sql = "INSERT INTO paciente_profissional (paciente_id, profissional_id, tipo_profissional) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $paciente_id, $profissional_id, $tipo_profissional);
        }
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Médico atribuído com sucesso!';
        } else {
            throw new Exception("Erro ao atribuir médico: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Erro: ' . $e->getMessage();
        error_log("Erro na atribuição de médico: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>