<?php
include 'conexao.php';
include 'verificar_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo'])) {
    $tipo = $_POST['tipo'];
    
    // Query para buscar profissionais baseado no tipo_usuario
    $sql = "SELECT 
                u.id,
                u.nome,
                u.tipo_usuario,
                p.especialidade,
                p.unidade_saude,
                p.id as profissional_id
            FROM usuarios u 
            INNER JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.tipo_usuario = ?
            ORDER BY u.nome";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $profissionais = array();
    while ($row = $result->fetch_assoc()) {
        $profissionais[] = [
            'id' => $row['profissional_id'], // Usando o ID da tabela profissionais
            'nome' => $row['nome'],
            'especialidade' => $row['especialidade'],
            'unidade_saude' => $row['unidade_saude']
        ];
    }
    
    echo json_encode($profissionais);
    exit;
}

// Se não houver POST, busca todos os profissionais (médicos e enfermeiros)
$sql = "SELECT 
            u.id,
            u.nome,
            u.tipo_usuario,
            p.especialidade,
            p.unidade_saude,
            p.id as profissional_id
        FROM usuarios u 
        INNER JOIN profissionais p ON u.id = p.usuario_id 
        WHERE u.tipo_usuario IN ('Medico', 'Enfermeiro')
        ORDER BY u.tipo_usuario, u.nome";

$result = $conn->query($sql);
$profissionais = array();

while ($row = $result->fetch_assoc()) {
    $profissionais[$row['tipo_usuario']][] = [
        'id' => $row['profissional_id'],
        'nome' => $row['nome'],
        'especialidade' => $row['especialidade'],
        'unidade_saude' => $row['unidade_saude']
    ];
}

if (!isset($_POST['tipo'])) {
    echo json_encode($profissionais);
    exit;
}
?>