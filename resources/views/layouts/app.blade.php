<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RunInLife</title>
    <link rel="stylesheet" href="{{ asset('assets/css/global.css') }}">
</head>
<body>
    <nav class="navbar">
        <div><strong>RunInLife</strong></div>
        <div>
            <a href="/">Início</a>
            <a href="/habits">Hábitos</a>
            <a href="/tasks">Tarefas</a>
            <a href="/ranking">Ranking</a>
            <a href="/achievements">Conquistas</a>
            <a href="/store">Loja</a>
            <a href="/goals">Metas</a>
            <a href="/history">Histórico</a>
        </div>
    </nav>
    <main style="padding: 20px;">
        @yield('content')
    </main>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
