<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

try {
    // Verificar permissões
    if (!isset($_SESSION['tipo_usuario']) || 
        ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
        throw new Exception('Acesso não autorizado');
    }

    // Validar dados obrigatórios
    if (empty($_POST['paciente_id'])) {
        throw new Exception('ID do paciente não fornecido');
    }
    if (empty($_POST['data_analise'])) {
        throw new Exception('Data da análise não fornecida');
    }
    if (empty($_POST['comparativo_pa'])) {
        throw new Exception('Comparativo de PA não fornecido');
    }
    if (empty($_POST['comparativo_glicemia'])) {
        throw new Exception('Comparativo de glicemia não fornecido');
    }
    if (empty($_POST['comparativo_risco_cardio'])) {
        throw new Exception('Comparativo de risco cardiovascular não fornecido');
    }

    $analise_id = !empty($_POST['analise_id']) ? intval($_POST['analise_id']) : null;
    $paciente_id = intval($_POST['paciente_id']);
    $data_analise = $_POST['data_analise'];
    $comparativo_pa = $_POST['comparativo_pa'];
    $comparativo_glicemia = $_POST['comparativo_glicemia'];
    $comparativo_risco_cardio = $_POST['comparativo_risco_cardio'];

    if ($analise_id) {
        // UPDATE
        $query = "UPDATE analises_estatisticas SET 
                    data_analise = ?,
                    comparativo_pa = ?,
                    comparativo_glicemia = ?,
                    comparativo_risco_cardio = ?
                 WHERE id = ? AND paciente_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssii", 
            $data_analise,
            $comparativo_pa,
            $comparativo_glicemia,
            $comparativo_risco_cardio,
            $analise_id,
            $paciente_id
        );
    } else {
        // INSERT
        $query = "INSERT INTO analises_estatisticas 
                    (paciente_id, data_analise, comparativo_pa, 
                     comparativo_glicemia, comparativo_risco_cardio) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", 
            $paciente_id,
            $data_analise,
            $comparativo_pa,
            $comparativo_glicemia,
            $comparativo_risco_cardio
        );
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar análise: " . $stmt->error);
    }

    $id = $analise_id ?: $conn->insert_id;

    echo json_encode([
        'success' => true,
        'message' => $analise_id ? 'Análise atualizada com sucesso' : 'Análise salva com sucesso',
        'id' => $id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 