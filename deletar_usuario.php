<?php
session_start();
include 'conexao.php';

// Inicializa a resposta
$response = ['success' => false];

// Verifica se o usuário está logado e tem permissão
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    $response['message'] = 'Usuário não autorizado';
    echo json_encode($response);
    exit;
}

// Verifica se o tipo de usuário tem permissão
$tipo_usuario = $_SESSION['tipo_usuario'];
if (!in_array($tipo_usuario, ['Admin', 'Medico', 'Enfermeiro'])) {
    $response['message'] = 'Permissão negada';
    echo json_encode($response);
    exit;
}

// Verifica se o ID do usuário foi fornecido
if (!isset($_POST['usuario_id'])) {
    $response['message'] = 'ID do usuário não fornecido';
    echo json_encode($response);
    exit;
}

$usuario_id = $_POST['usuario_id'];

try {
    // Inicia a transação
    $conn->begin_transaction();

    // 1. Primeiro, verifica se o usuário existe e é um paciente
    $sql_check = "SELECT tipo_usuario FROM usuarios WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $usuario_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }

    // 2. Deleta registros relacionados na tabela pacientes
    $sql_delete_paciente = "DELETE FROM pacientes WHERE usuario_id = ?";
    $stmt_paciente = $conn->prepare($sql_delete_paciente);
    $stmt_paciente->bind_param("i", $usuario_id);
    $stmt_paciente->execute();

    // 3. Deleta registros relacionados na tabela logs_acesso
    $sql_delete_logs = "DELETE FROM logs_acesso WHERE usuario_id = ?";
    $stmt_logs = $conn->prepare($sql_delete_logs);
    $stmt_logs->bind_param("i", $usuario_id);
    $stmt_logs->execute();

    // 4. Finalmente, deleta o usuário
    $sql_delete_usuario = "DELETE FROM usuarios WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_delete_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();

    // Se chegou até aqui, commit na transação
    $conn->commit();

    // Registra o log da ação
    $admin_id = $_SESSION['usuario_id'];
    $acao = "Exclusão de usuário ID: $usuario_id";
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql_log = "INSERT INTO logs_acesso (usuario_id, acao, endereco_ip) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("iss", $admin_id, $acao, $ip);
    $stmt_log->execute();

    $response['success'] = true;
    $response['message'] = 'Usuário excluído com sucesso';

} catch (Exception $e) {
    // Se algo deu errado, rollback na transação
    $conn->rollback();
    $response['message'] = 'Erro ao excluir usuário: ' . $e->getMessage();
} finally {
    // Fecha todas as statements
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_paciente)) $stmt_paciente->close();
    if (isset($stmt_logs)) $stmt_logs->close();
    if (isset($stmt_usuario)) $stmt_usuario->close();
    if (isset($stmt_log)) $stmt_log->close();
    $conn->close();
}

// Retorna a resposta em JSON
header('Content-Type: application/json');
echo json_encode($response); 