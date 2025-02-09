<?php
session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Medico' && $_SESSION['tipo_usuario'] !== 'Enfermeiro' && $_SESSION['tipo_usuario'] !== 'ACS' && $_SESSION['tipo_usuario'] !== 'Paciente')) {
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

        $query = "DELETE FROM acompanhamento_em_casa WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_acompanhamento);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao excluir acompanhamento: ' . $stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Acompanhamento excluído com sucesso!']);

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