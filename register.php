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
  <title>RunInLife | Cadastrar</title>
  <link rel="stylesheet" href="assets/css/app.css" />
</head>
<body>
  <div class="bg-noise"></div>
  <main class="container">
    <section class="panel">
      <header class="panel-header">
        <div>
          <p class="eyebrow">RunInLife</p>
          <h1>Criar conta</h1>
          <p class="muted">Cadastre-se para começar a gamificar sua vida.</p>
        </div>
      </header>

      <div class="auth-card">
        <form id="register-form" class="form">
          <input type="hidden" name="action" value="register" />
          <label>Nome <input type="text" name="name" required /></label>
          <label>Email <input type="email" name="email" required /></label>
          <label>Senha <input type="password" name="password" minlength="4" required /></label>
          <button class="btn" type="submit">Cadastrar</button>
          <div style="margin-top:12px; display:flex; justify-content:center;">
            <a href="index.php" class="btn white">Voltar</a>
          </div>
          <p class="form-message" data-message="register"></p>
        </form>
      </div>
    </section>
  </main>
  <script src="assets/js/app.js"></script>
</body>
</html>
