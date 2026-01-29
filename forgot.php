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
  <title>RunInLife | Recuperar senha</title>
  <link rel="stylesheet" href="assets/css/app.css" />
</head>
<body>
  <div class="bg-noise"></div>
  <main class="container">
    <section class="panel">
      <header class="panel-header">
        <div>
          <p class="eyebrow">RunInLife</p>
          <h1>Recuperar senha</h1>
          <p class="muted">Informe seu email para receber instruções.</p>
        </div>
      </header>

      <div class="auth-card">
        <form id="forgot-form" class="form">
          <input type="hidden" name="action" value="forgot" />
          <label>Email <input type="email" name="email" required /></label>
          <button class="btn" type="submit">Enviar</button>
          <p class="muted"><a href="index.php">Voltar ao login</a></p>
          <p class="form-message" data-message="forgot"></p>
        </form>
      </div>
    </section>
  </main>
  <script src="assets/js/app.js"></script>
</body>
</html>
