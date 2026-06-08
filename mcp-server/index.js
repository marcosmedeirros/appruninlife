import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

const API_BASE = "https://marcosmedeiros.page/api_lifeos.php";

async function apiGet(action, params = {}) {
  const url = new URL(API_BASE);
  url.searchParams.set("api", action);
  for (const [k, v] of Object.entries(params)) {
    url.searchParams.set(k, v);
  }
  const res = await fetch(url.toString());
  return res.json();
}

async function apiPost(action, body = {}) {
  const url = new URL(API_BASE);
  url.searchParams.set("api", action);
  const res = await fetch(url.toString(), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  return res.json();
}

const server = new McpServer({
  name: "vidaemcontrole",
  version: "1.0.0",
});

// ─── TAREFAS ──────────────────────────────────────────────────────────────────

server.tool(
  "listar_tarefas",
  "Lista todas as tarefas da semana com status de conclusao. Retorna tarefas recorrentes (semanais) e pontuais.",
  {},
  async () => {
    const data = await apiGet("tasks_list");
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "criar_tarefa",
  "Cria uma nova tarefa. Use recurrence='weekly' com recurrence_day (1=Dom,2=Seg,...,7=Sab) para tarefas semanais fixas, recurrence='once' com due_date para uma tarefa especifica desta semana, ou recurrence='once' sem due_date para tarefa sem data (aparece em 'A fazer').",
  {
    title: z.string().describe("Titulo da tarefa"),
    recurrence: z.enum(["weekly", "once"]).default("weekly").describe("Recorrencia: 'weekly' para toda semana, 'once' para unica vez"),
    recurrence_day: z.number().int().min(1).max(7).optional().describe("Dia da semana (1=Dom, 2=Seg, 3=Ter, 4=Qua, 5=Qui, 6=Sex, 7=Sab). Obrigatorio se recurrence='weekly'"),
    due_date: z.string().optional().describe("Data no formato YYYY-MM-DD. Usado quando recurrence='once' para uma data especifica"),
    color: z.string().optional().describe("Cor em hex, ex: '#10d9a0'"),
  },
  async ({ title, recurrence, recurrence_day, due_date, color }) => {
    const body = { title, recurrence };
    if (recurrence_day !== undefined) body.recurrence_day = recurrence_day;
    if (due_date) body.due_date = due_date;
    if (color) body.color = color;
    const data = await apiPost("task_save", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "atualizar_tarefa",
  "Atualiza uma tarefa existente pelo ID.",
  {
    id: z.number().int().describe("ID da tarefa a atualizar"),
    title: z.string().describe("Novo titulo"),
    recurrence: z.enum(["weekly", "once"]).optional(),
    recurrence_day: z.number().int().min(1).max(7).optional(),
    due_date: z.string().optional(),
    color: z.string().optional(),
  },
  async ({ id, title, recurrence, recurrence_day, due_date, color }) => {
    const body = { id, title };
    if (recurrence) body.recurrence = recurrence;
    if (recurrence_day !== undefined) body.recurrence_day = recurrence_day;
    if (due_date) body.due_date = due_date;
    if (color) body.color = color;
    const data = await apiPost("task_save", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "marcar_tarefa",
  "Marca ou desmarca uma tarefa como concluida. Para tarefas recorrentes passe a data do dia em questao.",
  {
    id: z.number().int().describe("ID da tarefa"),
    date: z.string().optional().describe("Data no formato YYYY-MM-DD (para tarefas recorrentes). Default: hoje"),
  },
  async ({ id, date }) => {
    const body = { id };
    if (date) body.date = date;
    const data = await apiPost("task_toggle", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "deletar_tarefa",
  "Remove uma tarefa permanentemente.",
  {
    id: z.number().int().describe("ID da tarefa a deletar"),
  },
  async ({ id }) => {
    const data = await apiPost("task_delete", { id });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ─── HABITOS ──────────────────────────────────────────────────────────────────

server.tool(
  "listar_habitos",
  "Lista todos os habitos com historico de conclusoes.",
  {},
  async () => {
    const data = await apiGet("habits_list");
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "criar_habito",
  "Cria ou atualiza um habito.",
  {
    name: z.string().describe("Nome do habito"),
    recurrence: z.enum(["daily", "weekly"]).default("daily").describe("'daily' para diario, 'weekly' para semanal"),
    recurrence_day: z.number().int().min(1).max(7).optional().describe("Dia da semana (1-7) se recurrence='weekly'"),
    id: z.number().int().optional().describe("ID para atualizar habito existente"),
  },
  async ({ name, recurrence, recurrence_day, id }) => {
    const body = { name, recurrence };
    if (id) body.id = id;
    if (recurrence_day !== undefined) body.recurrence_day = recurrence_day;
    const data = await apiPost("habit_save", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "marcar_habito",
  "Marca ou desmarca um habito como feito em uma data.",
  {
    id: z.number().int().describe("ID do habito"),
    date: z.string().optional().describe("Data no formato YYYY-MM-DD. Default: hoje"),
  },
  async ({ id, date }) => {
    const body = { id };
    if (date) body.date = date;
    const data = await apiPost("habit_toggle", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "deletar_habito",
  "Remove um habito.",
  {
    id: z.number().int().describe("ID do habito a deletar"),
  },
  async ({ id }) => {
    const data = await apiPost("habit_delete", { id });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ─── FINANCAS ─────────────────────────────────────────────────────────────────

server.tool(
  "listar_financas",
  "Lista transacoes financeiras de um mes.",
  {
    month: z.string().optional().describe("Mes no formato YYYY-MM, ex: '2026-06'. Default: mes atual"),
  },
  async ({ month }) => {
    const params = month ? { month } : {};
    const data = await apiGet("fin_transactions", params);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "resumo_financeiro",
  "Retorna resumo financeiro do mes: saldo atual, total de receitas e despesas.",
  {
    month: z.string().optional().describe("Mes no formato YYYY-MM. Default: mes atual"),
  },
  async ({ month }) => {
    const params = month ? { month } : {};
    const data = await apiGet("fin_summary", params);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "criar_transacao",
  "Registra uma nova transacao financeira (receita ou despesa).",
  {
    type: z.enum(["income", "expense"]).describe("Tipo: 'income' para receita, 'expense' para despesa"),
    amount: z.number().positive().describe("Valor em reais, ex: 150.00"),
    description: z.string().describe("Descricao da transacao"),
    transaction_date: z.string().describe("Data no formato YYYY-MM-DD"),
    category_id: z.number().int().optional().describe("ID da categoria (opcional)"),
  },
  async ({ type, amount, description, transaction_date, category_id }) => {
    const body = { type, amount, description, transaction_date };
    if (category_id) body.category_id = category_id;
    const data = await apiPost("fin_save", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "deletar_transacao",
  "Remove uma transacao financeira.",
  {
    id: z.number().int().describe("ID da transacao a deletar"),
  },
  async ({ id }) => {
    const data = await apiPost("fin_delete", { id });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "listar_categorias",
  "Lista categorias financeiras disponiveis.",
  {},
  async () => {
    const data = await apiGet("cats_list");
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ─── METAS ────────────────────────────────────────────────────────────────────

server.tool(
  "listar_metas",
  "Lista todas as metas/objetivos com progresso atual.",
  {},
  async () => {
    const data = await apiGet("goals_list");
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "criar_meta",
  "Cria ou atualiza uma meta/objetivo.",
  {
    title: z.string().describe("Titulo da meta"),
    target_value: z.number().optional().describe("Valor alvo (para metas financeiras), ex: 5000"),
    target_date: z.string().optional().describe("Data limite no formato YYYY-MM-DD"),
    id: z.number().int().optional().describe("ID para atualizar meta existente"),
  },
  async ({ title, target_value, target_date, id }) => {
    const body = { title };
    if (id) body.id = id;
    if (target_value !== undefined) body.target_value = target_value;
    if (target_date) body.target_date = target_date;
    const data = await apiPost("goal_save", body);
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "depositar_meta",
  "Adiciona um deposito/aporte a uma meta financeira.",
  {
    id: z.number().int().describe("ID da meta"),
    amount: z.number().positive().describe("Valor do deposito em reais"),
  },
  async ({ id, amount }) => {
    const data = await apiPost("goal_deposit", { id, amount });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "marcar_meta",
  "Marca ou desmarca uma meta como concluida.",
  {
    id: z.number().int().describe("ID da meta"),
  },
  async ({ id }) => {
    const data = await apiPost("goal_toggle", { id });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

server.tool(
  "deletar_meta",
  "Remove uma meta.",
  {
    id: z.number().int().describe("ID da meta a deletar"),
  },
  async ({ id }) => {
    const data = await apiPost("goal_delete", { id });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ─── START ────────────────────────────────────────────────────────────────────

const transport = new StdioServerTransport();
await server.connect(transport);
