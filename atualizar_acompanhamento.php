<?php
session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Medico' && $_SESSION['tipo_usuario'] !== 'Enfermeiro' && $_SESSION['tipo_usuario'] !== 'ACS')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['id'])) {
            throw new Exception('ID do acompanhamento não fornecido');
        }

        $id_acompanhamento = intval($_POST['id']);
        $data_acompanhamento = $_POST['data_acompanhamento'];
        $glicemia = $_POST['glicemia'] ?? null;
        $hipertensao = $_POST['hipertensao'] ?? null;
        $observacoes = $_POST['observacoes'] ?? '';

        $query = "UPDATE acompanhamento_em_casa SET 
                  data_acompanhamento = ?, 
                  glicemia = ?, 
                  hipertensao = ?, 
                  observacoes = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $data_acompanhamento, $glicemia, $hipertensao, $observacoes, $id_acompanhamento);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao atualizar acompanhamento: ' . $stmt->error);
        }

        // Retornar os dados atualizados
        echo json_encode([
            'success' => true,
            'message' => 'Acompanhamento atualizado com sucesso!',
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