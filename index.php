<?php
// ARQUIVO: index.php
// Casca do app "Vida em Controle". Toda a interface vive em assets/js/app.js e
// assets/css/app.css — este arquivo so monta o esqueleto e injeta os assets com
// cache-busting por data de modificacao.

require_once __DIR__ . '/includes/paths.php';

// O .htaccess manda /app e /app_api.php para ca. Mantem o app de apostas funcionando.
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#/app_api\.php$#', $uri)) {
    require __DIR__ . '/app_api.php';
    exit;
}
if (preg_match('#/app/?$#', $uri)) {
    require __DIR__ . '/app.php';
    exit;
}

$cssVer = @filemtime(__DIR__ . '/assets/css/app.css') ?: time();
$jsVer  = @filemtime(__DIR__ . '/assets/js/app.js') ?: time();
$icon = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 192 192\'%3E%3Crect fill=\'%230b0c0f\' width=\'192\' height=\'192\' rx=\'42\'/%3E%3Ccircle cx=\'96\' cy=\'96\' r=\'54\' fill=\'none\' stroke=\'%2310d9a0\' stroke-width=\'14\'/%3E%3Cpath d=\'M70 98l18 18 36-40\' fill=\'none\' stroke=\'%23ffffff\' stroke-width=\'14\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Vida em Controle</title>

<meta name="theme-color" content="#0b0c0f">
<meta name="description" content="Painel pessoal: o dia, tarefas de casa e do trabalho, finanças e treinos.">
<link rel="manifest" href="manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Vida em Controle">
<link rel="icon" type="image/svg+xml" href="<?= $icon ?>">
<link rel="apple-touch-icon" href="<?= $icon ?>">

<link rel="stylesheet" href="assets/css/app.css?v=<?= $cssVer ?>">
<script>
// Aplica o tema antes da primeira pintura para nao piscar branco no modo escuro.
(function () {
  try {
    var pref = localStorage.getItem('vc-theme') || 'auto';
    var dark = pref === 'dark' || (pref === 'auto' && !matchMedia('(prefers-color-scheme: light)').matches);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  } catch (e) {}
})();
</script>
</head>
<body>

<div class="shell">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-name">Vida em Controle</div>
      <div class="brand-sub">painel pessoal</div>
    </div>
    <nav id="sidebarNav"></nav>
    <div class="sidebar-foot">
      <div class="d1" id="sidebarDate">—</div>
      <div class="d2" id="sidebarDate2">—</div>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div>
        <div class="topbar-title" id="topTitle">Hoje</div>
        <div class="topbar-sub" id="topSub">—</div>
      </div>
    </header>
    <div class="wrap">
      <main id="app" class="panel"></main>
    </div>
  </div>

</div>

<nav class="bottomnav" id="bottomNav"></nav>
<div class="overlay" id="overlay"></div>
<div class="toasts" id="toasts"></div>

<script src="assets/js/app.js?v=<?= $jsVer ?>"></script>
</body>
</html>
