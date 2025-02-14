<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

// Função para calcular os horários
function calcularHorarios($horarioInicial, $frequencia) {
    if (!$horarioInicial || !$frequencia) return '';
    
    $horarios = [];
    $hora = DateTime::createFromFormat('H:i', $horarioInicial);
    
    if (!$hora) return '';
    
    // Gerar 4 horários (24h / frequência, limitado a 4)
    for ($i = 0; $i < min(4, 24/$frequencia); $i++) {
        $horarios[] = $hora->format('H:i');
        $hora->modify("+{$frequencia} hours");
    }
    
    return implode(' → ', $horarios);
}

if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    
    try {
        // Buscar total de registros
        $query_total = "SELECT COUNT(*) as total FROM medicamentos WHERE paciente_id = ?";
        $stmt_total = $conn->prepare($query_total);
        $stmt_total->bind_param("i", $paciente_id);
        $stmt_total->execute();
        $total = $stmt_total->get_result()->fetch_assoc()['total'];

        // Buscar medicamentos
        $query = "SELECT *, 
                 DATE_FORMAT(data_inicio, '%d/%m/%Y') as data_inicio_formatada,
                 DATE_FORMAT(data_fim, '%d/%m/%Y') as data_fim_formatada,
                 DATE_FORMAT(horario, '%H:%i') as horario_formatado
                 FROM medicamentos 
                 WHERE paciente_id = ? 
                 ORDER BY data_inicio DESC" . 
                 ($limit ? " LIMIT $limit" : "");
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicamentos = [];
        while ($row = $result->fetch_assoc()) {
            // Adicionar os horários calculados ao resultado
            $row['horarios_calculados'] = calcularHorarios($row['horario_formatado'], $row['frequencia']);
            $medicamentos[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'medicamentos' => $medicamentos,
            'total' => $total
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID do paciente não fornecido'
    ]);
} 