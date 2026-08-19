/* ARQUIVO: assets/js/app.js
   Vida em Controle — remodelagem 2026.

   Arquitetura: o app carrega TUDO numa unica chamada (api_lifeos.php?api=bootstrap)
   e guarda em `S`. Trocar de aba nao faz request — so re-renderiza. Cada acao
   atualiza o estado local na hora (otimista) e sincroniza com o servidor em segundo
   plano, entao o app parece instantaneo mesmo em 4G ruim. */

(function () {
'use strict';

var API = 'api_lifeos.php';

/* ===================== ESTADO ===================== */

var S = null;                 // payload do bootstrap
var view = 'hoje';            // aba atual
var loading = true;
var month = null;             // AAAA-MM em foco nas Financas
var filterArea = 'todas';     // tarefas: todas | casa | trabalho | pessoal
var filterWhen = 'hoje';      // tarefas: hoje | semana | todas | feitas
var moreTab = 'habitos';      // aba interna de "Mais"
var touched = {};             // ids de tarefas mexidas nesta sessao (ficam visiveis em Hoje)

var AREAS = {
  casa:      { label: 'Casa',     icon: '\u{1F3E0}' },
  trabalho:  { label: 'Trabalho', icon: '\u{1F4BC}' },
  pessoal:   { label: 'Pessoal',  icon: '\u{1F464}' }
};

var WTYPES = {
  gym:   { label: 'Academia', icon: '\u{1F3CB}' },
  run:   { label: 'Corrida',  icon: '\u{1F3C3}' },
  other: { label: 'Outro',    icon: '⚡' },
  rest:  { label: 'Descanso', icon: '\u{1F634}' }
};

var DOW_SHORT = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
var DOW_LONG = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

/* ===================== UTILITÁRIOS ===================== */

function $(sel, root) { return (root || document).querySelector(sel); }

function esc(v) {
  return String(v === null || v === undefined ? '' : v)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

var BRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
function money(v) { return BRL.format(Number(v) || 0); }
function moneyShort(v) {
  var n = Number(v) || 0;
  var abs = Math.abs(n);
  if (abs >= 1000) return (n < 0 ? '-' : '') + 'R$ ' + (abs / 1000).toFixed(abs >= 10000 ? 0 : 1).replace('.', ',') + 'k';
  return money(n);
}

/* Datas sao sempre strings AAAA-MM-DD para nao cair em armadilha de fuso. */
function parseISO(iso) {
  var p = String(iso || '').split('-');
  return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
}
function toISO(d) {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
function addDays(iso, n) {
  var d = parseISO(iso);
  d.setDate(d.getDate() + n);
  return toISO(d);
}
/* 1 = segunda ... 7 = domingo (mesma convencao que o banco ja usa). */
function dowIndex(iso) {
  var j = parseISO(iso).getDay();
  return j === 0 ? 7 : j;
}
function weekStartISO(iso) { return addDays(iso, -(dowIndex(iso) - 1)); }

function today() { return S && S.today ? S.today : toISO(new Date()); }

function fmtLongDate(iso) {
  return parseISO(iso).toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
}
function fmtShortDate(iso) {
  return parseISO(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}
function fmtDayLabel(iso) {
  if (iso === today()) return 'Hoje';
  if (iso === addDays(today(), -1)) return 'Ontem';
  if (iso === addDays(today(), 1)) return 'Amanhã';
  return parseISO(iso).toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
}
function fmtMonth(ym) {
  var d = parseISO(ym + '-01');
  return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
}
function monthOf(iso) { return String(iso).slice(0, 7); }
function currentMonth() { return today().slice(0, 7); }

/* Um lancamento guarda uma data, mas so pedimos o mes: no mes corrente vale hoje,
   nos outros vale o dia 1. */
function dateForMonth(ym) { return ym === currentMonth() ? today() : ym + '-01'; }

function monthLabel(ym) {
  var d = parseISO(ym + '-01');
  var nome = d.toLocaleDateString('pt-BR', { month: 'long' });
  nome = nome.charAt(0).toUpperCase() + nome.slice(1);
  return d.getFullYear() === parseISO(today()).getFullYear() ? nome : nome + ' de ' + d.getFullYear();
}
function shiftMonth(ym, n) {
  var d = parseISO(ym + '-01');
  d.setMonth(d.getMonth() + n);
  return toISO(d).slice(0, 7);
}

/* ===================== REDE ===================== */

function api(action, opts) {
  opts = opts || {};
  var url = API + '?api=' + encodeURIComponent(action);
  if (opts.query) {
    Object.keys(opts.query).forEach(function (k) {
      url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(opts.query[k]);
    });
  }
  var init = { method: opts.body ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json' } };
  if (opts.body) init.body = JSON.stringify(opts.body);

  return fetch(url, init)
    .then(function (r) { return r.json(); })
    .catch(function () { return { ok: false, error: 'Sem conexão com o servidor.' }; })
    .then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.error) || 'Erro inesperado.');
      return res.data;
    });
}

/* Executa a acao no servidor; se falhar, avisa e recarrega para nao ficar
   com a tela mentindo sobre o que foi salvo. */
function push(action, body, okMsg) {
  return api(action, { body: body })
    .then(function (data) {
      if (okMsg) toast(okMsg);
      return data;
    })
    .catch(function (e) {
      toast(e.message, true);
      return reload().then(function () { throw e; });
    });
}

function reload(opts) {
  opts = opts || {};
  return api('bootstrap', { query: { month: month || monthOf(toISO(new Date())) } })
    .then(function (data) {
      S = data;
      month = data.month;
      loading = false;
      if (!opts.silent) render();
      return data;
    })
    .catch(function (e) {
      loading = false;
      renderError(e.message);
      throw e;
    });
}

/* ===================== AVISOS ===================== */

function toast(msg, isError) {
  var wrap = $('#toasts');
  var el = document.createElement('div');
  el.className = 'toast' + (isError ? ' err' : '');
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(function () {
    el.style.opacity = '0';
    el.style.transition = 'opacity .2s';
    setTimeout(function () { el.remove(); }, 220);
  }, isError ? 3600 : 1900);
}

/* ===================== MODAL ===================== */

var modalSubmit = null;

function openModal(cfg) {
  var ov = $('#overlay');
  ov.innerHTML =
    '<div class="modal" role="dialog" aria-modal="true">' +
      '<div class="modal-title">' + esc(cfg.title) + '</div>' +
      '<form id="modalForm" autocomplete="off">' +
        cfg.body +
        '<div class="modal-actions">' +
          '<button type="button" class="btn btn-ghost" data-act="close-modal">Cancelar</button>' +
          '<button type="submit" class="btn btn-primary">' + esc(cfg.submitLabel || 'Salvar') + '</button>' +
        '</div>' +
      '</form>' +
    '</div>';
  ov.classList.add('open');
  modalSubmit = cfg.onSubmit;

  $('#modalForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (modalSubmit) modalSubmit(ov);
  });

  var monthSel = ov.querySelector('#f-month');
  if (monthSel) {
    monthSel.addEventListener('change', function () {
      [].slice.call(ov.querySelectorAll('[data-act="pick-month"]')).forEach(function (b) {
        b.classList.toggle('on', b.dataset.val === monthSel.value);
      });
    });
  }

  var first = ov.querySelector('[data-autofocus]');
  if (first) setTimeout(function () { first.focus(); }, 60);
}

function closeModal() {
  var ov = $('#overlay');
  ov.classList.remove('open');
  ov.innerHTML = '';
  modalSubmit = null;
}

function confirmDialog(message, confirmLabel) {
  return new Promise(function (resolve) {
    openModal({
      title: 'Confirmar',
      body: '<p style="color:var(--text-2);font-size:14.5px;line-height:1.5">' + esc(message) + '</p>',
      submitLabel: confirmLabel || 'Excluir',
      onSubmit: function () { closeModal(); resolve(true); }
    });
    $('#overlay').addEventListener('click', function handler(e) {
      if (e.target.dataset.act === 'close-modal' || e.target.id === 'overlay') {
        $('#overlay').removeEventListener('click', handler);
        resolve(false);
      }
    });
  });
}

/* Le o valor selecionado de um grupo de botoes .opt */
function optValue(group, root) {
  var el = (root || document).querySelector('[data-opt-group="' + group + '"] .opt.on');
  return el ? el.dataset.optValue : null;
}

function optGroup(group, options, selected) {
  return '<div class="opts" data-opt-group="' + group + '">' +
    options.map(function (o) {
      return '<button type="button" class="opt' + (o.value === selected ? ' on' : '') + '" ' +
        'data-act="opt" data-opt-value="' + esc(o.value) + '">' + o.label + '</button>';
    }).join('') + '</div>';
}

/* ===================== REGRAS DE DOMÍNIO ===================== */

/* A tarefa aparece na data informada? */
function taskDueOn(t, iso) {
  var rec = t.recurrence || 'once';
  if (rec === 'daily') return true;
  if (rec === 'weekly') return Number(t.recurrence_day || 0) === dowIndex(iso);
  if (rec === 'monthly') return Number(t.recurrence_day || 0) === parseISO(iso).getDate();
  return !!t.due_date && String(t.due_date).slice(0, 10) === iso;
}

function taskDoneOn(t, iso) {
  if ((t.recurrence || 'once') === 'once') return Number(t.status) === 1;
  var dates = String(t.done_dates || '').split(',');
  return dates.indexOf(iso) !== -1;
}

/* Depois de uma semana, tarefa avulsa (que nao se repete) sai da interface:
   concluida ha mais de 7 dias, ou vencida ha mais de 7 dias sem ninguem tocar.
   Nada e apagado — continua tudo no banco. */
var STALE_DAYS = 7;

function taskIsStale(t) {
  if ((t.recurrence || 'once') !== 'once') return false;
  if (touched[t.id]) return false;             // mexeu agora: fica visivel

  var limit = addDays(today(), -STALE_DAYS);

  if (Number(t.status) === 1) {
    // Tarefas antigas, concluidas antes de existir o carimbo de data, usam a
    // data prevista como referencia; sem nenhuma das duas, ja sao velhas.
    var ref = t.completed_at ? String(t.completed_at).slice(0, 10)
            : (t.due_date ? String(t.due_date).slice(0, 10) : null);
    return ref === null || ref < limit;
  }

  return !!t.due_date && String(t.due_date).slice(0, 10) < limit;
}

/* Tarefa avulsa com data passada e ainda em aberto. */
function taskIsLate(t) {
  return (t.recurrence || 'once') === 'once' &&
    !!t.due_date &&
    String(t.due_date).slice(0, 10) < today() &&
    Number(t.status) !== 1;
}

/* Tarefa avulsa sem data nenhuma — fica na "caixa de entrada". */
function taskIsInbox(t) {
  return (t.recurrence || 'once') === 'once' && !t.due_date && Number(t.status) !== 1;
}

function habitDueOn(h, iso) {
  if ((h.recurrence || 'daily') === 'weekly') return Number(h.recurrence_day || 0) === dowIndex(iso);
  return true;
}

function habitChecked(h, iso) {
  var arr;
  try { arr = JSON.parse(h.checked_dates || '[]'); } catch (e) { arr = []; }
  return Array.isArray(arr) && arr.indexOf(iso) !== -1;
}

function planFor(iso) {
  var wd = dowIndex(iso);
  var found = (S.workout_plan || []).filter(function (p) { return Number(p.weekday) === wd; })[0];
  return found || { weekday: wd, name: null, type: 'rest' };
}

function workoutLogFor(iso) {
  return (S.workout_logs || []).filter(function (w) {
    return String(w.workout_date).slice(0, 10) === iso;
  })[0] || null;
}

function workoutDoneOn(iso) {
  var log = workoutLogFor(iso);
  return !!(log && Number(log.done) === 1);
}

/* Tudo que precisa de atencao hoje, numa lista so. */
function todayItems() {
  var iso = today();
  var out = [];

  (S.tasks || []).forEach(function (t) {
    if (taskIsStale(t)) return;
    var due = taskDueOn(t, iso);
    var late = taskIsLate(t);
    // "touched" mantem na lista a tarefa atrasada que acabou de ser concluida,
    // senao ela sumiria no mesmo instante do clique.
    if (due || late || touched[t.id]) {
      out.push({
        kind: 'task', id: t.id, title: t.title, area: t.area || 'pessoal',
        done: taskDoneOn(t, iso), late: late, ref: t
      });
    }
  });

  (S.habits || []).forEach(function (h) {
    if (habitDueOn(h, iso)) {
      out.push({ kind: 'habit', id: h.id, title: h.name, done: habitChecked(h, iso), ref: h });
    }
  });

  var plan = planFor(iso);
  if (plan.type !== 'rest') {
    out.push({
      kind: 'workout',
      id: 0,
      title: plan.name || WTYPES[plan.type].label,
      done: workoutDoneOn(iso),
      ref: plan
    });
  }

  // Atrasadas primeiro, concluidas por ultimo.
  return out.sort(function (a, b) {
    if (a.done !== b.done) return a.done ? 1 : -1;
    if (!!a.late !== !!b.late) return a.late ? -1 : 1;
    return 0;
  });
}

function dayProgress() {
  var items = todayItems();
  var done = items.filter(function (i) { return i.done; }).length;
  return { done: done, total: items.length, pct: items.length ? Math.round(done / items.length * 100) : 0 };
}

function pendingCount() {
  return todayItems().filter(function (i) { return !i.done; }).length;
}

/* ===================== COMPONENTES ===================== */

function ring(pct) {
  var r = 34, c = 2 * Math.PI * r;
  var dash = (Math.max(0, Math.min(100, pct)) / 100) * c;
  return '<div class="ring">' +
    '<svg width="84" height="84" viewBox="0 0 84 84">' +
      '<circle class="ring-bg" cx="42" cy="42" r="' + r + '" fill="none" stroke-width="7"/>' +
      '<circle class="ring-fg" cx="42" cy="42" r="' + r + '" fill="none" stroke-width="7" ' +
        'stroke-dasharray="' + dash.toFixed(1) + ' ' + c.toFixed(1) + '"/>' +
    '</svg>' +
    '<div class="ring-val">' + pct + '<span style="font-size:11px">%</span></div>' +
  '</div>';
}

function areaChip(area) {
  var a = AREAS[area] || AREAS.pessoal;
  return '<span class="chip chip-' + area + '">' + a.icon + ' ' + a.label + '</span>';
}

function checkbox(on, act, data) {
  var attrs = Object.keys(data || {}).map(function (k) { return 'data-' + k + '="' + esc(data[k]) + '"'; }).join(' ');
  return '<button class="check' + (on ? ' on' : '') + '" data-act="' + act + '" ' + attrs +
    ' aria-label="' + (on ? 'Desmarcar' : 'Marcar') + '">✓</button>';
}

function emptyState(msg) { return '<div class="list-empty">' + esc(msg) + '</div>'; }

/* ===================== ABA: HOJE ===================== */

function viewHoje() {
  var iso = today();
  var p = dayProgress();
  var items = todayItems();
  var fin = S.finance;
  var hour = new Date().getHours();
  var greet = hour < 5 ? 'Boa madrugada' : hour < 12 ? 'Bom dia' : hour < 18 ? 'Boa tarde' : 'Boa noite';

  var spentToday = (fin.transactions || [])
    .filter(function (t) { return t.type === 'expense' && String(t.transaction_date).slice(0, 10) === iso; })
    .reduce(function (a, t) { return a + Number(t.amount); }, 0);

  var ws = weekStartISO(iso);
  var workoutsWeek = 0, plannedWeek = 0;
  for (var i = 0; i < 7; i++) {
    var d = addDays(ws, i);
    if (planFor(d).type !== 'rest') plannedWeek++;
    if (workoutDoneOn(d)) workoutsWeek++;
  }

  var listHTML = items.length ? items.map(function (it) {
    var meta = '';
    if (it.kind === 'task') {
      meta = areaChip(it.area) + (it.late ? '<span class="chip chip-late">atrasada</span>' : '');
    } else if (it.kind === 'habit') {
      meta = '<span class="chip">↻ Hábito</span>';
    } else {
      meta = '<span class="chip chip-accent">' + WTYPES[it.ref.type].icon + ' Treino</span>';
    }
    var act = it.kind === 'task' ? 'toggle-task' : it.kind === 'habit' ? 'toggle-habit' : 'toggle-workout';
    return '<div class="item' + (it.done ? ' done' : '') + '">' +
      checkbox(it.done, act, { id: it.id, date: iso }) +
      '<div class="item-body">' +
        '<div class="item-title">' + esc(it.title) + '</div>' +
        '<div class="item-meta">' + meta + '</div>' +
      '</div>' +
    '</div>';
  }).join('') : emptyState('Nada pendente hoje. Aproveite. \u{1F389}');

  var inbox = (S.tasks || []).filter(function (t) { return taskIsInbox(t) && !taskIsStale(t); });

  return '' +
  '<div class="hero">' +
    '<div class="hero-info">' +
      '<div class="hero-hi">' + greet + ', Marcos</div>' +
      '<div class="hero-date">' + esc(fmtLongDate(iso)) + '</div>' +
      '<div class="hero-line">' +
        (p.total
          ? '<b>' + p.done + ' de ' + p.total + '</b> concluídos hoje'
          : 'Dia livre — nenhum item programado') +
      '</div>' +
    '</div>' +
    ring(p.pct) +
  '</div>' +

  '<div class="quick">' +
    '<button data-act="new-task"><span class="qi">➕</span>Tarefa</button>' +
    '<button data-act="new-expense"><span class="qi">\u{1F4B8}</span>Gasto</button>' +
    '<button data-act="new-income"><span class="qi">\u{1F4B0}</span>Entrada</button>' +
    '<button data-act="go-treinos"><span class="qi">\u{1F3CB}</span>Treino</button>' +
  '</div>' +

  '<div class="grid grid-hoje">' +
    '<div class="stack">' +
      '<div class="card">' +
        '<div class="card-head">' +
          '<div class="card-title">Foco de hoje</div>' +
          '<button class="btn btn-ghost" data-act="new-task" style="padding:6px 12px;font-size:13px">+ Tarefa</button>' +
        '</div>' +
        '<div class="list">' + listHTML + '</div>' +
      '</div>' +

      (inbox.length ? '<div class="card">' +
        '<div class="card-head"><div class="card-title">Sem data (' + inbox.length + ')</div></div>' +
        '<div class="list">' + inbox.slice(0, 5).map(function (t) {
          return '<div class="item">' +
            checkbox(false, 'toggle-task', { id: t.id, date: iso }) +
            '<div class="item-body">' +
              '<div class="item-title">' + esc(t.title) + '</div>' +
              '<div class="item-meta">' + areaChip(t.area || 'pessoal') + '</div>' +
            '</div>' +
            '<button class="icon-btn" data-act="schedule-today" data-id="' + t.id + '" title="Puxar para hoje">↓</button>' +
          '</div>';
        }).join('') + '</div>' +
      '</div>' : '') +
    '</div>' +

    '<div class="stack">' +
      '<div class="grid grid-stats-3" style="grid-template-columns:repeat(3,1fr)">' +
        '<div class="stat"><div class="stat-label">Saldo</div>' +
          '<div class="stat-value ' + (fin.balance >= 0 ? 'pos' : 'neg') + '">' + moneyShort(fin.balance) + '</div></div>' +
        '<div class="stat"><div class="stat-label">Gasto hoje</div>' +
          '<div class="stat-value">' + moneyShort(spentToday) + '</div></div>' +
        '<div class="stat"><div class="stat-label">Treinos</div>' +
          '<div class="stat-value">' + workoutsWeek + '<span style="font-size:14px;color:var(--text-3)">/' + plannedWeek + '</span></div>' +
          '<div class="stat-hint">nesta semana</div></div>' +
      '</div>' +

      '<div class="card">' +
        '<div class="card-head"><div class="card-title">Últimos lançamentos</div>' +
          '<button class="btn btn-ghost" data-act="go-financas" style="padding:6px 12px;font-size:13px">Ver tudo</button></div>' +
        '<div class="list">' +
          ((fin.transactions || []).length
            ? fin.transactions.slice(0, 5).map(txRow).join('')
            : emptyState('Nenhum lançamento neste mês.')) +
        '</div>' +
      '</div>' +

      '<div class="card">' +
        '<div class="card-head"><div class="card-title">Nota do dia</div></div>' +
        '<textarea class="input" id="noteToday" rows="4" placeholder="Como foi o dia? O que ficou pendente?">' +
          esc(S.note_today || '') + '</textarea>' +
        '<div style="font-size:11.5px;color:var(--text-3);margin-top:7px" id="noteStatus">Salva sozinho.</div>' +
      '</div>' +
    '</div>' +
  '</div>';
}

/* ===================== ABA: TAREFAS ===================== */

function tasksFiltered() {
  var iso = today();
  var list = (S.tasks || []).filter(function (t) { return !taskIsStale(t); });

  if (filterArea !== 'todas') {
    list = list.filter(function (t) { return (t.area || 'pessoal') === filterArea; });
  }

  if (filterWhen === 'hoje') {
    list = list.filter(function (t) { return (taskDueOn(t, iso) && !taskDoneOn(t, iso)) || taskIsLate(t) || taskIsInbox(t); });
  } else if (filterWhen === 'semana') {
    var ws = weekStartISO(iso);
    list = list.filter(function (t) {
      if (taskIsLate(t) || taskIsInbox(t)) return true;
      for (var i = 0; i < 7; i++) {
        if (taskDueOn(t, addDays(ws, i))) return true;
      }
      return false;
    });
  } else if (filterWhen === 'feitas') {
    list = list.filter(function (t) { return taskDoneOn(t, iso); });
  }

  return list;
}

function taskRow(t) {
  var iso = today();
  var done = taskDoneOn(t, iso);
  var late = taskIsLate(t);
  var when = '';
  var rec = t.recurrence || 'once';
  if (rec === 'daily') when = '<span class="chip">Todo dia</span>';
  else if (rec === 'weekly') when = '<span class="chip">Toda ' + DOW_LONG[Number(t.recurrence_day || 1) - 1] + '</span>';
  else if (rec === 'monthly') when = '<span class="chip">Dia ' + Number(t.recurrence_day || 1) + '</span>';
  else if (t.due_date) {
    var d = String(t.due_date).slice(0, 10);
    when = '<span class="chip' + (late ? ' chip-late' : '') + '">' + esc(fmtDayLabel(d)) + '</span>';
  } else when = '<span class="chip">Sem data</span>';

  return '<div class="item' + (done ? ' done' : '') + '">' +
    checkbox(done, 'toggle-task', { id: t.id, date: iso }) +
    '<div class="item-body">' +
      '<div class="item-title">' + (Number(t.priority) ? '★ ' : '') + esc(t.title) + '</div>' +
      '<div class="item-meta">' + areaChip(t.area || 'pessoal') + when + '</div>' +
    '</div>' +
    '<button class="icon-btn" data-act="edit-task" data-id="' + t.id + '" title="Editar">✎</button>' +
    '<button class="icon-btn danger" data-act="del-task" data-id="' + t.id + '" title="Excluir">✕</button>' +
  '</div>';
}

function viewTarefas() {
  var list = tasksFiltered();
  var byArea = { casa: [], trabalho: [], pessoal: [] };
  list.forEach(function (t) { (byArea[t.area] || byArea.pessoal).push(t); });

  var counts = {};
  ['casa', 'trabalho', 'pessoal'].forEach(function (a) {
    counts[a] = (S.tasks || []).filter(function (t) {
      return (t.area || 'pessoal') === a && !taskIsStale(t) &&
        ((taskDueOn(t, today()) && !taskDoneOn(t, today())) || taskIsLate(t));
    }).length;
  });

  var body;
  if (!list.length) {
    body = '<div class="card">' + emptyState(
      filterWhen === 'feitas' ? 'Nenhuma tarefa concluída hoje.' : 'Nenhuma tarefa por aqui. Que tal criar uma?'
    ) + '</div>';
  } else if (filterArea === 'todas') {
    body = ['casa', 'trabalho', 'pessoal'].filter(function (a) { return byArea[a].length; }).map(function (a) {
      return '<div class="card">' +
        '<div class="card-head"><div class="card-title">' + AREAS[a].icon + ' ' + AREAS[a].label +
          ' <span style="color:var(--text-3)">(' + byArea[a].length + ')</span></div></div>' +
        '<div class="list">' + byArea[a].map(taskRow).join('') + '</div>' +
      '</div>';
    }).join('');
  } else {
    body = '<div class="card"><div class="list">' + list.map(taskRow).join('') + '</div></div>';
  }

  return '' +
  '<div class="page-head">' +
    '<div><div class="page-title">Tarefas</div>' +
    '<div class="page-sub">' + list.length + ' ' + (list.length === 1 ? 'item' : 'itens') + ' no filtro atual</div></div>' +
    '<button class="btn btn-primary" data-act="new-task">+ Nova tarefa</button>' +
  '</div>' +

  '<div class="filters">' +
    '<div class="segment" style="margin-right:6px">' +
      [['hoje', 'Hoje'], ['semana', 'Semana'], ['todas', 'Todas'], ['feitas', 'Feitas']].map(function (o) {
        return '<button data-act="filter-when" data-val="' + o[0] + '" class="' + (filterWhen === o[0] ? 'on' : '') + '">' + o[1] + '</button>';
      }).join('') +
    '</div>' +
    '<button class="fchip' + (filterArea === 'todas' ? ' on' : '') + '" data-act="filter-area" data-val="todas">Todas</button>' +
    ['casa', 'trabalho', 'pessoal'].map(function (a) {
      return '<button class="fchip' + (filterArea === a ? ' on' : '') + '" data-act="filter-area" data-val="' + a + '">' +
        AREAS[a].icon + ' ' + AREAS[a].label + (counts[a] ? ' · ' + counts[a] : '') + '</button>';
    }).join('') +
  '</div>' +

  '<div class="stack">' + body + '</div>' +

  '<button class="btn btn-primary btn-block btn-lg" data-act="new-task" style="margin-top:16px">+ Nova tarefa</button>';
}

/* ===================== ABA: FINANÇAS ===================== */

function txRow(t) {
  var isIn = t.type === 'income';
  return '<div class="item item-tx">' +
    '<div style="flex:0 0 32px;height:32px;border-radius:10px;display:grid;place-items:center;font-size:15px;' +
      'background:' + (isIn ? 'var(--accent-soft)' : 'var(--surface-3)') + '">' + (isIn ? '↑' : '↓') + '</div>' +
    '<div class="item-body">' +
      '<div class="item-title">' + esc(t.description || t.cat_name || (isIn ? 'Entrada' : 'Gasto')) + '</div>' +
      '<div class="item-meta">' +
        '<span class="chip">' + esc(fmtShortDate(String(t.transaction_date).slice(0, 10))) + '</span>' +
        (t.cat_name && t.description ? '<span class="chip">' + esc(t.cat_name) + '</span>' : '') +
      '</div>' +
    '</div>' +
    '<div class="amount ' + (isIn ? 'in' : 'out') + '">' + (isIn ? '+' : '−') + ' ' + money(t.amount) + '</div>' +
    '<button class="icon-btn" data-act="edit-tx" data-id="' + t.id + '" title="Editar">✎</button>' +
    '<button class="icon-btn danger" data-act="del-tx" data-id="' + t.id + '" title="Excluir">✕</button>' +
  '</div>';
}

function viewFinancas() {
  var fin = S.finance;
  var txs = fin.transactions || [];

  // Agrupa gastos por categoria para o "onde foi o dinheiro".
  var byCat = {};
  txs.filter(function (t) { return t.type === 'expense'; }).forEach(function (t) {
    var key = t.cat_name || 'Sem categoria';
    byCat[key] = (byCat[key] || 0) + Number(t.amount);
  });
  var cats = Object.keys(byCat).map(function (k) { return { name: k, total: byCat[k] }; })
    .sort(function (a, b) { return b.total - a.total; }).slice(0, 6);
  var maxCat = cats.length ? cats[0].total : 0;

  // Agrupa lancamentos por dia.
  var groups = [];
  var lastDate = null;
  txs.forEach(function (t) {
    var d = String(t.transaction_date).slice(0, 10);
    if (d !== lastDate) { groups.push({ date: d, items: [] }); lastDate = d; }
    groups[groups.length - 1].items.push(t);
  });

  return '' +
  '<div class="page-head">' +
    '<div><div class="page-title">Finanças</div>' +
    '<div class="page-sub">' + txs.length + ' lançamentos no mês</div></div>' +
    '<div style="display:flex;gap:8px">' +
      '<button class="btn" data-act="new-income">+ Entrada</button>' +
      '<button class="btn btn-primary" data-act="new-expense">+ Gasto</button>' +
    '</div>' +
  '</div>' +

  '<div class="card" style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;margin-bottom:14px">' +
    '<button class="icon-btn" data-act="month" data-val="-1" aria-label="Mês anterior">‹</button>' +
    '<div class="cap" style="font-weight:600;text-transform:lowercase">' + esc(fmtMonth(month)) + '</div>' +
    '<button class="icon-btn" data-act="month" data-val="1" aria-label="Próximo mês">›</button>' +
  '</div>' +

  '<div class="grid grid-stats-3" style="grid-template-columns:repeat(3,1fr);margin-bottom:14px">' +
    '<div class="stat"><div class="stat-label">Saldo</div>' +
      '<div class="stat-value ' + (fin.balance >= 0 ? 'pos' : 'neg') + '">' + moneyShort(fin.balance) + '</div>' +
      '<div class="stat-hint">iniciou com ' + moneyShort(fin.initial_balance) + '</div></div>' +
    '<div class="stat"><div class="stat-label">Entradas</div>' +
      '<div class="stat-value pos">' + moneyShort(fin.income) + '</div></div>' +
    '<div class="stat"><div class="stat-label">Saídas</div>' +
      '<div class="stat-value neg">' + moneyShort(fin.expense) + '</div></div>' +
  '</div>' +

  (cats.length ? '<div class="card">' +
    '<div class="card-head"><div class="card-title">Onde foi o dinheiro</div></div>' +
    cats.map(function (c) {
      return '<div class="bar-row">' +
        '<div class="bar-top"><span>' + esc(c.name) + '</span><span>' + money(c.total) + '</span></div>' +
        '<div class="bar-track"><div class="bar-fill" style="width:' +
          (maxCat ? Math.max(4, c.total / maxCat * 100) : 0) + '%;background:var(--red)"></div></div>' +
      '</div>';
    }).join('') +
  '</div>' : '') +

  '<div class="card" style="margin-top:14px">' +
    '<div class="card-head"><div class="card-title">Lançamentos</div>' +
      '<button class="btn btn-ghost" data-act="fin-settings" style="padding:6px 12px;font-size:13px">Saldo inicial</button></div>' +
    (groups.length ? groups.map(function (g) {
      return '<div class="day-group">' +
        '<div class="day-label">' + esc(fmtDayLabel(g.date)) + '</div>' +
        '<div class="list">' + g.items.map(txRow).join('') + '</div>' +
      '</div>';
    }).join('') : emptyState('Nenhum lançamento neste mês.')) +
  '</div>' +

  '<div style="display:flex;gap:10px;margin-top:16px">' +
    '<button class="btn btn-block btn-lg" data-act="new-income">+ Entrada</button>' +
    '<button class="btn btn-primary btn-block btn-lg" data-act="new-expense">+ Gasto</button>' +
  '</div>';
}

/* ===================== ABA: TREINOS ===================== */

function pace(km, min) {
  if (!km || !min) return '—';
  var p = min / km;
  var m = Math.floor(p);
  var s = Math.round((p - m) * 60);
  if (s === 60) { m++; s = 0; }
  return m + "'" + String(s).padStart(2, '0') + '"/km';
}

function viewTreinos() {
  var iso = today();
  var plan = planFor(iso);
  var done = workoutDoneOn(iso);
  var ws = weekStartISO(iso);

  var weekHTML = '';
  for (var i = 0; i < 7; i++) {
    var d = addDays(ws, i);
    var p = planFor(d);
    var isDone = workoutDoneOn(d);
    weekHTML += '<div class="wday' + (d === iso ? ' today' : '') + '">' +
      '<div class="wday-label">' + DOW_SHORT[i] + '</div>' +
      '<button class="wday-dot' + (isDone ? ' done' : '') + (p.type === 'rest' ? ' rest' : '') + '" ' +
        'data-act="toggle-workout" data-date="' + d + '" title="' + esc(p.name || WTYPES[p.type].label) + '">' +
        (isDone ? '✓' : WTYPES[p.type].icon) +
      '</button>' +
    '</div>';
  }

  // Estatisticas do mes corrente.
  var mo = monthOf(iso);
  var doneMonth = (S.workout_logs || []).filter(function (w) {
    return Number(w.done) === 1 && monthOf(String(w.workout_date).slice(0, 10)) === mo;
  }).length;
  var runsMonth = (S.runs || []).filter(function (r) { return monthOf(String(r.run_date).slice(0, 10)) === mo; });
  var kmMonth = runsMonth.reduce(function (a, r) { return a + Number(r.distance_km || 0); }, 0);
  var minMonth = runsMonth.reduce(function (a, r) { return a + Number(r.duration_min || 0); }, 0);

  return '' +
  '<div class="page-head">' +
    '<div><div class="page-title">Treinos</div>' +
    '<div class="page-sub">' + doneMonth + ' treinos em ' + esc(fmtMonth(mo)) + '</div></div>' +
    '<button class="btn" data-act="edit-plan">Editar plano</button>' +
  '</div>' +

  '<div class="stack">' +

  '<div class="card">' +
    '<div class="today-workout">' +
      '<div class="today-workout-info">' +
        '<div class="card-title" style="margin-bottom:6px">Hoje · ' + DOW_LONG[dowIndex(iso) - 1] + '</div>' +
        '<div class="today-workout-name">' +
          (plan.type === 'rest' ? 'Dia de descanso' : esc(plan.name || WTYPES[plan.type].label)) +
        '</div>' +
        '<div class="today-workout-type">' + WTYPES[plan.type].icon + ' ' + WTYPES[plan.type].label + '</div>' +
      '</div>' +
      '<button class="btn ' + (done ? '' : 'btn-primary') + ' btn-lg" data-act="toggle-workout" data-date="' + iso + '">' +
        (done ? '✓ Treino feito' : (plan.type === 'rest' ? 'Treinei mesmo assim' : 'Marcar como feito')) +
      '</button>' +
    '</div>' +
  '</div>' +

  '<div class="card">' +
    '<div class="card-head"><div class="card-title">Sua semana</div></div>' +
    '<div class="week">' + weekHTML + '</div>' +
  '</div>' +

  '<div class="grid grid-stats-3" style="grid-template-columns:repeat(3,1fr)">' +
    '<div class="stat"><div class="stat-label">Treinos</div><div class="stat-value">' + doneMonth + '</div>' +
      '<div class="stat-hint">no mês</div></div>' +
    '<div class="stat"><div class="stat-label">Distância</div>' +
      '<div class="stat-value">' + kmMonth.toFixed(1).replace('.', ',') + '<span style="font-size:13px;color:var(--text-3)"> km</span></div>' +
      '<div class="stat-hint">' + runsMonth.length + ' corridas</div></div>' +
    '<div class="stat"><div class="stat-label">Pace médio</div>' +
      '<div class="stat-value" style="font-size:19px">' + pace(kmMonth, minMonth) + '</div></div>' +
  '</div>' +

  '<div class="card">' +
    '<div class="card-head"><div class="card-title">Corridas</div>' +
      '<button class="btn btn-ghost" data-act="new-run" style="padding:6px 12px;font-size:13px">+ Registrar</button></div>' +
    '<div class="list">' +
      ((S.runs || []).length ? S.runs.slice(0, 12).map(function (r) {
        return '<div class="item">' +
          '<div style="flex:0 0 32px;height:32px;border-radius:10px;display:grid;place-items:center;' +
            'background:var(--accent-soft)">\u{1F3C3}</div>' +
          '<div class="item-body">' +
            '<div class="item-title">' + Number(r.distance_km).toFixed(2).replace('.', ',') + ' km' +
              (Number(r.duration_min) ? ' · ' + r.duration_min + ' min' : '') + '</div>' +
            '<div class="item-meta">' +
              '<span class="chip">' + esc(fmtDayLabel(String(r.run_date).slice(0, 10))) + '</span>' +
              '<span class="chip chip-accent">' + pace(Number(r.distance_km), Number(r.duration_min)) + '</span>' +
              (r.notes ? '<span class="chip">' + esc(r.notes) + '</span>' : '') +
            '</div>' +
          '</div>' +
          '<button class="icon-btn danger" data-act="del-run" data-id="' + r.id + '" title="Excluir">✕</button>' +
        '</div>';
      }).join('') : emptyState('Nenhuma corrida registrada ainda.')) +
    '</div>' +
  '</div>' +

  '</div>' +

  '<button class="btn btn-primary btn-block btn-lg" data-act="new-run" style="margin-top:16px">+ Registrar corrida</button>';
}

/* ===================== ABA: MAIS ===================== */

function viewMais() {
  var iso = today();
  var tabs = [['habitos', 'Hábitos'], ['metas', 'Metas'], ['notas', 'Anotações'], ['ajustes', 'Ajustes']];
  var body = '';

  if (moreTab === 'habitos') {
    body = '<div class="card">' +
      '<div class="card-head"><div class="card-title">Hábitos</div>' +
        '<button class="btn btn-ghost" data-act="new-habit" style="padding:6px 12px;font-size:13px">+ Novo</button></div>' +
      '<div class="list">' +
        ((S.habits || []).length ? S.habits.map(function (h) {
          var checked = habitChecked(h, iso);
          var due = habitDueOn(h, iso);
          // Sequencia atual: quantos dias seguidos ate hoje.
          var arr; try { arr = JSON.parse(h.checked_dates || '[]'); } catch (e) { arr = []; }
          var streak = 0, cur = iso;
          while (arr.indexOf(cur) !== -1) { streak++; cur = addDays(cur, -1); }
          return '<div class="item' + (checked ? ' done' : '') + '">' +
            checkbox(checked, 'toggle-habit', { id: h.id, date: iso }) +
            '<div class="item-body">' +
              '<div class="item-title">' + esc(h.name) + '</div>' +
              '<div class="item-meta">' +
                '<span class="chip">' + (h.recurrence === 'weekly'
                  ? 'Toda ' + DOW_LONG[Number(h.recurrence_day || 1) - 1] : 'Todo dia') + '</span>' +
                (streak > 1 ? '<span class="chip chip-accent">\u{1F525} ' + streak + ' dias</span>' : '') +
                (!due ? '<span class="chip">fora do dia</span>' : '') +
              '</div>' +
            '</div>' +
            '<button class="icon-btn danger" data-act="del-habit" data-id="' + h.id + '">✕</button>' +
          '</div>';
        }).join('') : emptyState('Nenhum hábito cadastrado.')) +
      '</div>' +
    '</div>';

  } else if (moreTab === 'metas') {
    body = '<div class="card">' +
      '<div class="card-head"><div class="card-title">Metas</div>' +
        '<button class="btn btn-ghost" data-act="new-goal" style="padding:6px 12px;font-size:13px">+ Nova</button></div>' +
      ((S.goals || []).length ? S.goals.map(function (g) {
        var target = Number(g.target_amount) || 0;
        var cur = Number(g.current_amount) || 0;
        var pct = target > 0 ? Math.min(100, Math.round(cur / target * 100)) : (Number(g.status) ? 100 : 0);
        return '<div class="bar-row" style="padding:10px 0;border-bottom:1px solid var(--border)">' +
          '<div class="bar-top">' +
            '<span style="font-weight:600' + (Number(g.status) ? ';text-decoration:line-through;color:var(--text-3)' : '') + '">' +
              esc(g.title) + '</span>' +
            '<span>' + (target > 0 ? money(cur) + ' / ' + money(target) : (Number(g.status) ? 'Concluída' : 'Em aberto')) + '</span>' +
          '</div>' +
          '<div class="bar-track"><div class="bar-fill" style="width:' + pct + '%"></div></div>' +
          '<div style="display:flex;gap:6px;margin-top:8px">' +
            (target > 0 ? '<button class="btn btn-ghost" data-act="deposit-goal" data-id="' + g.id +
              '" style="padding:5px 11px;font-size:12.5px">+ Depositar</button>' : '') +
            '<button class="btn btn-ghost" data-act="toggle-goal" data-id="' + g.id +
              '" style="padding:5px 11px;font-size:12.5px">' + (Number(g.status) ? 'Reabrir' : 'Concluir') + '</button>' +
            '<button class="btn btn-ghost btn-danger" data-act="del-goal" data-id="' + g.id +
              '" style="padding:5px 11px;font-size:12.5px">Excluir</button>' +
          '</div>' +
        '</div>';
      }).join('') : emptyState('Nenhuma meta cadastrada.')) +
    '</div>';

  } else if (moreTab === 'notas') {
    body = '<div class="card">' +
      '<div class="card-head"><div class="card-title">Nota de hoje</div></div>' +
      '<textarea class="input" id="noteToday" rows="6" placeholder="Escreva sobre o dia…">' + esc(S.note_today || '') + '</textarea>' +
      '<div style="font-size:11.5px;color:var(--text-3);margin-top:7px" id="noteStatus">Salva sozinho.</div>' +
      '<div style="margin-top:14px"><button class="btn btn-block" data-act="load-notes">Ver anotações anteriores</button></div>' +
      '<div id="notesHistory" style="margin-top:12px"></div>' +
    '</div>';

  } else {
    body = '<div class="card">' +
      '<div class="card-head"><div class="card-title">Aparência</div></div>' +
      '<div class="opts" data-opt-group="theme">' +
        [['dark', 'Escuro'], ['light', 'Claro'], ['auto', 'Sistema']].map(function (o) {
          return '<button type="button" class="opt' + (currentThemePref() === o[0] ? ' on' : '') +
            '" data-act="set-theme" data-val="' + o[0] + '">' + o[1] + '</button>';
        }).join('') +
      '</div>' +
    '</div>' +

    '<div class="card">' +
      '<div class="card-head"><div class="card-title">Finanças</div></div>' +
      '<button class="btn btn-block" data-act="fin-settings">Definir saldo inicial</button>' +
      '<div style="height:10px"></div>' +
      '<button class="btn btn-block" data-act="new-cat">Nova categoria</button>' +
      '<div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">' +
        ((S.finance.categories || []).map(function (c) {
          return '<span class="chip">' + esc(c.name) + ' · ' + (c.type === 'income' ? 'entrada' : 'gasto') + '</span>';
        }).join('') || '<span style="color:var(--text-3);font-size:13px">Nenhuma categoria ainda.</span>') +
      '</div>' +
    '</div>' +

    '<div class="card">' +
      '<div class="card-head"><div class="card-title">Dados</div></div>' +
      '<button class="btn btn-block" data-act="hard-reload">Recarregar do servidor</button>' +
      '<div style="font-size:12px;color:var(--text-3);margin-top:10px;line-height:1.5">' +
        'Todos os seus dados antigos continuam no banco. As abas de Hábitos, Metas e Anotações ' +
        'moraram aqui para deixar o dia a dia mais limpo.' +
      '</div>' +
    '</div>';
  }

  return '' +
  '<div class="page-head"><div><div class="page-title">Mais</div>' +
    '<div class="page-sub">Hábitos, metas, anotações e ajustes</div></div></div>' +
  '<div class="filters">' +
    '<div class="segment">' + tabs.map(function (t) {
      return '<button data-act="more-tab" data-val="' + t[0] + '" class="' + (moreTab === t[0] ? 'on' : '') + '">' + t[1] + '</button>';
    }).join('') + '</div>' +
  '</div>' +
  '<div class="stack">' + body + '</div>';
}

/* ===================== TEMA ===================== */

function currentThemePref() { return localStorage.getItem('vc-theme') || 'auto'; }

function applyTheme() {
  var pref = currentThemePref();
  var dark = pref === 'dark' || (pref === 'auto' && !window.matchMedia('(prefers-color-scheme: light)').matches);
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  var meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute('content', dark ? '#0b0c0f' : '#f4f5f7');
}

/* ===================== RENDER ===================== */

var NAV = [
  { id: 'hoje',     label: 'Hoje',     icon: '◉' },
  { id: 'tarefas',  label: 'Tarefas',  icon: '≡' },
  { id: 'financas', label: 'Finanças', icon: '◫' },
  { id: 'treinos',  label: 'Treinos',  icon: '⚡' },
  { id: 'mais',     label: 'Mais',     icon: '⋯' }
];

function renderNav() {
  var pend = loading ? 0 : pendingCount();

  $('#sidebarNav').innerHTML = NAV.map(function (n) {
    var badge = n.id === 'hoje' && pend ? '<span class="count">' + pend + '</span>' : '';
    return '<button class="nav-btn' + (view === n.id ? ' is-active' : '') + '" data-act="nav" data-val="' + n.id + '">' +
      '<span class="ic">' + n.icon + '</span><span>' + n.label + '</span>' + badge + '</button>';
  }).join('');

  $('#bottomNav').innerHTML = NAV.map(function (n) {
    var badge = n.id === 'hoje' && pend ? '<span class="count">' + (pend > 9 ? '9+' : pend) + '</span>' : '';
    return '<button class="' + (view === n.id ? 'is-active' : '') + '" data-act="nav" data-val="' + n.id + '">' +
      '<span class="ic">' + n.icon + '</span><span>' + n.label + '</span>' + badge + '</button>';
  }).join('');

  var iso = loading ? toISO(new Date()) : today();
  $('#sidebarDate').textContent = parseISO(iso).toLocaleDateString('pt-BR', { weekday: 'long' });
  $('#sidebarDate2').textContent = parseISO(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });

  var current = NAV.filter(function (n) { return n.id === view; })[0];
  $('#topTitle').textContent = current ? current.label : 'Vida em Controle';
  $('#topSub').textContent = parseISO(iso).toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
}

