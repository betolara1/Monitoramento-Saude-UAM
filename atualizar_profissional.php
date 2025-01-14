<?php
include "conexao.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $usuario_id = $_POST['usuario_id'];
    $especialidade = $_POST['especialidade'];
    $registro_profissional = $_POST['registro_profissional'];
    $unidade_saude = $_POST['unidade_saude'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("UPDATE profissionais SET usuario_id = ?, especialidade = ?, registro_profissional = ?, unidade_saude = ? WHERE id = ?");
    $stmt->bind_param("isssi", $usuario_id, $especialidade, $registro_profissional, $unidade_saude, $id);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to a success page or list of professionals
        header("Location: listar_profissionais.php?success=2");
        exit();
    } else {
        // If there's an error, redirect back to the form with an error message
        header("Location: editar_profissionais.php?id=$id&error=1");
        exit();
    }

    $stmt->close();
} else {
    // If the form wasn't submitted, redirect to the list of professionals
    header("Location: listar_profissionais.php");
    exit();
}

$conn->close();
?>