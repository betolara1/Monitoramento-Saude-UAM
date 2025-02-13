<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Iniciar transação
        $conn->begin_transaction();

        $usuario_id = $_POST['usuario_id'];
        $tipo_doenca = $_POST['tipo_doenca'];
        $historico_familiar = $_POST['historico_familiar'];
        $estado_civil = $_POST['estado_civil'];
        $profissao = $_POST['profissao'];

        // 1. Inserir na tabela pacientes
        $sql = "INSERT INTO pacientes (usuario_id, tipo_doenca, historico_familiar, estado_civil, profissao) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $usuario_id, $tipo_doenca, $historico_familiar, $estado_civil, $profissao);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao salvar dados do paciente");
        }
        
        $paciente_id = $conn->insert_id;

        // 2. Buscar um médico
        $sql_medico = "SELECT p.id 
                       FROM profissionais p 
                       JOIN usuarios u ON p.usuario_id = u.id 
                       WHERE u.tipo_usuario = 'Medico'
                       ORDER BY RAND()
                       LIMIT 1";
        
        $result_medico = $conn->query($sql_medico);
        $medico = $result_medico->fetch_assoc();

        // 3. Buscar um enfermeiro
        $sql_enfermeiro = "SELECT p.id 
                          FROM profissionais p 
                          JOIN usuarios u ON p.usuario_id = u.id 
                          WHERE u.tipo_usuario = 'Enfermeiro'
                          ORDER BY RAND()
                          LIMIT 1";
        
        $result_enfermeiro = $conn->query($sql_enfermeiro);
        $enfermeiro = $result_enfermeiro->fetch_assoc();

        // 4. Inserir relação com médico
        if ($medico) {
            $sql_relacao = "INSERT INTO paciente_profissional (paciente_id, profissional_id, tipo_profissional) 
                           VALUES (?, ?, 'Medico')";
            $stmt_relacao = $conn->prepare($sql_relacao);
            $stmt_relacao->bind_param("ii", $paciente_id, $medico['id']);
            
            if (!$stmt_relacao->execute()) {
                throw new Exception("Erro ao atribuir médico");
            }
        }

        // 5. Inserir relação com enfermeiro
        if ($enfermeiro) {
            $sql_relacao = "INSERT INTO paciente_profissional (paciente_id, profissional_id, tipo_profissional) 
                           VALUES (?, ?, 'Enfermeiro')";
            $stmt_relacao = $conn->prepare($sql_relacao);
            $stmt_relacao->bind_param("ii", $paciente_id, $enfermeiro['id']);
            
            if (!$stmt_relacao->execute()) {
                throw new Exception("Erro ao atribuir enfermeiro");
            }
        }

        // Se tudo deu certo, commit na transação
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Se algo deu errado, rollback na transação
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
    
    exit;
}
?>