function renderError(msg) {
  $('#app').innerHTML = '<div class="card" style="text-align:center;padding:40px 20px">' +
    '<div style="font-size:30px;margin-bottom:10px">⚠</div>' +
    '<div style="font-weight:600;margin-bottom:6px">Não deu para carregar</div>' +
    '<div style="color:var(--text-3);font-size:13.5px;margin-bottom:18px">' + esc(msg) + '</div>' +
    '<button class="btn btn-primary" data-act="hard-reload">Tentar de novo</button>' +
  '</div>';
  renderNav();
}

function render() {
  if (loading || !S) {
    $('#app').innerHTML = '<div class="skel" style="height:120px"></div>' +
      '<div class="skel" style="height:64px;margin-top:14px"></div>' +
      '<div class="skel" style="height:320px;margin-top:14px"></div>';
    renderNav();
    return;
  }

  var html =
    view === 'hoje' ? viewHoje() :
    view === 'tarefas' ? viewTarefas() :
    view === 'financas' ? viewFinancas() :
    view === 'treinos' ? viewTreinos() : viewMais();

  var app = $('#app');
  app.className = 'panel';
  app.innerHTML = html;
  renderNav();
  bindNote();
}

function go(v) {
  if (view === v) return;
  view = v;
  location.hash = v;
  window.scrollTo(0, 0);
  render();
}

