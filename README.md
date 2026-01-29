# RunInLife

Sistema híbrido (mobile/desktop) de controle de vida em formato gamificado. Tudo é 100% front-end e funciona direto na Hostinger com arquivos estáticos.

## Tecnologias
- Frontend: HTML + CSS + JavaScript (puro)
- Backend: PHP + MySQL (PDO)
- Estilo: Preto (fundo), vermelho (destaques) e branco

## Estrutura de Pastas
- `index.php` — Login (página inicial)
- `app.php` — Dashboard e módulos
- `auth.php` — Login/cadastro (API)
- `api.php` — Dados do usuário (API)
- `logout.php` — Encerrar sessão
- `assets/css/app.css` — Estilo global
- `assets/js/app.js` — UI e regras
- `migrations/init.sql` — Script de criação da tabela

## Funcionalidades
- Login como página inicial (index)
- Metas, agenda (tarefas/reuniões), treinos e habit tracker
- Categorias personalizadas
- Pontos por ação com níveis
- Histórico de conquistas
- Ranking global, missões diárias, conquistas e perfil com avatar
- Painel admin (criação de missões e conquistas)
- Dados separados por usuário (MySQL)

## Banco de dados
1. Crie um banco MySQL.
2. Execute `migrations/init.sql`.
3. Configure as variáveis no `.env` (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

## Deploy (Hostinger)
- Publique a raiz do projeto (onde está o `index.php`).

## Observações
- Autenticação via sessão PHP.
