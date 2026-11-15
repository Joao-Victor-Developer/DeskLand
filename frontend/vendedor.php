<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendedor') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexao.php';

$schemaResult = $conn->query("SHOW COLUMNS FROM produtos LIKE 'id_vendedor'");
$temVinculoVendedor = $schemaResult && $schemaResult->num_rows > 0;
if ($schemaResult) {
    $schemaResult->free();
}

if (!isset($_SESSION['csrf_produto'])) {
    $_SESSION['csrf_produto'] = bin2hex(random_bytes(32));
}

$erro = $temVinculoVendedor
    ? ''
    : 'Para usar o cadastro de produtos, importe migrar_produtos_vendedor.sql no banco de dados.';
$sucesso = isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso';
$categorias = array();
$categoriasResult = $conn->query('SELECT id_categoria, nome FROM categorias ORDER BY nome');
while ($categoria = $categoriasResult->fetch_assoc()) {
    $categorias[] = $categoria;
}
$categoriasResult->free();
$categoriasIds = array_map('intval', array_column($categorias, 'id_categoria'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$temVinculoVendedor) {
    $erro = 'Importe migrar_produtos_vendedor.sql no banco de dados antes de cadastrar produtos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $nome = isset($_POST['nome']) && is_string($_POST['nome']) ? trim($_POST['nome']) : '';
    $marca = isset($_POST['marca']) && is_string($_POST['marca']) ? trim($_POST['marca']) : '';
    $descricao = isset($_POST['descricao']) && is_string($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $imagem = isset($_POST['imagem']) && is_string($_POST['imagem']) ? trim($_POST['imagem']) : '';
    $precoEntrada = isset($_POST['preco']) && is_string($_POST['preco']) ? str_replace(',', '.', trim($_POST['preco'])) : '';
    $precoAntigoEntrada = isset($_POST['preco_antigo']) && is_string($_POST['preco_antigo']) ? str_replace(',', '.', trim($_POST['preco_antigo'])) : '';
    $categoriaId = isset($_POST['id_categoria']) && is_string($_POST['id_categoria'])
        ? filter_var($_POST['id_categoria'], FILTER_VALIDATE_INT)
        : false;
    $estoque = isset($_POST['estoque']) && is_string($_POST['estoque'])
        ? filter_var($_POST['estoque'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 4294967295)))
        : false;

    $precoValido = preg_match('/^\d+(?:\.\d{1,2})?$/', $precoEntrada) === 1
        && (float) $precoEntrada > 0
        && (float) $precoEntrada <= 99999999.99;
    $precoAntigoVazio = $precoAntigoEntrada === '';
    $precoAntigoValido = $precoAntigoVazio || (
        preg_match('/^\d+(?:\.\d{1,2})?$/', $precoAntigoEntrada) === 1
        && (float) $precoAntigoEntrada > 0
        && (float) $precoAntigoEntrada <= 99999999.99
    );
    $imagemUrlValida = filter_var($imagem, FILTER_VALIDATE_URL) !== false
        && preg_match('/^https?:\/\//i', $imagem) === 1;
    $imagemLocalValida = preg_match('#^[A-Za-z0-9_./-]+$#', $imagem) === 1
        && strpos($imagem, '..') === false;
    $imagemValida = $imagem === ''
        || (strlen($imagem) <= 255 && ($imagemUrlValida || $imagemLocalValida));
    $nomeValido = preg_match('/\A.{1,180}\z/us', $nome) === 1;
    $marcaValida = preg_match('/\A.{0,80}\z/us', $marca) === 1;
    $descricaoValida = strlen($descricao) <= 65535;
    $categoriaValida = $categoriaId !== false && in_array((int) $categoriaId, $categoriasIds, true);

    if (!hash_equals($_SESSION['csrf_produto'], $token)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif (!$nomeValido || !$marcaValida || !$descricaoValida || !$precoValido || !$precoAntigoValido || $estoque === false || !$imagemValida || !$categoriaValida) {
        $erro = 'Confira os dados do produto e tente novamente.';
    } else {
        $slugBase = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) : $nome;
        if ($slugBase === false) {
            $slugBase = $nome;
        }
        $slugBase = strtolower($slugBase);
        $slugBase = preg_replace('/[^a-z0-9]+/', '-', $slugBase);
        $slugBase = trim($slugBase, '-');
        if ($slugBase === '') {
            $slugBase = 'produto';
        }
        $slug = substr($slugBase, 0, 180) . '-' . bin2hex(random_bytes(4));
        $descricaoBanco = $descricao === '' ? null : $descricao;
        $precoBanco = number_format((float) $precoEntrada, 2, '.', '');
        $precoAntigoBanco = $precoAntigoVazio ? null : number_format((float) $precoAntigoEntrada, 2, '.', '');
        $imagemBanco = $imagem === '' ? null : $imagem;

        $stmt = $conn->prepare(
            'INSERT INTO produtos (id_categoria, id_vendedor, nome, slug, marca, descricao, preco, preco_antigo, estoque, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $idVendedor = (int) $_SESSION['user_id'];
        $categoriaId = (int) $categoriaId;
        $estoque = (int) $estoque;
        $stmt->bind_param(
            'iissssssis',
            $categoriaId,
            $idVendedor,
            $nome,
            $slug,
            $marca,
            $descricaoBanco,
            $precoBanco,
            $precoAntigoBanco,
            $estoque,
            $imagemBanco
        );

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: vendedor.php?cadastro=sucesso');
            exit;
        }

        error_log('Falha ao cadastrar produto: ' . $stmt->error);
        $stmt->close();
        $erro = 'Não foi possível cadastrar o produto. Tente novamente.';
    }
}

$produtos = array();
if ($temVinculoVendedor) {
    $stmt = $conn->prepare(
        'SELECT p.nome, p.marca, p.preco, p.estoque, p.imagem, p.ativo, c.nome AS categoria
         FROM produtos p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         WHERE p.id_vendedor = ?
         ORDER BY p.criado_em DESC, p.id_produto DESC'
    );
    $idVendedor = (int) $_SESSION['user_id'];
    $stmt->bind_param('i', $idVendedor);
    $stmt->execute();
    $produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$produtosAtivos = 0;
foreach ($produtos as $produto) {
    if ((int) $produto['ativo'] === 1) {
        $produtosAtivos++;
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deskland - Painel do Vendedor</title>
  <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="bootstrap-5.3.8-dist/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <header class="bg-header py-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
      <a href="index.html" class="text-decoration-none text-dark bg-white px-2 py-1 rounded"><strong>🖥️ DESKLAND</strong></a>
      <nav class="d-flex flex-wrap gap-2">
        <a href="index.html" class="btn btn-header px-4">Início</a>
        <a href="produtos.php" class="btn btn-header px-4">Ver loja</a>
        <a href="vendedor.php" class="btn btn-header px-4" aria-current="page">Painel do vendedor</a>
      </nav>
      <span class="cart-icon" aria-hidden="true">🖥️</span>
    </div>
  </header>

  <main class="container my-5 flex-grow-1">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="box-roxo flex-grow-1">
        <p class="text-uppercase fw-bold mb-1">Central do vendedor</p>
        <h1 class="h2 mb-2">Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <p class="mb-0">Gerencie seus anúncios, acompanhe as vendas e mantenha seu catálogo atualizado.</p>
      </div>
      <?php if ($temVinculoVendedor): ?>
        <a href="#form-produto" class="btn btn-success rounded-pill px-4 py-3">＋ Cadastrar produto</a>
      <?php endif; ?>
    </div>

    <section class="row g-3 mb-4" aria-label="Resumo da loja">
      <div class="col-sm-6 col-lg-4"><div class="box-cinza h-100"><p class="text-muted mb-1">Produtos ativos</p><p class="h2 mb-0"><?php echo $produtosAtivos; ?></p></div></div>
      <div class="col-sm-6 col-lg-4"><div class="box-cinza h-100"><p class="text-muted mb-1">Pedidos recebidos</p><p class="h2 mb-0">0</p></div></div>
      <div class="col-sm-6 col-lg-4"><div class="box-cinza h-100"><p class="text-muted mb-1">Vendas realizadas</p><p class="h2 mb-0">R$ 0,00</p></div></div>
    </section>

    <?php if ($sucesso): ?>
      <div class="alert alert-success" role="status">Produto cadastrado com sucesso.</div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
      <div class="alert <?php echo $temVinculoVendedor ? 'alert-danger' : 'alert-warning'; ?>" role="alert"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($temVinculoVendedor): ?>
    <section class="box-cinza mb-4" id="form-produto" aria-labelledby="form-produto-titulo">
      <h2 id="form-produto-titulo" class="h4 mb-3">Cadastrar produto</h2>
      <form method="POST" action="vendedor.php#form-produto" class="bg-white rounded p-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_produto'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="row g-3">
          <div class="col-md-8">
            <label for="nome" class="form-label">Nome do produto</label>
            <input id="nome" name="nome" class="form-control" maxlength="180" required value="<?php echo htmlspecialchars(isset($_POST['nome']) && is_string($_POST['nome']) ? $_POST['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-4">
            <label for="marca" class="form-label">Marca</label>
            <input id="marca" name="marca" class="form-control" maxlength="80" value="<?php echo htmlspecialchars(isset($_POST['marca']) && is_string($_POST['marca']) ? $_POST['marca'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-6">
            <label for="id_categoria" class="form-label">Categoria</label>
            <select id="id_categoria" name="id_categoria" class="form-select" required>
              <option value="">Selecione uma categoria</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo (int) $categoria['id_categoria']; ?>" <?php echo isset($_POST['id_categoria']) && is_string($_POST['id_categoria']) && $_POST['id_categoria'] === (string) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label for="preco" class="form-label">Preço (R$)</label>
            <input id="preco" name="preco" type="number" min="0.01" step="0.01" class="form-control" required value="<?php echo htmlspecialchars(isset($_POST['preco']) && is_string($_POST['preco']) ? $_POST['preco'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-3">
            <label for="preco_antigo" class="form-label">Preço anterior (opcional)</label>
            <input id="preco_antigo" name="preco_antigo" type="number" min="0.01" step="0.01" class="form-control" value="<?php echo htmlspecialchars(isset($_POST['preco_antigo']) && is_string($_POST['preco_antigo']) ? $_POST['preco_antigo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-4">
            <label for="estoque" class="form-label">Estoque</label>
            <input id="estoque" name="estoque" type="number" min="0" step="1" class="form-control" required value="<?php echo htmlspecialchars(isset($_POST['estoque']) && is_string($_POST['estoque']) ? $_POST['estoque'] : '0', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-md-8">
            <label for="imagem" class="form-label">Caminho da imagem ou URL (opcional)</label>
            <input id="imagem" name="imagem" class="form-control" maxlength="255" placeholder="imagens/produtos/produto.webp ou https://..." value="<?php echo htmlspecialchars(isset($_POST['imagem']) && is_string($_POST['imagem']) ? $_POST['imagem'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="col-12">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea id="descricao" name="descricao" class="form-control" rows="4"><?php echo htmlspecialchars(isset($_POST['descricao']) && is_string($_POST['descricao']) ? $_POST['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-success rounded-pill px-4">Salvar produto</button>
          </div>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <section class="box-cinza" aria-labelledby="anuncios-titulo">
      <h2 id="anuncios-titulo" class="h4 mb-3">Meus anúncios</h2>
      <?php if (count($produtos) === 0): ?>
        <div class="bg-white rounded p-4 text-center">
          <div class="fs-1" aria-hidden="true">🛍️</div>
          <h3 class="h5">Você ainda não tem anúncios</h3>
          <p class="text-muted mb-0"><?php echo $temVinculoVendedor ? 'Cadastre seu primeiro produto usando o formulário acima.' : 'Aplique a migração do banco para cadastrar produtos.'; ?></p>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($produtos as $produto): ?>
            <div class="col-md-6 col-xl-4">
              <article class="bg-white rounded p-3 h-100">
                <?php if ($produto['imagem'] !== null && $produto['imagem'] !== ''): ?>
                  <img src="<?php echo htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid rounded mb-3" style="max-height: 180px; width: 100%; object-fit: contain;">
                <?php endif; ?>
                <p class="text-muted small mb-1"><?php echo htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($produto['marca'], ENT_QUOTES, 'UTF-8'); ?></p>
                <h3 class="h5"><?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="mb-1">R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?></p>
                <p class="text-muted mb-0">Estoque: <?php echo (int) $produto['estoque']; ?> · <?php echo (int) $produto['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?></p>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <p class="container">Você está logado como: <?php echo htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?> · <a href="logoff.php">Sair</a></p>

  <footer class="bg-footer py-4 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
      <span>🖥️ DESKLAND</span><span>Central do Vendedor</span>
    </div>
  </footer>
</body>
</html>