/* ===================== NOTA DO DIA (autosave) ===================== */

var noteTimer = null;

function bindNote() {
  var ta = $('#noteToday');
  if (!ta) return;
  ta.addEventListener('input', function () {
    S.note_today = ta.value;
    var status = $('#noteStatus');
    if (status) status.textContent = 'Digitando…';
    clearTimeout(noteTimer);
    noteTimer = setTimeout(function () {
      api('note_save', { body: { date: today(), content: ta.value } })
        .then(function () { if ($('#noteStatus')) $('#noteStatus').textContent = 'Salvo ✓'; })
        .catch(function () { if ($('#noteStatus')) $('#noteStatus').textContent = 'Falhou ao salvar'; });
    }, 700);
  });
}

/* ===================== FORMULÁRIOS ===================== */

function taskModal(taskId) {
  var t = taskId ? (S.tasks || []).filter(function (x) { return Number(x.id) === Number(taskId); })[0] : null;
  var rec = t ? (t.recurrence || 'once') : 'once';
  var whenSel = rec === 'once' ? (t && t.due_date ? 'data' : (t ? 'sem' : 'hoje')) : rec;
  var dueVal = t && t.due_date ? String(t.due_date).slice(0, 10) : today();

  openModal({
    title: t ? 'Editar tarefa' : 'Nova tarefa',
    submitLabel: t ? 'Salvar' : 'Criar',
    body:
      '<div class="field"><label class="label">O que precisa ser feito</label>' +
        '<input class="input" id="f-title" data-autofocus placeholder="Ex: pagar a conta de luz" value="' +
          esc(t ? t.title : '') + '"></div>' +
      '<div class="field"><label class="label">Área</label>' +
        optGroup('area', [
          { value: 'casa', label: AREAS.casa.icon + ' Casa' },
          { value: 'trabalho', label: AREAS.trabalho.icon + ' Trabalho' },
          { value: 'pessoal', label: AREAS.pessoal.icon + ' Pessoal' }
        ], t ? (t.area || 'pessoal') : 'pessoal') + '</div>' +
      '<div class="field"><label class="label">Quando</label>' +
        optGroup('when', [
          { value: 'hoje', label: 'Hoje' },
          { value: 'data', label: 'Data' },
          { value: 'daily', label: 'Todo dia' },
          { value: 'weekly', label: 'Semanal' },
          { value: 'sem', label: 'Sem data' }
        ], whenSel) + '</div>' +
      '<div class="field" id="wrap-date" style="display:' + (whenSel === 'data' ? 'block' : 'none') + '">' +
        '<label class="label">Data</label>' +
        '<input type="date" class="input" id="f-date" value="' + esc(dueVal) + '"></div>' +
      '<div class="field" id="wrap-dow" style="display:' + (whenSel === 'weekly' ? 'block' : 'none') + '">' +
        '<label class="label">Dia da semana</label>' +
        '<select class="input" id="f-dow">' + DOW_LONG.map(function (d, i) {
          var v = i + 1;
          var sel = Number(t && t.recurrence_day || dowIndex(today())) === v ? ' selected' : '';
          return '<option value="' + v + '"' + sel + '>' + d + '</option>';
        }).join('') + '</select></div>' +
      '<div class="field">' +
        '<label style="display:flex;align-items:center;gap:9px;font-size:14px;cursor:pointer">' +
          '<input type="checkbox" id="f-prio" style="width:17px;height:17px;accent-color:var(--accent)"' +
            (t && Number(t.priority) ? ' checked' : '') + '> Marcar como prioridade' +
        '</label></div>',
    onSubmit: function (ov) {
      var title = $('#f-title', ov).value.trim();
      if (!title) { toast('Escreva o que precisa ser feito.', true); return; }

      var when = optValue('when', ov) || 'hoje';
      var payload = {
        id: t ? t.id : 0,
        title: title,
        area: optValue('area', ov) || 'pessoal',
        priority: $('#f-prio', ov).checked ? 1 : 0,
        recurrence: 'once',
        recurrence_day: null,
        due_date: null
      };
      if (when === 'hoje') payload.due_date = today();
      else if (when === 'data') payload.due_date = $('#f-date', ov).value || today();
      else if (when === 'daily') payload.recurrence = 'daily';
      else if (when === 'weekly') {
        payload.recurrence = 'weekly';
        payload.recurrence_day = Number($('#f-dow', ov).value);
      }

      closeModal();
      push('task_save', payload, t ? 'Tarefa atualizada' : 'Tarefa criada').then(function () { reload(); });
    }
  });

  // Mostra o campo certo conforme a opcao de "quando".
  $('#overlay').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-opt-group="when"] .opt');
    if (!btn) return;
    var v = btn.dataset.optValue;
    $('#wrap-date').style.display = v === 'data' ? 'block' : 'none';
    $('#wrap-dow').style.display = v === 'weekly' ? 'block' : 'none';
  });
}

