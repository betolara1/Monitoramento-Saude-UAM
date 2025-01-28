<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

try {
    // Recebe os dados do formulário
    $paciente_id = $_POST['paciente_id'];
    $pontuacao = $_POST['pontuacao'];
    $probabilidade = $_POST['probabilidade'];
    $data_calculo = date('Y-m-d H:i:s'); // Data atual

    // Validação básica
    if (empty($paciente_id) || !isset($pontuacao) || !isset($probabilidade)) {
        throw new Exception('Dados incompletos');
    }

    // Prepara e executa a query
    $query = "INSERT INTO riscos_saude (paciente_id, pontuacao, probabilidade, data_calculo) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    // Converte para os tipos corretos
    $paciente_id = intval($paciente_id);
    $pontuacao = intval($pontuacao);
    $probabilidade = floatval($probabilidade);
    
    $stmt->bind_param("iiis", $paciente_id, $pontuacao, $probabilidade, $data_calculo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Risco cardiovascular salvo com sucesso']);
    } else {
        throw new Exception('Erro ao salvar no banco de dados');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 