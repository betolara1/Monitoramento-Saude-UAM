<?php
session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $paciente_id = intval($_POST['paciente_id']);
        $data_acompanhamento = $_POST['data_acompanhamento'];
        $glicemia = $_POST['glicemia'] ?? null;
        $hipertensao = $_POST['hipertensao'] ?? null;
        $observacoes = $_POST['observacoes'] ?? '';

        $query = "INSERT INTO acompanhamento_em_casa (paciente_id, data_acompanhamento, glicemia, hipertensao, observacoes) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $paciente_id, $data_acompanhamento, $glicemia, $hipertensao, $observacoes);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao salvar acompanhamento: ' . $stmt->error);
        }

        // Obter o ID do último acompanhamento inserido
        $id_acompanhamento = $stmt->insert_id;

        // Retornar os dados do acompanhamento salvo
        echo json_encode([
            'success' => true,
            'message' => 'Acompanhamento salvo com sucesso!',
            'dados_acompanhamento' => [
                'id' => $id_acompanhamento,
                'data_acompanhamento' => date('d/m/Y', strtotime($data_acompanhamento)),
                'glicemia' => $glicemia,
                'hipertensao' => $hipertensao,
                'observacoes' => $observacoes
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?> 