/* Os ultimos n meses. "include" garante que o mes de um lancamento antigo em
   edicao apareca na lista, mesmo fora da janela. */
function monthOptions(n, include) {
  var out = [];
  for (var i = 0; i < n; i++) out.push(shiftMonth(currentMonth(), -i));
  if (include && out.indexOf(include) === -1) {
    out.push(include);
    out.sort().reverse();
  }
  return out;
}

function txModal(type, txId) {
  var tx = txId ? (S.finance.transactions || []).filter(function (x) {
    return Number(x.id) === Number(txId);
  })[0] : null;
  if (tx) type = tx.type;

  var isIn = type === 'income';
  var cats = (S.finance.categories || []).filter(function (c) { return c.type === type; });
  var txDate = tx ? String(tx.transaction_date).slice(0, 10) : null;
  var selMonth = tx ? txDate.slice(0, 7) : currentMonth();
  var amountVal = tx ? Number(tx.amount).toFixed(2).replace('.', ',') : '';

  openModal({
    title: tx ? (isIn ? 'Editar entrada' : 'Editar gasto') : (isIn ? 'Nova entrada' : 'Novo gasto'),
    submitLabel: tx ? 'Salvar' : 'Lançar',
    body:
      '<div class="field"><label class="label">Valor</label>' +
        '<input class="input input-money" id="f-amount" data-autofocus inputmode="decimal" placeholder="0,00" ' +
          'value="' + esc(amountVal) + '"></div>' +
      '<div class="field"><label class="label">Descrição <span style="text-transform:none;letter-spacing:0;' +
          'font-weight:500;color:var(--text-3)">— opcional</span></label>' +
        '<input class="input" id="f-desc" value="' + esc(tx && tx.description ? tx.description : '') + '" placeholder="' +
          (cats.length ? 'Em branco, usa a categoria' : (isIn ? 'Ex: salário' : 'Ex: mercado')) + '"></div>' +
      (cats.length ? '<div class="field"><label class="label">Categoria</label>' +
        optGroup('cat', [{ value: '', label: 'Nenhuma' }].concat(cats.map(function (c) {
          return { value: String(c.id), label: esc(c.name) };
        })), tx && tx.category_id ? String(tx.category_id) : '') + '</div>' : '') +
      '<div class="field"><label class="label">Mês</label>' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">' +
          '<select class="input" id="f-month" style="flex:1;min-width:130px">' +
            monthOptions(12, selMonth).map(function (ym) {
              return '<option value="' + ym + '"' + (ym === selMonth ? ' selected' : '') + '>' +
                esc(monthLabel(ym)) + '</option>';
            }).join('') +
          '</select>' +
          '<button type="button" class="opt' + (selMonth === shiftMonth(currentMonth(), -1) ? ' on' : '') +
            '" data-act="pick-month" data-val="' + shiftMonth(currentMonth(), -1) + '">' +
            esc(monthLabel(shiftMonth(currentMonth(), -1))) + '</button>' +
          '<button type="button" class="opt' + (selMonth === currentMonth() ? ' on' : '') +
            '" data-act="pick-month" data-val="' + currentMonth() + '">Este mês</button>' +
        '</div></div>',
    onSubmit: function (ov) {
      var raw = $('#f-amount', ov).value.trim().replace(/\./g, '').replace(',', '.');
      var amount = parseFloat(raw);
      if (!amount || amount <= 0) { toast('Informe um valor válido.', true); return; }

      var catId = optValue('cat', ov);
      var desc = $('#f-desc', ov).value.trim();
      var newMonth = $('#f-month', ov).value || currentMonth();
      // Editando sem trocar de mes, o dia original e preservado — so pedimos o mes,
      // mas nao ha razao para reescrever a data que ja estava certa.
      var date = (tx && newMonth === txDate.slice(0, 7)) ? txDate : dateForMonth(newMonth);
      closeModal();
      push('fin_save', {
        id: tx ? tx.id : 0,
        type: type,
        amount: amount,
        description: desc,
        category_id: catId ? Number(catId) : null,
        date: date
      }, tx ? 'Lançamento atualizado' : (isIn ? 'Entrada lançada' : 'Gasto lançado'))
        .then(function () { reload(); });
    }
  });
}

