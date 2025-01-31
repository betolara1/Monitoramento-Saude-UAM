<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe os dados
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
        exit;
    }

    try {
        // Iniciar transação
        $conn->begin_transaction();

        // Primeiro, verificar se o registro existe
        $query_check = "SELECT id FROM riscos_saude WHERE id = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Registro não encontrado');
        }

        // Prepara e executa a query de exclusão
        $query = "DELETE FROM riscos_saude WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Commit da transação
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Registro excluído com sucesso',
                'deleted_id' => $id
            ]);
        } else {
            throw new Exception('Erro ao excluir o registro');
        }
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
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Método de requisição inválido'
    ]);
}
?> 