<?php
session_start();

$erro = '';
$senhaPadrao = 'DeskLand#2026';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/conexao.php';

    $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');

    if ($nome === '' || strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $erro = 'Confira os dados informados e tente novamente.';
    } else {
        $stmt = $conn->prepare('SELECT email FROM clientes WHERE email = ? UNION SELECT email FROM vendedores WHERE email = ? LIMIT 1');
        $stmt->bind_param('ss', $email, $email);
        $stmt->execute();
        $emailExistente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($emailExistente) {
            $erro = 'Já existe uma conta cadastrada com esse e-mail.';
        } else {
            $stmt = $conn->prepare('INSERT INTO clientes (nome, email, senha) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nome, $email, $senhaPadrao);
            $stmt->execute();
            $idCliente = $conn->insert_id;
            $stmt->close();

            session_regenerate_id(true);
            $_SESSION['user'] = $email;
            $_SESSION['user_id'] = (int) $idCliente;
            $_SESSION['user_name'] = $nome;
            $_SESSION['user_role'] = 'cliente';
            header('Location: cliente.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Página de Cadastro</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css" />
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-color: #4171d8;
      }

      .fundo-informacoes {
        display: flex;
        justify-content: center;
        align-items: center;
        max-width: 100%;
        max-height: 100%;
        min-height: 100vh;
      }

      form {
        text-align: center;
        background-color: #6182cb;
        width: min(520px, calc(100% - 2rem));
        padding: 50px;
        box-sizing: border-box;
        color: #fff;
        border-radius: 100px;
      }

      input[type="text"],
      input[type="email"] {
        width: 100%;
        box-sizing: border-box;
        padding: 10px;
        margin-bottom: 20px;
        border: none;
        border-radius: 50px;
        color: #fff;
        background-color: #8196c1;
      }

      input::placeholder {
        color: #fff;
        opacity: 0.5;
      }

      .Login-Texto {
        font-size: 30px;
        margin-bottom: 20px;
        justify-content: flex-start;
        align-items: flex-start;
        height: 100px;
      }

      .botao {
        width: 50%;
        padding: 10px;
        border: none;
        border-radius: 50px;
        background-color: #fff;
        color: #4171d8;
        font-size: 16px;
        cursor: pointer;
      }
    </style>
  </head>
  <body>
    <div class="fundo-informacoes">
      <form action="cadastro.php" method="POST">
        <h2 class="Login-Texto">Cadastro de cliente</h2>
        <?php if ($erro !== ''): ?>
          <p class="alert alert-danger" role="alert"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <input type="text" name="nome" placeholder="Nome:" autocomplete="name" maxlength="120" required /><br />
        <input type="email" name="email" placeholder="E-mail:" autocomplete="email" maxlength="150" required /><br />
        <p class="alert alert-info">Todos os usuários compartilham a senha <strong><?php echo htmlspecialchars($senhaPadrao, ENT_QUOTES, 'UTF-8'); ?></strong>. Não use essa configuração em produção.</p>
        <button type="submit" class="botao">Cadastrar</button>
        <p><a href="login.php" style="color: #fff; text-decoration: underline;">Já tem uma conta? Faça login aqui.</a></p>
      </form>
    </div>
  </body>
</html>
