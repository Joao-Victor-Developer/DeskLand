<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/conexao.php';

$conn->begin_transaction();
$senhaPadrao = 'DeskLand#2026';

$clientes = $conn->prepare('UPDATE clientes SET senha = ?');
$clientes->bind_param('s', $senhaPadrao);
$clientes->execute();
$totalClientes = $clientes->affected_rows;
$clientes->close();

$vendedores = $conn->prepare('UPDATE vendedores SET senha = ?');
$vendedores->bind_param('s', $senhaPadrao);
$vendedores->execute();
$totalVendedores = $vendedores->affected_rows;
$vendedores->close();

$conn->commit();
$conn->close();

echo "Senhas de clientes e vendedores redefinidas para: " . $senhaPadrao . "\n";
echo "Clientes: " . $totalClientes . "\n";
echo "Vendedores: " . $totalVendedores . "\n";