function runModal() {
  openModal({
    title: 'Registrar corrida',
    submitLabel: 'Salvar',
    body:
      '<div class="row-2">' +
        '<div class="field"><label class="label">Distância (km)</label>' +
          '<input class="input" id="f-km" data-autofocus inputmode="decimal" placeholder="5,0"></div>' +
        '<div class="field"><label class="label">Tempo (min)</label>' +
          '<input class="input" id="f-min" inputmode="numeric" placeholder="30"></div>' +
      '</div>' +
      '<div class="field"><label class="label">Data</label>' +
        '<input type="date" class="input" id="f-date" value="' + today() + '"></div>' +
      '<div class="field"><label class="label">Observação (opcional)</label>' +
        '<input class="input" id="f-notes" placeholder="Ex: parque, ritmo leve"></div>',
    onSubmit: function (ov) {
      var km = parseFloat($('#f-km', ov).value.replace(',', '.')) || 0;
      var min = parseInt($('#f-min', ov).value, 10) || 0;
      if (km <= 0 && min <= 0) { toast('Informe a distância ou o tempo.', true); return; }
      var date = $('#f-date', ov).value || today();
      var notes = $('#f-notes', ov).value.trim();
      closeModal();
      push('run_save', {
        title: 'Corrida',
        distance_km: km,
        duration_min: min,
        date: date,
        notes: notes
      }, 'Corrida registrada').then(function () { reload(); });
    }
  });
}

