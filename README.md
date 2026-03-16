# RunInLife - Sistema Integrado com App de Apostas

Sistema completo de planejamento de vida com módulo integrado de gestão de apostas.

## 🎯 URLs de Acesso

| Rota | Descrição |
|------|-----------|
| `https://marcosmedeiros.page/` | Página principal do sistema |
| `https://marcosmedeiros.page/app` | App integrado de apostas |
| `https://marcosmedeiros.page/app_api.php` | API JSON para operações |

## 📁 Arquivos Principais

| Arquivo | Descrição |
|---------|-----------|
| `index.php` | Página principal + roteamento para `/app` |
| `app.php` | Interface do app de apostas (incluído via index.php) |
| `app_api.php` | API JSON com operações CRUD |
| `config.php` | Configuração do banco + migrations |

## 🗄️ Banco de Dados

### Tabelas de Apostas
- **bets**: Armazena informações das apostas
  - id, bet_date, odds, stake, result, profit, created_at
  
- **bet_selections**: Armazena seleções/competições de cada aposta
  - id, bet_id, comp, descr, sort_order

### Migrations Automáticas
Todas as tabelas são criadas automaticamente quando o sistema inicia (via `config.php`).

## 🔌 API Endpoints

Requisições para `/app_api.php` com `?action=`:

| Action | Método | Descrição |
|--------|--------|-----------|
| `list` | GET | Lista todas as apostas |
| `create` | POST | Cria nova aposta |
| `update` | POST | Atualiza aposta existente |
| `delete` | POST | Deleta uma aposta |

**Exemplo:**
```bash
curl "https://marcosmedeiros.page/app_api.php?action=list"
```

## ✨ Funcionalidades

✅ Dashboard com estatísticas gerais (lucro, investimento, winrate)
✅ Registro de apostas simples e múltiplas
✅ Edição e exclusão de registros
✅ Análise de performance por competição
✅ Fluxo de caixa diário
✅ Gráfico de evolução da banca
✅ Cálculo automático de lucro/prejuízo
✅ Validação de dados em tempo real

## 🚀 Tecnologias

- **Backend**: PHP 7.4+
- **Banco**: MySQL 5.7+
- **Frontend**: HTML5, CSS3 (TailwindCSS), JavaScript (Vanilla)
- **API**: JSON REST
- **Charting**: Chart.js

## 📝 Notas

- O app de apostas é totalmente integrado no sistema principal
- Dados persistidos em banco de dados MySQL
- Service Worker configurado para evitar cache em `/app` e `/app_api.php`
- Compatível com localhost (`/lifeos/`) e produção
- URLs amigáveis configuradas via `.htaccess`

## 📖 Documentação Adicional

- `MIGRATION.md` - Detalhes técnicos da integração
- `INTEGRATION_COMPLETE.md` - Resumo das mudanças
- `test_integration.php` - Script de validação

## 🔄 Arquitetura de Roteamento

```
Requisição HTTP
    ↓
.htaccess (rewrite rules)
    ↓
index.php (entry point)
    ├─ Detecta /app → inclui app.php
    ├─ Detecta /app_api.php → inclui app_api.php
    └─ Resto do sistema continua normalmente
```

