<?php
require_once __DIR__ . '/conexao.php';

$schemaResult = $conn->query("SHOW COLUMNS FROM produtos LIKE 'id_vendedor'");
$temVinculoVendedor = $schemaResult && $schemaResult->num_rows > 0;
if ($schemaResult) {
    $schemaResult->free();
}

if ($temVinculoVendedor) {
    $resultado = $conn->query(
        'SELECT p.nome, p.marca, p.descricao, p.preco, p.preco_antigo, p.estoque, p.imagem,
                c.nome AS categoria, v.nome AS vendedor
         FROM produtos p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         LEFT JOIN vendedores v ON v.id_vendedor = p.id_vendedor
         WHERE p.ativo = 1 AND p.estoque > 0
         ORDER BY p.destaque DESC, p.criado_em DESC, p.id_produto DESC'
    );
} else {
    $resultado = $conn->query(
        'SELECT p.nome, p.marca, p.descricao, p.preco, p.preco_antigo, p.estoque, p.imagem,
                c.nome AS categoria, NULL AS vendedor
         FROM produtos p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         WHERE p.ativo = 1 AND p.estoque > 0
         ORDER BY p.destaque DESC, p.criado_em DESC, p.id_produto DESC'
    );
}

$produtos = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deskland - Produtos</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-header py-3">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <a href="index.html" class="text-decoration-none text-dark bg-white px-2 py-1 rounded"><strong>🖥️ DESKLAND</strong></a>
            <nav class="d-flex flex-wrap gap-2">
                <a href="index.html" class="btn btn-header px-4">Início</a>
                <a href="produtos.php" class="btn btn-header px-4" aria-current="page">Produtos</a>
                <a href="login.php" class="btn btn-header px-4">Entrar</a>
            </nav>
            <span class="cart-icon" aria-hidden="true">🛒</span>
        </div>
    </header>

    <main class="container my-5 flex-grow-1">
        <div class="box-roxo mb-4">
            <p class="text-uppercase fw-bold mb-1">Catálogo</p>
            <h1 class="h2 mb-2">Produtos à venda</h1>
            <p class="mb-0">Produtos cadastrados pelos vendedores da Deskland.</p>
        </div>

        <?php if (count($produtos) === 0): ?>
            <div class="alert alert-info" role="status">Não há produtos disponíveis para venda no momento.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($produtos as $produto): ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <article class="card h-100 shadow-sm">
                            <?php if ($produto['imagem'] !== null && $produto['imagem'] !== ''): ?>
                                <img
                                    src="<?php echo htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                                    class="card-img-top bg-white p-3"
                                    alt="<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                    style="height: 200px; object-fit: contain;"
                                >
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center fs-1" style="height: 200px;" aria-hidden="true">🖥️</div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <p class="text-muted small mb-1">
                                    <?php echo htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                    ·
                                    <?php echo htmlspecialchars($produto['marca'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <h2 class="h5"><?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <?php if ($produto['descricao'] !== null && $produto['descricao'] !== ''): ?>
                                    <p class="small text-muted"><?php echo htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <p class="h5 mt-auto mb-1">R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?></p>
                                <?php if ($produto['preco_antigo'] !== null): ?>
                                    <p class="small text-muted mb-2"><del>R$ <?php echo number_format((float) $produto['preco_antigo'], 2, ',', '.'); ?></del></p>
                                <?php endif; ?>
                                <p class="small text-muted mb-1">Estoque: <?php echo (int) $produto['estoque']; ?></p>
                                <p class="small text-muted mb-0">Vendedor: <?php echo htmlspecialchars($produto['vendedor'] !== null ? $produto['vendedor'] : 'Deskland', ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-footer py-4 mt-auto">
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>🖥️ DESKLAND</span><span>Seu setup começa aqui.</span>
        </div>
    </footer>
</body>
</html>
