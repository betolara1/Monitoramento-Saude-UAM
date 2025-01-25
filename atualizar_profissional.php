<?php
// Desabilitar a saída de HTML de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Garantir que a resposta seja sempre JSON
header('Content-Type: application/json');

// Registrar erros em log ao invés de exibi-los
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

try {
    require_once 'conexao.php';
    require_once 'verificar_login.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Log para debug
    error_log('POST data: ' . print_r($_POST, true));

    // Validar campos obrigatórios básicos
    if (!isset($_POST['profissional_id']) || !isset($_POST['usuario_id'])) {
        throw new Exception('ID do profissional e usuário são obrigatórios');
    }

    $profissional_id = intval($_POST['profissional_id']);
    $usuario_id = intval($_POST['usuario_id']);
    $unidade_saude = $_POST['unidade_saude'] ?? '';

    // Definir especialidade e registro profissional baseado no tipo de usuário

    if ($tipo_usuario === 'ACS') {
        $especialidade = 'ACS';
        $registro_profissional = null;
    }
        
    if ($tipo_usuario === 'Enfermeiro') {
        $especialidade = 'Enfermeiro';
        $registro_profissional = $_POST['registro_profissional'] ?? null;
    }
        
    if ($tipo_usuario === 'Medico' || $tipo_usuario === 'Admin') {
        $especialidade = $_POST['especialidade'] ?? '';
        $registro_profissional = $_POST['registro_profissional'] ?? null;
    }
        
    // Verifica conexão com o banco
    if (!$conn) {
        throw new Exception('Erro de conexão com o banco de dados');
    }

    // Verifica se o profissional existe
    $check_sql = "SELECT id FROM profissionais WHERE id = ? AND usuario_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        throw new Exception('Erro ao preparar consulta');
    }

    $check_stmt->bind_param("ii", $profissional_id, $usuario_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Profissional não encontrado');
    }

    // Atualiza as informações
    $sql = "UPDATE profissionais 
            SET especialidade = ?, 
                registro_profissional = ?,
                unidade_saude = ?
            WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar atualização');
    }

    $stmt->bind_param("sssii", 
        $especialidade,
        $registro_profissional,
        $unidade_saude,
        $profissional_id,
        $usuario_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar profissional');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profissional atualizado com sucesso',
        'profissional_id' => $profissional_id,
        'usuario_id' => $usuario_id,
        'data' => [
            'especialidade' => $especialidade,
            'registro_profissional' => $registro_profissional,
            'unidade_saude' => $unidade_saude
        ]
    ]);

} catch (Exception $e) {
    // Log do erro
    error_log('Erro em atualizar_profissional.php: ' . $e->getMessage());
    
    // Retorna erro em formato JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Garante que nada mais será enviado após o JSON
exit();
?>

