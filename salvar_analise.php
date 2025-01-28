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

    $analise_id = !empty($_POST['analise_id']) ? intval($_POST['analise_id']) : null;
    $paciente_id = intval($_POST['paciente_id']);
    
    // Buscar última consulta para comparação
    $query_ultima_consulta = "SELECT pressao_arterial, glicemia, data_consulta 
                             FROM consultas 
                             WHERE paciente_id = ? 
                             ORDER BY data_consulta DESC 
                             LIMIT 1, 1";
    $stmt = $conn->prepare($query_ultima_consulta);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $ultima_consulta = $stmt->get_result()->fetch_assoc();

    // Buscar consulta atual
    $query_consulta_atual = "SELECT pressao_arterial, glicemia, data_consulta 
                            FROM consultas 
                            WHERE paciente_id = ? 
                            ORDER BY data_consulta DESC 
                            LIMIT 1";
    $stmt = $conn->prepare($query_consulta_atual);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $consulta_atual = $stmt->get_result()->fetch_assoc();

    // Comparar Pressão Arterial
    $comparativo_pa = "Primeira medição";
    if ($ultima_consulta && $ultima_consulta['pressao_arterial'] && $consulta_atual['pressao_arterial']) {
        $pa_anterior = explode('/', $ultima_consulta['pressao_arterial'])[0]; // Pega sistólica
        $pa_atual = explode('/', $consulta_atual['pressao_arterial'])[0];
        
        if ($pa_atual < $pa_anterior) {
            $comparativo_pa = "Melhorou";
        } elseif ($pa_atual > $pa_anterior) {
            $comparativo_pa = "Piorou";
        } else {
            $comparativo_pa = "Estável";
        }
    }

    // Comparar Glicemia
    $comparativo_glicemia = "Primeira medição";
    if ($ultima_consulta && $ultima_consulta['glicemia'] && $consulta_atual['glicemia']) {
        $glicemia_anterior = intval($ultima_consulta['glicemia']);
        $glicemia_atual = intval($consulta_atual['glicemia']);
        
        if ($glicemia_atual < $glicemia_anterior) {
            $comparativo_glicemia = "Melhorou";
        } elseif ($glicemia_atual > $glicemia_anterior) {
            $comparativo_glicemia = "Piorou";
        } else {
            $comparativo_glicemia = "Estável";
        }
    }

    // Buscar último risco cardiovascular
    $query_riscos = "SELECT probabilidade, data_calculo 
                     FROM riscos_saude 
                     WHERE paciente_id = ? 
                     ORDER BY data_calculo DESC 
                     LIMIT 2";
    $stmt = $conn->prepare($query_riscos);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result_riscos = $stmt->get_result();
    $riscos = $result_riscos->fetch_all(MYSQLI_ASSOC);

    // Comparar Risco Cardiovascular
    $comparativo_risco_cardio = "Não avaliado";
    if (count($riscos) >= 2) {
        $risco_atual = $riscos[0]['probabilidade'];
        $risco_anterior = $riscos[1]['probabilidade'];
        
        if ($risco_atual < $risco_anterior) {
            $comparativo_risco_cardio = "Melhorou";
        } elseif ($risco_atual > $risco_anterior) {
            $comparativo_risco_cardio = "Piorou";
        } else {
            $comparativo_risco_cardio = "Estável";
        }
    } elseif (count($riscos) == 1) {
        $comparativo_risco_cardio = "Primeira avaliação";
    }

    // Data da análise será a data atual
    $data_analise = date('Y-m-d');

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
        'id' => $id,
        'analise' => [
            'comparativo_pa' => $comparativo_pa,
            'comparativo_glicemia' => $comparativo_glicemia,
            'comparativo_risco_cardio' => $comparativo_risco_cardio
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 