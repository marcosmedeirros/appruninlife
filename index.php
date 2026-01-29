<?php
require __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /app.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RunInLife | Login</title>
  <link rel="stylesheet" href="assets/css/app.css" />
</head>
<body>
  <div class="bg-noise"></div>

  <main class="container">
    <section id="auth" class="panel">
      <div class="auth-grid">
        <div class="auth-card" style="max-width:480px;margin:auto;">
          <h2>Login</h2>
          <?php if (isset($_GET['registered'])): ?>
            <div style="color:var(--accent);margin-bottom:8px;">Cadastro realizado com sucesso. Faça login.</div>
          <?php endif; ?>
          <?php if (isset($_GET['reset'])): ?>
            <div style="color:var(--accent);margin-bottom:8px;">Senha redefinida com sucesso. Faça login.</div>
          <?php endif; ?>
          <form id="login-form" class="form">
            <input type="hidden" name="action" value="login" />
            <label>
              Email
              <input type="email" name="email" required />
            </label>
            <label>
              Senha
              <input type="password" name="password" required />
            </label>
            <button type="submit" class="btn primary">Entrar</button>
            <p class="form-message" data-message="login"></p>
          </form>
          <div style="margin-top:16px; display:flex; justify-content:center;">
            <a href="register.php" class="btn white">Criar conta</a>
          </div>
          <div class="link-center">
            <a href="forgot.php">Esqueci a senha</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
