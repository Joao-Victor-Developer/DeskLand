<?php
session_start();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/conexao.php';

    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $erro = 'Informe um e-mail válido e sua senha.';
    } else {
        $usuario = null;
        $tipo = null;
        $consultas = array(
            'cliente' => array(
                'sql' => 'SELECT id_cliente AS id, nome, email, senha FROM clientes WHERE email = ? AND ativo = 1 LIMIT 1',
                'pagina' => 'cliente.php'
            ),
            'vendedor' => array(
                'sql' => 'SELECT id_vendedor AS id, nome, email, senha FROM vendedores WHERE email = ? AND ativo = 1 LIMIT 1',
                'pagina' => 'vendedor.php'
            )
        );

        foreach ($consultas as $papel => $consulta) {
            $stmt = $conn->prepare($consulta['sql']);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();
            $stmt->close();

            if ($usuario) {
                $tipo = $papel;
                break;
            }
        }

        $senhaValida = $usuario && hash_equals($usuario['senha'], $senha);

        if ($usuario && $senhaValida) {
            session_regenerate_id(true);
            $_SESSION['user'] = $usuario['email'];
            $_SESSION['user_id'] = (int) $usuario['id'];
            $_SESSION['user_name'] = $usuario['nome'];
            $_SESSION['user_role'] = $tipo;
            header('Location: ' . $consultas[$tipo]['pagina']);
            exit;
        }

        $erro = 'E-mail ou senha incorretos.';
    }
}
?>
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Página de Login</title>
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

      input[type="email"],
      input[type="password"] {
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
      <form action="login.php" method="POST">
        <h2 class="Login-Texto">Login</h2>
        <?php if ($erro !== ''): ?>
          <p class="alert alert-danger" role="alert"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <input type="email" name="email" placeholder="E-mail:" autocomplete="email" required /><br />
        <input type="password" name="senha" placeholder="Senha:" autocomplete="current-password" required /><br />
        <button type="submit" class="botao">Entrar</button>
        <p><a href="esqueceu-senha.html" style="color: #fff; text-decoration: underline;">Esqueceu a senha?</a></p>
        <p><a href="cadastro.php" style="color: #fff; text-decoration: underline;">Não tem uma conta? Cadastre-se aqui.</a></p>
      </form>
    </div>
  </body>
</html>
