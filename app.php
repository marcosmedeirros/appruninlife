<?php
require __DIR__ . '/includes/bootstrap.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RunInLife | Dashboard</title>
  <link rel="stylesheet" href="assets/css/app.css" />
</head>
<body>
  <div class="bg-noise"></div>

  <main class="container">
    <section id="app" class="panel">
      <header class="topbar">
        <div>
          <p class="eyebrow">RunInLife</p>
          <h2 id="welcome"></h2>
        </div>
        <div class="stats">
          <div class="stat">
            <span>Pontos</span>
            <strong id="points">0</strong>
          </div>
          <div class="stat">
            <span>Nível</span>
            <strong id="level">1</strong>
          </div>
          <button id="logout" class="btn ghost">Sair</button>
        </div>
      </header>

      <div class="layout">
        <aside class="sidebar">
          <button class="nav-link active" data-view="dashboard">Painel</button>
          <button class="nav-link" data-view="tasks">Agenda</button>
          <button class="nav-link" data-view="goals">Metas</button>
          <button class="nav-link" data-view="habits">Hábitos</button>
          <button class="nav-link" data-view="workouts">Treinos</button>
          <button class="nav-link" data-view="categories">Categorias</button>
          <button class="nav-link" data-view="history">Histórico</button>
        </aside>

        <section class="content">
          <div id="dashboard" class="view"></div>
          <div id="tasks" class="view hidden"></div>
          <div id="goals" class="view hidden"></div>
          <div id="habits" class="view hidden"></div>
          <div id="workouts" class="view hidden"></div>
          <div id="categories" class="view hidden"></div>
          <div id="history" class="view hidden"></div>
        </section>
      </div>
    </section>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
