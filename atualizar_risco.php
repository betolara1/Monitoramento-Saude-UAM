<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se todos os campos necessários foram enviados
    $campos_requeridos = [
        'risco_id', 'paciente_id', 'sexo', 'idade', 
        'colesterol_total', 'colesterol_hdl', 'pressao_sistolica',
        'fumante', 'remedios_hipertensao', 'pontuacao', 'probabilidade'
    ];

    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo])) {
            throw new Exception("Campo obrigatório não fornecido: $campo");
        }
    }

    // Início da transação
    $conn->begin_transaction();

    // Atualizar o registro de risco
    $query = "UPDATE riscos_saude SET 
                sexo = ?,
                idade = ?,
                colesterol_total = ?,
                colesterol_hdl = ?,
                pressao_sistolica = ?,
                fumante = ?,
                remedios_hipertensao = ?,
                pontuacao = ?,
                probabilidade = ?
              WHERE id = ? AND paciente_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssiiiissiii",
        $_POST['sexo'],
        $_POST['idade'],
        $_POST['colesterol_total'],
        $_POST['colesterol_hdl'],
        $_POST['pressao_sistolica'],
        $_POST['fumante'],
        $_POST['remedios_hipertensao'],
        $_POST['pontuacao'],
        $_POST['probabilidade'],
        $_POST['risco_id'],
        $_POST['paciente_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Risco atualizado com sucesso'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar risco'
        ]);
    }

    // Commit da transação
    $conn->commit();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido'
    ]);
}

// Fechar conexões
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?> 