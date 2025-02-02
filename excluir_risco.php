<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido'
    ]);
    exit;
}

try {
    // Validação do ID
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
    
    if (!$id) {
        throw new Exception('ID inválido ou não fornecido');
    }

    // Início da transação
    $conn->begin_transaction();

    // Verificar se o registro existe e pertence ao paciente correto
    $query_check = "SELECT rs.id, rs.paciente_id 
                   FROM riscos_saude rs 
                   WHERE rs.id = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Registro não encontrado');
    }

    // Excluir o registro
    $query_delete = "DELETE FROM riscos_saude WHERE id = ?";
    $stmt_delete = $conn->prepare($query_delete);
    $stmt_delete->bind_param("i", $id);

    if (!$stmt_delete->execute()) {
        throw new Exception('Erro ao excluir o registro');
    }

    // Commit da transação
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Registro excluído com sucesso',
        'deleted_id' => $id
    ]);

} catch (Exception $e) {
    // Rollback em caso de erro
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
} finally {
    // Fechar todas as conexões
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_delete)) $stmt_delete->close();
    $conn->close();
}
?> 