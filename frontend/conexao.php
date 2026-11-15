<?php
		
// Cria a conexão
$conn = new mysqli("localhost", "root", "123456", "if0_43167089_deskland_bd"); //servidor usuario senha banco

// Verifica a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error); //retorna erro caso não conecte com o banco
}

$conn->set_charset("utf8mb4");
?>