<?php
header('Content-Type: application/json');
include 'conexao.php';
include 'verificar_login.php';

// Adicione esta linha para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Validate required fields
    $required_fields = ['profissional_id', 'especialidade', 'unidade_saude'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo obrigatório não preenchido: $field");
        }
    }

    $profissional_id = $_POST['profissional_id'];
    $especialidade = $_POST['especialidade'];
    $registro_profissional = $_POST['registro_profissional'] ?? null;
    $unidade_saude = $_POST['unidade_saude'];

    // Se o tipo de usuário for enfermeiro, definir especialidade como "Enfermeiro"
    if ($tipo_usuario === 'Enfermeiro') {
        $especialidade = 'Enfermeiro';
    }
    if($tipo_usuario === 'ACS') {
        $especialidade = 'ACS';
    }

    // Update the professional's information
    $sql = "UPDATE profissionais 
            SET especialidade = ?, 
                registro_profissional = ?,
                unidade_saude = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", 
        $especialidade,
        $registro_profissional,
        $unidade_saude,
        $profissional_id
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Profissional atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar profissional: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

