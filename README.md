# Vida em Controle

Painel pessoal para tocar o dia: tarefas de casa e do trabalho, finanças e treinos.
PHP + MySQL, sem build, sem dependência de front-end.

## Navegação

| Aba | O que faz |
|-----|-----------|
| **Hoje** | Tudo que precisa de atenção hoje numa lista só (tarefas + hábitos + treino), anel de progresso, saldo do mês, gasto do dia e nota do dia com autosave |
| **Tarefas** | Filtros por período (hoje / semana / todas / feitas) e por área (🏠 Casa, 💼 Trabalho, 👤 Pessoal). Avulsas, com data, diárias ou semanais |
| **Finanças** | Navegação por mês, saldo/entradas/saídas, "onde foi o dinheiro" por categoria e lançamentos agrupados por dia |
| **Treinos** | Plano semanal fixo, treino do dia com um botão de concluir, semana em 7 bolinhas, e corridas com km, tempo e pace |
| **Mais** | Hábitos, metas, anotações e ajustes (tema, saldo inicial, categorias) |

Desktop usa barra lateral; no celular vira barra inferior com 5 abas. Tema escuro,
claro ou automático (segue o sistema). Instalável como PWA.

Atalhos de teclado no desktop: `n` nova tarefa, `g` novo gasto, `Esc` fecha o modal.

## Arquivos

| Arquivo | Descrição |
|---------|-----------|
| `index.php` | Casca do app (HTML mínimo) + roteamento de `/app` e `/app_api.php` |
| `assets/css/app.css` | Toda a interface. Tokens de cor em `:root` e `[data-theme="light"]` |
| `assets/js/app.js` | Todo o comportamento. Sem framework, sem dependência externa |
| `api_lifeos.php` | API JSON principal (tarefas, hábitos, finanças, metas, notas) |
| `api_lifeos_extra.php` | Endpoints de treinos, corridas e o `bootstrap` |
| `api_lifeos_shared.php` | Criação de tabelas e migrations automáticas |
| `config.php` | Conexão com o banco + tabelas legadas |
| `app.php` / `app_api.php` | App de apostas, servido em `/app` |

## Por que é rápido

O app carrega **tudo numa chamada só** (`api_lifeos.php?api=bootstrap`) e guarda em
memória. Trocar de aba não faz request — só re-renderiza. Cada clique atualiza a tela
na hora e sincroniza com o servidor em segundo plano; se o servidor recusar, o app
avisa e recarrega para não mentir sobre o que foi salvo.

As fontes são as do sistema, então não há requisição de fonte bloqueando a primeira
pintura, e o tema é aplicado antes dela para não piscar branco no modo escuro.

## Banco de dados

As migrations rodam sozinhas a cada requisição (`ensure_tables()`), então basta subir
os arquivos. A remodelagem adicionou:

- `tasks.area` — `casa` / `trabalho` / `pessoal`
- `tasks.priority`, `tasks.archived`
- `workout_plan` — plano fixo por dia da semana (1 = segunda … 7 = domingo)
- `workouts.type`, `workouts.notes` + índice único por dia
- `runs.duration_min` — permite calcular o pace

Nada foi apagado: hábitos, metas, anotações, XP e apostas continuam no banco.

## API

Requisições para `api_lifeos.php?api=<ação>`. `GET` para leitura, `POST` com corpo
JSON para escrita. Todas respondem `{ok: true, data: ...}` ou `{ok: false, error: "..."}`.

Principais: `bootstrap`, `task_save`, `task_toggle`, `task_delete`, `habit_save`,
`habit_toggle`, `fin_save`, `fin_delete`, `fin_settings_save`, `cat_save`,
`goal_save`, `goal_deposit`, `note_save`, `workout_plan_save`, `workout_toggle`,
`run_save`, `run_delete`.

## Rodando local

```
run_local.bat
```

Sobe o PHP embutido em `http://localhost:8899`. Precisa do MySQL local com o banco
`u289267434_runlife` — ou defina `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no ambiente.
