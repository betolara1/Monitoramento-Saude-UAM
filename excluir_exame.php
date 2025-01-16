<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    if (empty($_POST['exame_id'])) {
        throw new Exception('ID do exame não fornecido');
    }

    $exame_id = intval($_POST['exame_id']);

    // Primeiro, pegar o arquivo para excluir
    $query = "SELECT arquivo_exame FROM exames WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exame_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['arquivo_exame']) && file_exists($row['arquivo_exame'])) {
            unlink($row['arquivo_exame']); // Remove o arquivo físico
        }
    }

    // Agora excluir o registro
    $query = "DELETE FROM exames WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exame_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir exame: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Exame excluído com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 