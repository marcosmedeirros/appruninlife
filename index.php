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
      <header class="panel-header">
        <div>
          <p class="eyebrow">RunInLife</p>
          <h1>Controle sua vida como um jogo.</h1>
          <p class="muted">Metas, agenda, treinos e hábitos com pontuação pessoal.</p>
        </div>
        <div class="badge">Mobile + Desktop</div>
      </header>

      <div class="auth-grid">
        <div class="auth-card">
          <h2>Entrar</h2>
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
        </div>

        <div class="auth-card">
          <h2>Criar conta</h2>
          <form id="register-form" class="form">
            <input type="hidden" name="action" value="register" />
            <label>
              Nome
              <input type="text" name="name" required />
            </label>
            <label>
              Email
              <input type="email" name="email" required />
            </label>
            <label>
              Senha
              <input type="password" name="password" minlength="4" required />
            </label>
            <button type="submit" class="btn ghost">Cadastrar</button>
            <p class="form-message" data-message="register"></p>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
