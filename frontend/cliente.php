<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deskland - Área do Cliente</title>
  <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="bootstrap-5.3.8-dist/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <header class="bg-header py-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
      <a href="index.html" class="text-decoration-none text-dark bg-white px-2 py-1 rounded"><strong>🖥️ DESKLAND</strong></a>
      <nav class="d-flex flex-wrap gap-2">
        <a href="index.html" class="btn btn-header px-4">Início</a>
        <a href="produtos.html" class="btn btn-header px-4">Produtos</a>
        <a href="cliente.php" class="btn btn-header px-4" aria-current="page">Minha conta</a>
      </nav>
      <a href="produtos.html" class="cart-icon text-decoration-none" aria-label="Ver produtos">🛒</a>
    </div>
  </header>

  <main class="container my-5 flex-grow-1">
    <div class="box-roxo mb-4">
      <p class="text-uppercase fw-bold mb-1">Área do cliente</p>
      <h1 class="h2 mb-2">Olá, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
      <p class="mb-0">Acompanhe seus pedidos e encontre os próximos componentes do seu setup.</p>
    </div>

    <p>Você está logado como: <?php echo htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?> · <a href="logoff.php">Sair</a></p>

    <div class="row g-4">
      <section class="col-lg-8" aria-labelledby="pedidos-titulo">
        <div class="box-cinza h-100">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 id="pedidos-titulo" class="h4 mb-0">Meus pedidos</h2>
            <a href="produtos.html" class="btn btn-success rounded-pill px-4">Continuar comprando</a>
          </div>
          <div class="bg-white rounded p-4 text-center">
            <div class="fs-1" aria-hidden="true">📦</div>
            <h3 class="h5">Seus pedidos aparecerão aqui</h3>
            <p class="text-muted mb-0">Quando você fizer uma compra, poderá acompanhar o status por esta página.</p>
          </div>
        </div>
      </section>
      <aside class="col-lg-4">
        <div class="bg-white border rounded-4 p-4 shadow-sm h-100">
          <h2 class="h5">Minha conta</h2>
          <p class="text-muted">Gerencie seus dados e preferências de compra.</p>
          <ul class="list-unstyled mb-0">
            <li class="py-2 border-bottom">👤 Dados pessoais</li>
            <li class="py-2 border-bottom">📍 Endereços de entrega</li>
            <li class="py-2">💳 Formas de pagamento</li>
          </ul>
        </div>
      </aside>
    </div>
  </main>

  <footer class="bg-footer py-4 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
      <span>🖥️ DESKLAND</span><span>Seu setup começa aqui.</span>
    </div>
  </footer>
</body>
</html>