function planModal() {
  var rows = [];
  for (var i = 1; i <= 7; i++) {
    var p = (S.workout_plan || []).filter(function (x) { return Number(x.weekday) === i; })[0] || { type: 'rest', name: '' };
    rows.push(
      '<div class="field" data-weekday="' + i + '">' +
        '<label class="label">' + DOW_LONG[i - 1] + '</label>' +
        '<div class="row-2">' +
          '<select class="input plan-type">' +
            ['rest', 'gym', 'run', 'other'].map(function (tp) {
              return '<option value="' + tp + '"' + (p.type === tp ? ' selected' : '') + '>' +
                WTYPES[tp].icon + ' ' + WTYPES[tp].label + '</option>';
            }).join('') +
          '</select>' +
          '<input class="input plan-name" placeholder="Ex: Peito e tríceps" value="' + esc(p.name || '') + '">' +
        '</div>' +
      '</div>'
    );
  }

  openModal({
    title: 'Plano da semana',
    submitLabel: 'Salvar plano',
    body: rows.join(''),
    onSubmit: function (ov) {
      var items = [].slice.call(ov.querySelectorAll('[data-weekday]')).map(function (row) {
        return {
          weekday: Number(row.dataset.weekday),
          type: row.querySelector('.plan-type').value,
          name: row.querySelector('.plan-name').value.trim()
        };
      });
      closeModal();
      push('workout_plan_save', { items: items }, 'Plano salvo').then(function () { reload(); });
    }
  });
}

