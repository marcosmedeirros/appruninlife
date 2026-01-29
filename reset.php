<?php
require __DIR__ . '/includes/bootstrap.php';
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RunInLife | Redefinir senha</title>
  <link rel="stylesheet" href="assets/css/app.css" />
</head>
<body>
  <div class="bg-noise"></div>
  <main class="container">
    <section class="panel">
      <header class="panel-header">
        <div>
          <p class="eyebrow">RunInLife</p>
          <h1>Redefinir senha</h1>
          <p class="muted">Informe a nova senha.</p>
        </div>
      </header>

      <div class="auth-card">
        <form id="reset-form" class="form">
          <input type="hidden" name="action" value="reset" />
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
          <label>Nova senha <input type="password" name="password" minlength="4" required /></label>
          <button class="btn" type="submit">Redefinir</button>
          <p class="muted"><a href="index.php">Voltar ao login</a></p>
          <p class="form-message" data-message="reset"></p>
        </form>
      </div>
    </section>
  </main>
  <script src="assets/js/app.js"></script>
</body>
</html>
