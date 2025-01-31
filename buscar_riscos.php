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
                    DATE_FORMAT(rs.data_calculo, '%Y-%m-%d') as data_calculo_iso
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
                $risco['probabilidade_formatada'] = '<1';
            } elseif ($risco['probabilidade'] == '≥30' || $risco['probabilidade'] >= 30) {
                $risco['probabilidade_formatada'] = '≥30';
            } else {
                $risco['probabilidade_formatada'] = $risco['probabilidade'];
            }
            
            // Preparar o objeto com todos os campos necessários
            $riscoFormatado = [
                'id' => (int)$risco['id'],
                'paciente_id' => (int)$risco['paciente_id'],
                'data_formatada' => $risco['data_formatada'],
                'data_calculo' => $risco['data_calculo_iso'],
                'idade' => $risco['idade'],
                'sexo' => $risco['sexo'],
                'colesterol_total' => (int)$risco['colesterol_total'],
                'colesterol_hdl' => (int)$risco['colesterol_hdl'],
                'pressao_sistolica' => (int)$risco['pressao_sistolica'],
                'fumante' => $risco['fumante'],
                'remedios_hipertensao' => $risco['remedios_hipertensao'],
                'pontuacao' => (int)$risco['pontuacao'],
                'probabilidade' => $risco['probabilidade_formatada']
            ];
            
            $riscos[] = $riscoFormatado;
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