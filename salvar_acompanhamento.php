<?php
// Desabilitar a exibição de erros no output
error_reporting(0);
ini_set('display_errors', 0);

// Garantir que nenhum output foi enviado antes
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    !in_array($_SESSION['tipo_usuario'], ['Admin', 'Medico', 'Enfermeiro', 'ACS', 'Paciente'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar dados de entrada
        if (!isset($_POST['paciente_id']) || !isset($_POST['data_acompanhamento'])) {
            throw new Exception('Dados obrigatórios não fornecidos');
        }

        $paciente_id = filter_var($_POST['paciente_id'], FILTER_VALIDATE_INT);
        if ($paciente_id === false) {
            throw new Exception('ID do paciente inválido');
        }

        $data_acompanhamento = $_POST['data_acompanhamento'];
        $glicemia = isset($_POST['glicemia']) ? filter_var($_POST['glicemia'], FILTER_SANITIZE_STRING) : null;
        $hipertensao = isset($_POST['hipertensao']) ? filter_var($_POST['hipertensao'], FILTER_SANITIZE_STRING) : null;
        $observacoes = isset($_POST['observacoes']) ? filter_var($_POST['observacoes'], FILTER_SANITIZE_STRING) : '';

        $query = "INSERT INTO acompanhamento_em_casa (paciente_id, data_acompanhamento, glicemia, hipertensao, observacoes) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Erro na preparação da query: ' . $conn->error);
        }

        $stmt->bind_param("issss", $paciente_id, $data_acompanhamento, $glicemia, $hipertensao, $observacoes);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao executar query: ' . $stmt->error);
        }

        $id_acompanhamento = $stmt->insert_id;

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