function habitModal() {
  openModal({
    title: 'Novo hábito',
    submitLabel: 'Criar',
    body:
      '<div class="field"><label class="label">Nome</label>' +
        '<input class="input" id="f-name" data-autofocus placeholder="Ex: tomar remédio"></div>' +
      '<div class="field"><label class="label">Frequência</label>' +
        optGroup('rec', [{ value: 'daily', label: 'Todo dia' }, { value: 'weekly', label: 'Semanal' }], 'daily') + '</div>' +
      '<div class="field"><label class="label">Dia da semana (se semanal)</label>' +
        '<select class="input" id="f-dow">' + DOW_LONG.map(function (d, i) {
          return '<option value="' + (i + 1) + '">' + d + '</option>';
        }).join('') + '</select></div>',
    onSubmit: function (ov) {
      var name = $('#f-name', ov).value.trim();
      if (!name) { toast('Dê um nome ao hábito.', true); return; }
      var rec = optValue('rec', ov) || 'daily';
      var dow = Number($('#f-dow', ov).value);
      closeModal();
      push('habit_save', {
        name: name,
        recurrence: rec,
        recurrence_day: rec === 'weekly' ? dow : null
      }, 'Hábito criado').then(function () { reload(); });
    }
  });
}

function goalModal() {
  openModal({
    title: 'Nova meta',
    submitLabel: 'Criar',
    body:
      '<div class="field"><label class="label">Meta</label>' +
        '<input class="input" id="f-title" data-autofocus placeholder="Ex: reserva de emergência"></div>' +
      '<div class="field"><label class="label">Valor alvo (deixe vazio se não for dinheiro)</label>' +
        '<input class="input" id="f-target" inputmode="decimal" placeholder="5000,00"></div>' +
      '<div class="field"><label class="label">Prazo (opcional)</label>' +
        '<input type="date" class="input" id="f-deadline"></div>',
    onSubmit: function (ov) {
      var title = $('#f-title', ov).value.trim();
      if (!title) { toast('Dê um nome à meta.', true); return; }
      var target = parseFloat($('#f-target', ov).value.replace(/\./g, '').replace(',', '.')) || 0;
      var deadline = $('#f-deadline', ov).value || null;
      closeModal();
      push('goal_save', {
        title: title,
        target_amount: target,
        deadline: deadline
      }, 'Meta criada').then(function () { reload(); });
    }
  });
}

function depositModal(goalId) {
  var g = (S.goals || []).filter(function (x) { return Number(x.id) === Number(goalId); })[0];
  if (!g) return;
  openModal({
    title: 'Depositar em "' + g.title + '"',
    submitLabel: 'Depositar',
    body: '<div class="field"><label class="label">Valor</label>' +
      '<input class="input input-money" id="f-amount" data-autofocus inputmode="decimal" placeholder="0,00"></div>',
    onSubmit: function (ov) {
      var amount = parseFloat($('#f-amount', ov).value.replace(/\./g, '').replace(',', '.'));
      if (!amount || amount <= 0) { toast('Informe um valor válido.', true); return; }
      closeModal();
      push('goal_deposit', { id: g.id, amount: amount }, 'Depósito registrado').then(function () { reload(); });
    }
  });
}

function finSettingsModal() {
  openModal({
    title: 'Saldo inicial',
    submitLabel: 'Salvar',
    body:
      '<div class="field"><label class="label">Quanto você tinha antes de começar a registrar</label>' +
        '<input class="input input-money" id="f-amount" data-autofocus inputmode="decimal" placeholder="0,00"></div>' +
      '<div style="font-size:12.5px;color:var(--text-3);line-height:1.5">' +
        'Esse valor é a base do saldo. Todo lançamento entra ou sai a partir dele.</div>',
    onSubmit: function (ov) {
      var v = parseFloat($('#f-amount', ov).value.replace(/\./g, '').replace(',', '.'));
      if (isNaN(v)) { toast('Informe um valor.', true); return; }
      closeModal();
      push('fin_settings_save', { initial_balance: v }, 'Saldo inicial salvo').then(function () { reload(); });
    }
  });
}

function catModal() {
  openModal({
    title: 'Nova categoria',
    submitLabel: 'Criar',
    body:
      '<div class="field"><label class="label">Nome</label>' +
        '<input class="input" id="f-name" data-autofocus placeholder="Ex: mercado"></div>' +
      '<div class="field"><label class="label">Tipo</label>' +
        optGroup('type', [{ value: 'expense', label: 'Gasto' }, { value: 'income', label: 'Entrada' }], 'expense') + '</div>',
    onSubmit: function (ov) {
      var name = $('#f-name', ov).value.trim();
      if (!name) { toast('Dê um nome à categoria.', true); return; }
      var type = optValue('type', ov) || 'expense';
      closeModal();
      push('cat_save', { name: name, type: type }, 'Categoria criada').then(function () { reload(); });
    }
  });
}

