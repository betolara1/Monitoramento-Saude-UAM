<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Medico' && $_SESSION['tipo_usuario'] !== 'Enfermeiro')) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    if (empty($_POST['exame_id'])) {
        throw new Exception('ID do exame não fornecido');
    }

    $exame_id = intval($_POST['exame_id']);

    // Primeiro, verificar se o exame existe e pegar o arquivo
    $query = "SELECT arquivo_exame FROM exames WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exame_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar exame: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Exame não encontrado");
    }
    
    $row = $result->fetch_assoc();
    
    // Excluir o arquivo físico se existir
    if (!empty($row['arquivo_exame']) && file_exists($row['arquivo_exame'])) {
        if (!unlink($row['arquivo_exame'])) {
            throw new Exception("Erro ao excluir arquivo físico do exame");
        }
    }

    // Excluir o registro do banco de dados
    $query = "DELETE FROM exames WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exame_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir exame do banco de dados: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("Nenhum exame foi excluído");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Exame excluído com sucesso'
    ]);

} catch (Exception $e) {
    error_log("Erro ao excluir exame: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 