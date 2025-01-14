<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['paciente_id']) || !isset($_POST['profissional_id'])) {
            throw new Exception('Dados incompletos');
        }

        $paciente_id = intval($_POST['paciente_id']);
        $profissional_id = intval($_POST['profissional_id']);

        // Primeiro, remove qualquer relação existente
        $query_delete = "DELETE FROM paciente_profissional WHERE paciente_id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param("i", $paciente_id);
        $stmt_delete->execute();

        // Depois, insere a nova relação
        $query_insert = "INSERT INTO paciente_profissional (paciente_id, profissional_id) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("ii", $paciente_id, $profissional_id);
        
        if ($stmt_insert->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Médico responsável atualizado com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar médico responsável');
        }

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