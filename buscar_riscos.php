<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    
    try {
        // Iniciar transação
        $conn->begin_transaction();

        // Primeiro, vamos buscar o total de registros
        $query_total = "SELECT COUNT(*) as total FROM riscos_saude WHERE paciente_id = ?";
        $stmt_total = $conn->prepare($query_total);
        $stmt_total->bind_param('i', $paciente_id);
        $stmt_total->execute();
        $total = $stmt_total->get_result()->fetch_assoc()['total'];

        // Determinar o limite baseado no parâmetro
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $total;
        
        // Buscar os registros com o limite apropriado
        $query = "SELECT 
                    rs.*,
                    DATE_FORMAT(rs.data_calculo, '%d/%m/%Y') as data_formatada,
                    COALESCE(rs.comparativo_risco, 'N/A') as comparativo_risco
                 FROM riscos_saude rs 
                 WHERE rs.paciente_id = ? 
                 ORDER BY rs.data_calculo DESC 
                 LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $paciente_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $riscos = [];
        while ($risco = $result->fetch_assoc()) {
            // Formatar a probabilidade de acordo com o valor
            if ($risco['probabilidade'] == '<1') {
                $risco['probabilidade'] = '<1';
            } elseif ($risco['probabilidade'] == '≥30' || $risco['probabilidade'] >= 30) {
                $risco['probabilidade'] = '≥30';
            }
            
            // Garantir que todos os campos necessários estejam presentes
            $risco['id'] = (int)$risco['id'];
            $risco['paciente_id'] = (int)$risco['paciente_id'];
            $risco['pontuacao'] = (int)$risco['pontuacao'];
            $risco['data_formatada'] = $risco['data_formatada'];
            
            $riscos[] = $risco;
        }

        // Commit da transação
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'riscos' => $riscos,
            'total' => $total
        ]);

    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar riscos: ' . $e->getMessage()
        ]);
    } finally {
        // Fechar todas as conexões
        if (isset($stmt_total)) $stmt_total->close();
        if (isset($stmt)) $stmt->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID do paciente não fornecido'
    ]);
}

$conn->close();
?> 