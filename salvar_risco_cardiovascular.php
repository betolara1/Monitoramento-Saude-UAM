<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $paciente_id = $_POST['paciente_id'];
        $sexo = $_POST['sexo'];
        $idade = $_POST['idade'];
        $colesterol_total = $_POST['colesterol_total'];
        $colesterol_hdl = $_POST['colesterol_hdl'];
        $pressao_sistolica = $_POST['pressao_sistolica'];
        $fumante = $_POST['fumante'];
        $remedios_hipertensao = $_POST['remedios_hipertensao'];
        $probabilidade = $_POST['probabilidade'];
        $pontuacao = $_POST['pontuacao'];
        
        $query = "INSERT INTO riscos_saude (paciente_id, data_calculo, sexo, idade, colesterol_total, 
                  colesterol_hdl, pressao_sistolica, fumante, remedios_hipertensao, probabilidade, pontuacao) 
                  VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issiiiissi", $paciente_id, $sexo, $idade, $colesterol_total, 
                         $colesterol_hdl, $pressao_sistolica, $fumante, $remedios_hipertensao, 
                         $probabilidade, $pontuacao);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Risco cardiovascular salvo com sucesso!';
        } else {
            throw new Exception("Erro ao salvar no banco de dados");
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
}
?> 