/* ===================== AÇÕES ===================== */

/* Atualiza o estado local na hora para o clique parecer instantaneo. */
function localToggleTask(id, iso) {
  var t = (S.tasks || []).filter(function (x) { return Number(x.id) === Number(id); })[0];
  if (!t) return;
  if ((t.recurrence || 'once') === 'once') {
    t.status = Number(t.status) === 1 ? 0 : 1;
    t.completed_at = t.status ? today() + ' 00:00:00' : null;
  } else {
    var dates = String(t.done_dates || '').split(',').filter(Boolean);
    var i = dates.indexOf(iso);
    if (i === -1) dates.push(iso); else dates.splice(i, 1);
    t.done_dates = dates.join(',');
  }
}

function localToggleHabit(id, iso) {
  var h = (S.habits || []).filter(function (x) { return Number(x.id) === Number(id); })[0];
  if (!h) return;
  var arr; try { arr = JSON.parse(h.checked_dates || '[]'); } catch (e) { arr = []; }
  var i = arr.indexOf(iso);
  if (i === -1) arr.push(iso); else arr.splice(i, 1);
  h.checked_dates = JSON.stringify(arr);
}

function localToggleWorkout(iso) {
  var log = workoutLogFor(iso);
  if (log) {
    log.done = Number(log.done) === 1 ? 0 : 1;
  } else {
    var p = planFor(iso);
    S.workout_logs.unshift({
      name: p.name || WTYPES[p.type].label,
      workout_date: iso,
      done: 1,
      type: p.type === 'rest' ? 'other' : p.type
    });
  }
}

var ACTIONS = {
  nav: function (el) { go(el.dataset.val); },
  'go-treinos': function () { go('treinos'); },
  'go-financas': function () { go('financas'); },

  'filter-area': function (el) { filterArea = el.dataset.val; render(); },
  'filter-when': function (el) { filterWhen = el.dataset.val; render(); },
  'more-tab': function (el) { moreTab = el.dataset.val; render(); },

  'close-modal': closeModal,

  opt: function (el) {
    var group = el.closest('[data-opt-group]');
    [].slice.call(group.querySelectorAll('.opt')).forEach(function (b) { b.classList.remove('on'); });
    el.classList.add('on');
  },

  'toggle-task': function (el) {
    var id = el.dataset.id, iso = el.dataset.date || today();
    touched[id] = true;
    localToggleTask(id, iso);
    render();
    push('task_toggle', { id: Number(id), date: iso });
  },

  'toggle-habit': function (el) {
    var id = el.dataset.id, iso = el.dataset.date || today();
    localToggleHabit(id, iso);
    render();
    push('habit_toggle', { id: Number(id), date: iso });
  },

  'toggle-workout': function (el) {
    var iso = el.dataset.date || today();
    var p = planFor(iso);
    localToggleWorkout(iso);
    render();
    push('workout_toggle', { date: iso, name: p.name || WTYPES[p.type].label, type: p.type === 'rest' ? 'other' : p.type });
  },

  'schedule-today': function (el) {
    var t = (S.tasks || []).filter(function (x) { return Number(x.id) === Number(el.dataset.id); })[0];
    if (!t) return;
    t.due_date = today();
    render();
    push('task_save', {
      id: t.id, title: t.title, area: t.area || 'pessoal', priority: Number(t.priority) || 0,
      recurrence: 'once', due_date: today()
    }, 'Puxada para hoje');
  },

  'new-task': function () { taskModal(0); },
  'edit-task': function (el) { taskModal(el.dataset.id); },
  'del-task': function (el) {
    confirmDialog('Excluir esta tarefa? Isso não pode ser desfeito.').then(function (ok) {
      if (!ok) return;
      S.tasks = (S.tasks || []).filter(function (x) { return Number(x.id) !== Number(el.dataset.id); });
      render();
      push('task_delete', { id: Number(el.dataset.id) }, 'Tarefa excluída');
    });
  },

  'new-expense': function () { txModal('expense'); },
  'new-income': function () { txModal('income'); },
  'edit-tx': function (el) { txModal(null, el.dataset.id); },
  'del-tx': function (el) {
    confirmDialog('Excluir este lançamento?').then(function (ok) {
      if (!ok) return;
      push('fin_delete', { id: Number(el.dataset.id) }, 'Lançamento excluído').then(function () { reload(); });
    });
  },
  month: function (el) {
    month = shiftMonth(month, Number(el.dataset.val));
    loading = true;
    render();
    reload();
  },
  'fin-settings': finSettingsModal,

  // Atalhos ao lado do seletor de mes no modal de lancamento.
  'pick-month': function (el) {
    var sel = $('#f-month');
    if (!sel) return;
    sel.value = el.dataset.val;
    [].slice.call(el.parentElement.querySelectorAll('[data-act="pick-month"]')).forEach(function (b) {
      b.classList.toggle('on', b === el);
    });
  },
  'new-cat': catModal,

  'new-run': runModal,
  'del-run': function (el) {
    confirmDialog('Excluir esta corrida?').then(function (ok) {
      if (!ok) return;
      S.runs = (S.runs || []).filter(function (x) { return Number(x.id) !== Number(el.dataset.id); });
      render();
      push('run_delete', { id: Number(el.dataset.id) }, 'Corrida excluída');
    });
  },
  'edit-plan': planModal,

  'new-habit': habitModal,
  'del-habit': function (el) {
    confirmDialog('Excluir este hábito e todo o histórico dele?').then(function (ok) {
      if (!ok) return;
      S.habits = (S.habits || []).filter(function (x) { return Number(x.id) !== Number(el.dataset.id); });
      render();
      push('habit_delete', { id: Number(el.dataset.id) }, 'Hábito excluído');
    });
  },

  'new-goal': goalModal,
  'deposit-goal': function (el) { depositModal(el.dataset.id); },
  'toggle-goal': function (el) {
    push('goal_toggle', { id: Number(el.dataset.id) }).then(function () { reload(); });
  },
  'del-goal': function (el) {
    confirmDialog('Excluir esta meta?').then(function (ok) {
      if (!ok) return;
      push('goal_delete', { id: Number(el.dataset.id) }, 'Meta excluída').then(function () { reload(); });
    });
  },

  'load-notes': function (el) {
    el.disabled = true;
    el.textContent = 'Carregando…';
    api('notes_list').then(function (notes) {
      var box = $('#notesHistory');
      if (!box) return;
      var past = (notes || []).filter(function (n) { return String(n.note_date).slice(0, 10) !== today(); });
      box.innerHTML = past.length ? past.slice(0, 30).map(function (n) {
        return '<div style="padding:11px 0;border-top:1px solid var(--border)">' +
          '<div class="day-label" style="padding:0 0 4px">' + esc(fmtDayLabel(String(n.note_date).slice(0, 10))) + '</div>' +
          '<div style="font-size:14px;color:var(--text-2);white-space:pre-wrap">' + esc(n.content || '—') + '</div>' +
        '</div>';
      }).join('') : '<div class="list-empty">Nenhuma anotação anterior.</div>';
      el.remove();
    }).catch(function (e) {
      toast(e.message, true);
      el.disabled = false;
      el.textContent = 'Ver anotações anteriores';
    });
  },

  'set-theme': function (el) {
    localStorage.setItem('vc-theme', el.dataset.val);
    applyTheme();
    render();
  },

  'hard-reload': function () {
    loading = true;
    render();
    reload().catch(function () {});
  }
};

/* ===================== EVENTOS ===================== */

document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-act]');
  if (el) {
    var fn = ACTIONS[el.dataset.act];
    if (fn) { e.preventDefault(); fn(el); }
    return;
  }
  // Clique fora do modal fecha.
  if (e.target.id === 'overlay') closeModal();
});

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && $('#overlay').classList.contains('open')) closeModal();
  // Atalhos de desktop: n = nova tarefa, g = novo gasto.
  if (e.target.matches('input, textarea, select')) return;
  if (e.key === 'n') { e.preventDefault(); taskModal(0); }
  if (e.key === 'g') { e.preventDefault(); txModal('expense'); }
});

window.addEventListener('hashchange', function () {
  var v = location.hash.replace('#', '');
  if (v && v !== view && NAV.some(function (n) { return n.id === v; })) { view = v; render(); }
});

window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () {
  if (currentThemePref() === 'auto') applyTheme();
});

// Ao voltar para o app depois de um tempo, revalida os dados em segundo plano.
document.addEventListener('visibilitychange', function () {
  if (document.visibilityState === 'visible' && S) reload({ silent: false }).catch(function () {});
});

/* ===================== BOOT ===================== */

applyTheme();

var initial = location.hash.replace('#', '');
if (initial && NAV.some(function (n) { return n.id === initial; })) view = initial;

month = toISO(new Date()).slice(0, 7);
render();
reload().catch(function () {});

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(function () {});
}

})();
