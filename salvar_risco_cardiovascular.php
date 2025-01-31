<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

try {
    // Recebe os dados do formulário
    $paciente_id = $_POST['paciente_id'];
    $pontuacao = $_POST['pontuacao'];
    $probabilidade = $_POST['probabilidade'];
    $data_calculo = date('Y-m-d H:i:s');

    // Validação básica
    if (empty($paciente_id) || !isset($pontuacao) || !isset($probabilidade)) {
        throw new Exception('Dados incompletos');
    }

    // Início da transação
    $conn->begin_transaction();

    // Buscar os dois últimos registros de risco cardiovascular
    $query_ultimos = "SELECT probabilidade FROM riscos_saude 
                     WHERE paciente_id = ? 
                     ORDER BY data_calculo DESC 
                     LIMIT 2";
    $stmt_ultimos = $conn->prepare($query_ultimos);
    $stmt_ultimos->bind_param("i", $paciente_id);
    $stmt_ultimos->execute();
    $result_ultimos = $stmt_ultimos->get_result();
    $registros = $result_ultimos->fetch_all(MYSQLI_ASSOC);

    // Determinar o status do risco cardiovascular
    $comparativo_risco = "Primeira medição";
    if (count($registros) >= 1) {
        $risco_anterior = floatval($registros[0]['probabilidade']);
        if ($probabilidade < $risco_anterior) {
            $comparativo_risco = "Melhorou";
        } elseif ($probabilidade > $risco_anterior) {
            $comparativo_risco = "Piorou";
        } else {
            $comparativo_risco = "Estável";
        }
    }

    // Salvar novo registro de risco cardiovascular
    $query_risco = "INSERT INTO riscos_saude (paciente_id, pontuacao, probabilidade, data_calculo, 
                    sexo, idade, colesterol_total, colesterol_hdl, pressao_sistolica, 
                    fumante, remedios_hipertensao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_risco = $conn->prepare($query_risco);
    
    $stmt_risco->bind_param("iidsssiiiss", 
        $paciente_id, 
        $pontuacao, 
        $probabilidade, 
        $data_calculo,
        $_POST['sexo'],
        $_POST['idade'],
        $_POST['colesterol_total'],
        $_POST['colesterol_hdl'],
        $_POST['pressao_sistolica'],
        $_POST['fumante'],
        $_POST['remedios_hipertensao']
    );
    $stmt_risco->execute();

    // Após salvar o registro com sucesso, buscar o ID inserido
    $novo_id = $stmt_risco->insert_id;

    // Buscar os dados completos do novo registro
    $query_novo = "SELECT * FROM riscos_saude WHERE id = ?";
    $stmt_novo = $conn->prepare($query_novo);
    $stmt_novo->bind_param("i", $novo_id);
    $stmt_novo->execute();
    $result_novo = $stmt_novo->get_result();
    $novo_registro = $result_novo->fetch_assoc();

    // Verificar se existe um registro em analises_estatisticas
    $query_check = "SELECT id FROM analises_estatisticas 
                   WHERE paciente_id = ? 
                   ORDER BY id DESC 
                   LIMIT 1";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $paciente_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Atualizar o registro mais recente (último ID)
        $query_update = "UPDATE analises_estatisticas 
                        SET comparativo_risco_cardio = ? 
                        WHERE id = (
                            SELECT id FROM (
                                SELECT MAX(id) as id 
                                FROM analises_estatisticas 
                                WHERE paciente_id = ?
                            ) as temp
                        )";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("si", $comparativo_risco, $paciente_id);
        $stmt_update->execute();
    } else {
        // Criar novo registro se não existir
        $data_atual = date('Y-m-d');
        $query_insert = "INSERT INTO analises_estatisticas 
                        (paciente_id, data_analise, comparativo_risco_cardio) 
                        VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("iss", $paciente_id, $data_atual, $comparativo_risco);
        $stmt_insert->execute();
    }

    // Commit da transação
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Risco cardiovascular salvo com sucesso',
        'comparativo' => $comparativo_risco,
        'risco' => $novo_registro
    ]);

} catch (Exception $e) {
    // Rollback em caso de erro
    if ($conn->connect_errno == 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Fechar todas as conexões
    if (isset($stmt_ultimos)) $stmt_ultimos->close();
    if (isset($stmt_risco)) $stmt_risco->close();
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_update)) $stmt_update->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    if (isset($stmt_novo)) $stmt_novo->close();
    $conn->close();
}
?> 