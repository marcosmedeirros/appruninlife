<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vida em Controle — Marcos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Bebas+Neue&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0a0a0a;
  --surface: #141414;
  --surface2: #1c1c1c;
  --surface3: #242424;
  --border: rgba(255,255,255,0.06);
  --border2: rgba(255,255,255,0.12);
  --text: #f0f0f0;
  --muted: #666;
  --muted2: #888;
  --accent: #ffffff;
  --green: #10d9a0;
  --red: #ff4d6d;
  --yellow: #f5c842;
  --blue: #4da6ff;
  --purple: #a78bfa;
  --radius: 18px;
  --radius-sm: 12px;
  --sidebar: 260px;
  --bottomnav: 72px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Space Grotesk', sans-serif;
  font-size: 15px;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ===== LAYOUT ===== */
.layout {
  display: flex;
  min-height: 100vh;
}

/* SIDEBAR — desktop only */
.sidebar {
  width: var(--sidebar);
  background: var(--surface);
  border-right: 1px solid var(--border);
  position: fixed;
  top: 0; left: 0; bottom: 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
  padding: 32px 0;
}

.sidebar-logo {
  padding: 0 24px 32px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 24px;
}
.logo-mark {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 26px;
  letter-spacing: 1px;
  line-height: 1;
  color: var(--text);
}
.logo-sub {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  color: var(--muted);
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-top: 4px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 12px;
  flex: 1;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: var(--muted2);
  transition: all 0.2s;
  border: 1px solid transparent;
}
.nav-item:hover { color: var(--text); background: var(--surface2); }
.nav-item.active {
  background: var(--text);
  color: var(--bg);
  font-weight: 600;
}
.nav-icon { font-size: 18px; width: 24px; text-align: center; }
.nav-label { flex: 1; }
.nav-badge {
  display: none;
}
.nav-item.active .nav-badge {
  background: rgba(0,0,0,0.2);
  color: rgba(0,0,0,0.6);
}

.sidebar-footer {
  padding: 24px;
  border-top: 1px solid var(--border);
}
.sidebar-date {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  line-height: 1.6;
}
.sidebar-date strong {
  color: var(--text);
  font-size: 13px;
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 600;
  display: block;
  margin-bottom: 2px;
}

/* MAIN CONTENT */
.main {
  margin-left: var(--sidebar);
  flex: 1;
  min-width: 0;
  padding: 40px;
  padding-bottom: 40px;
}

/* PANELS */
.panel { display: none; }
.panel.active { display: block; }

/* ===== STREAK CIRCLE ===== */
.streak-section {
  display: flex;
  align-items: center;
  gap: 40px;
  margin-bottom: 40px;
  padding: 36px 40px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px;
}

.streak-circle-wrap {
  position: relative;
  flex-shrink: 0;
}
.streak-circle-wrap svg {
  transform: rotate(-90deg);
}
.streak-circle-bg {
  fill: none;
  stroke: var(--surface3);
  stroke-width: 6;
}
.streak-circle-fg {
  fill: none;
  stroke: var(--text);
  stroke-width: 6;
  stroke-linecap: round;
  transition: stroke-dashoffset 1s ease;
}
.streak-number {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  line-height: 1;
}
.streak-num {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 52px;
  color: var(--text);
  display: block;
}
.streak-label {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  color: var(--muted);
  letter-spacing: 2px;
  text-transform: uppercase;
}

.streak-info {
  flex: 1;
}
.streak-headline {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 38px;
  letter-spacing: 1px;
  line-height: 1;
  margin-bottom: 8px;
}
.streak-headline span { color: var(--muted); }
.streak-sub {
  font-size: 13px;
  color: var(--muted2);
  margin-bottom: 20px;
  font-family: 'DM Mono', monospace;
}
.streak-quote {
  font-size: 14px;
  color: var(--muted2);
  font-style: italic;
  padding: 12px 16px;
  background: var(--surface2);
  border-radius: var(--radius-sm);
  border-left: 2px solid var(--surface3);
}
.streak-toggle {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
}
.streak-btn {
  padding: 8px 20px;
  border-radius: 99px;
  border: none;
  cursor: pointer;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}
.streak-btn.active { background: var(--text); color: var(--bg); }
.streak-btn:not(.active) { background: var(--surface2); color: var(--muted2); }

/* ===== STAT CARDS ===== */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 28px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 20px;
  position: relative;
  overflow: hidden;
}
.stat-card::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0; height: 1px;
}
.stat-card.green::after { background: var(--green); }
.stat-card.red::after { background: var(--red); }
.stat-card.blue::after { background: var(--blue); }
.stat-card.purple::after { background: var(--purple); }

.stat-label {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 10px;
}
.stat-value {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 32px;
  line-height: 1;
}
.stat-value.green { color: var(--green); }
.stat-value.red { color: var(--red); }
.stat-value.blue { color: var(--blue); }
.stat-value.purple { color: var(--purple); }
.stat-sub { font-size: 11px; color: var(--muted); margin-top: 6px; font-family: 'DM Mono', monospace; }

/* ===== SECTION HEADER ===== */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  gap: 12px;
  flex-wrap: wrap;
}
.section-title {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--muted);
}

/* ===== CONTENT GRID ===== */
.content-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 20px;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
}
.card-title {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--muted);
  margin-bottom: 20px;
}

/* ===== HABIT LIST ===== */
.habit-list { display: flex; flex-direction: column; gap: 8px; }

.habit-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: var(--surface2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  transition: all 0.2s;
  cursor: pointer;
}
.habit-item:hover { border-color: var(--border2); }
.habit-item.done { opacity: 0.5; }
.habit-item.done .habit-name { text-decoration: line-through; }

.habit-check {
  width: 26px; height: 26px;
  border-radius: 50%;
  border: 2px solid var(--surface3);
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
  font-size: 13px;
}
.habit-item.done .habit-check {
  background: var(--text);
  border-color: var(--text);
  color: var(--bg);
}

.habit-emoji { font-size: 18px; }
.habit-info { flex: 1; }
.habit-name { font-size: 14px; font-weight: 500; }
.habit-meta { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 2px; }

.habit-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.habit-item:hover .habit-actions { opacity: 1; }

/* ADD HABIT BTN */
.add-habit-btn {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: var(--radius-sm);
  border: 1px dashed var(--surface3);
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  width: 100%;
  text-align: left;
}
.add-habit-btn:hover { border-color: var(--border2); color: var(--muted2); }
.add-plus {
  width: 26px; height: 26px;
  background: var(--surface2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

/* ===== TASK LIST ===== */
.task-section { margin-bottom: 24px; }
.task-section-label {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.task-section-label .warn { color: var(--yellow); }

.task-items { display: flex; flex-direction: column; gap: 6px; }

.task-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: var(--surface);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  transition: all 0.2s;
}
.task-item:hover { border-color: var(--border2); }
.task-item.done { opacity: 0.45; }
.task-item.done .task-name { text-decoration: line-through; }
.task-item.overdue { border-color: rgba(255,77,109,0.2); }

.task-check {
  width: 22px; height: 22px;
  border-radius: 50%;
  border: 2px solid var(--surface3);
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s;
}
.task-item.done .task-check { background: var(--muted); border-color: var(--muted); color: var(--bg); }

.task-info { flex: 1; }
.task-name { font-size: 14px; font-weight: 500; }
.task-date { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 2px; }
.task-date.overdue { color: var(--red); }

.badge-vencida {
  font-size: 10px;
  font-family: 'DM Mono', monospace;
  background: rgba(255,77,109,0.1);
  color: var(--red);
  border: 1px solid rgba(255,77,109,0.2);
  padding: 2px 8px;
  border-radius: 6px;
  letter-spacing: 0.5px;
}

.task-more {
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px;
  cursor: pointer;
  color: var(--muted);
  font-size: 16px;
  transition: all 0.2s;
  background: transparent;
  border: none;
}
.task-more:hover { background: var(--surface2); color: var(--text); }

.task-week-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 10px;
}
.task-week-wrap {
  overflow-x: auto;
  padding-bottom: 4px;
}
.task-day {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px;
  min-height: 160px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.task-day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.task-day-header strong { color: var(--text); font-family: 'Space Grotesk', sans-serif; font-size: 13px; }
.task-day-list { display: flex; flex-direction: column; gap: 8px; }
.task-day-empty { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; padding: 4px 0; }
.task-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--surface2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}
.task-row-meta {
  font-size: 10px;
  color: var(--muted);
  font-family: 'DM Mono', monospace;
  margin-top: 4px;
}
.task-mobile-list { display: flex; flex-direction: column; gap: 10px; }
.task-mobile-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
}
.task-mobile-title {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.task-mobile-count {
  background: var(--surface2);
  color: var(--muted2);
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 999px;
}
.task-mobile-items { display: flex; flex-direction: column; gap: 8px; }
.task-mobile-item {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.task-mobile-item-title { font-size: 13px; }
.task-mobile-actions { display: flex; gap: 6px; }
.task-row.done { opacity: 0.5; }
.task-row.overdue { border-color: rgba(255,77,109,0.2); }
.task-title { flex: 1; font-size: 13px; }
.task-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.task-row:hover .task-actions { opacity: 1; }
.task-item:hover .task-actions { opacity: 1; }

/* ===== TRANSACTIONS ===== */
.txn-list { display: flex; flex-direction: column; gap: 6px; }
.txn-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--surface2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  transition: all 0.2s;
}
.txn-item:hover { border-color: var(--border2); }

.txn-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.txn-info { flex: 1; }
.txn-desc { font-size: 13px; font-weight: 500; }
.txn-cat { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; }
.txn-amount { font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 500; }
.txn-amount.income { color: var(--green); }
.txn-amount.expense { color: var(--red); }

/* ===== GOAL ITEMS ===== */
.goal-items { display: flex; flex-direction: column; gap: 14px; }
.goal-item {}
.goal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.goal-name { font-size: 13px; font-weight: 500; }
.goal-pct { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); }
.progress-track { background: var(--surface2); border-radius: 99px; height: 5px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }

/* ===== FINANCE PANEL ===== */
.fin-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}
.fin-month-nav {
  display: flex;
  align-items: center;
  gap: 12px;
}
.month-btn {
  width: 32px; height: 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: 8px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}
.month-btn:hover { border-color: var(--border2); }
.month-label { font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 500; }

.fin-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 20px;
}

.filter-row { display: flex; gap: 8px; margin-bottom: 16px; }
.filter-chip {
  padding: 6px 16px;
  border-radius: 99px;
  border: 1px solid var(--border);
  background: transparent;
  color: var(--muted2);
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.filter-chip.active { background: var(--text); color: var(--bg); border-color: var(--text); }
.filter-chip:not(.active):hover { border-color: var(--border2); color: var(--muted); }

.txn-full { display: flex; flex-direction: column; gap: 8px; }
.txn-full-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: var(--surface);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  transition: all 0.2s;
}
.txn-full-item:hover { border-color: var(--border2); }
.txn-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.txn-full-item:hover .txn-actions { opacity: 1; }

/* Category breakdown */
.cat-breakdown { display: flex; flex-direction: column; gap: 14px; }
.cat-item {}
.cat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.cat-name { font-size: 13px; display: flex; align-items: center; gap: 8px; }
.cat-dot { width: 8px; height: 8px; border-radius: 50%; }
.cat-val { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--red); }

/* ===== GOALS PANEL ===== */
.goals-list { display: flex; flex-direction: column; gap: 10px; }
.goal-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.goal-row.done { opacity: 0.55; text-decoration: line-through; }
.goal-check {
  width: 22px; height: 22px;
  border-radius: 50%;
  border: 2px solid var(--surface3);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
  cursor: pointer;
}
.goal-row.done .goal-check { background: var(--text); border-color: var(--text); color: var(--bg); }
.goal-title { flex: 1; font-size: 14px; font-weight: 500; }
.goal-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.goal-row:hover .goal-actions { opacity: 1; }

/* ===== BUTTONS ===== */
.btn {
  padding: 9px 18px;
  border-radius: 9px; border: none; cursor: pointer;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px; font-weight: 600;
  display: inline-flex; align-items: center; gap: 6px;
  transition: all 0.2s;
}
.btn-primary { background: var(--text); color: var(--bg); }
.btn-primary:hover { opacity: 0.85; }
.btn-ghost { background: var(--surface2); color: var(--muted2); border: 1px solid var(--border); }
.btn-ghost:hover { border-color: var(--border2); color: var(--text); }
.btn-danger { background: rgba(255,77,109,0.1); color: var(--red); border: 1px solid rgba(255,77,109,0.15); }
.btn-danger:hover { background: rgba(255,77,109,0.2); }
.btn-green { background: rgba(16,217,160,0.1); color: var(--green); border: 1px solid rgba(16,217,160,0.15); }
.btn-green:hover { background: rgba(16,217,160,0.2); }
.btn-sm { padding: 5px 12px; font-size: 11px; border-radius: 7px; }
.btn-icon { width: 30px; height: 30px; padding: 0; justify-content: center; border-radius: 8px; }

/* ===== MODALS ===== */
.modal-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(0,0,0,0.8);
  display: none; align-items: center; justify-content: center;
  padding: 20px;
  backdrop-filter: blur(4px);
}
.modal-overlay.open { display: flex; }
.modal {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 20px;
  padding: 28px;
  width: 100%; max-width: 440px;
  max-height: 90vh; overflow-y: auto;
  animation: modalIn 0.2s ease;
}
@keyframes modalIn { from { opacity:0; transform:translateY(12px) scale(0.98); } to { opacity:1; transform:translateY(0) scale(1); } }
.modal-title { font-family: 'Space Grotesk', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 24px; }
.form-group { margin-bottom: 16px; }
.form-label { font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
.form-control {
  width: 100%; padding: 11px 14px;
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 10px; color: var(--text);
  font-family: 'Space Grotesk', sans-serif; font-size: 14px;
  transition: border-color 0.2s; outline: none;
}
.form-control:focus { border-color: rgba(255,255,255,0.3); }
.form-control option { background: var(--surface2); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }

.color-row { display: flex; gap: 8px; flex-wrap: wrap; }
.color-swatch { width: 26px; height: 26px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.15s; }
.color-swatch.selected { border-color: #fff; transform: scale(1.15); }

/* ===== COMMENT BAR ===== */
.comment-bar {
  position: fixed;
  bottom: 0; left: var(--sidebar); right: 0;
  background: var(--surface);
  border-top: 1px solid var(--border);
  padding: 12px 40px;
  z-index: 50;
}
.comment-input {
  width: 100%; padding: 12px 16px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--muted2);
  font-family: 'Space Grotesk', sans-serif;
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}
.comment-input:focus { border-color: var(--border2); color: var(--text); }

/* ===== BOTTOM NAV — mobile ===== */
.bottom-nav {
  display: none;
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: var(--surface);
  border-top: 1px solid var(--border);
  height: var(--bottomnav);
  z-index: 200;
  padding: 0 16px;
  justify-content: space-around;
  align-items: center;
}
.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 8px 16px;
  border-radius: 12px;
  transition: all 0.2s;
  color: var(--muted);
  background: transparent;
  border: none;
  font-family: 'Space Grotesk', sans-serif;
}
.bottom-nav-item.active { color: var(--text); }
.bottom-nav-icon { font-size: 20px; }
.bottom-nav-label { font-size: 10px; font-weight: 600; letter-spacing: 0.3px; }

/* ===== MOBILE HEADER ===== */
.mobile-header {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 16px 20px;
  z-index: 200;
  align-items: center;
  justify-content: space-between;
}
.mobile-logo { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 1px; }
.mobile-date { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted); }

/* ===== MOBILE STREAK ===== */
.mobile-streak {
  display: none;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 28px 20px 20px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 20px;
}

/* ===== TOAST ===== */
.toast-wrap { position: fixed; bottom: 80px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.toast {
  padding: 12px 18px; border-radius: 11px;
  font-size: 13px; font-weight: 500;
  background: var(--surface2); border: 1px solid var(--border2); color: var(--text);
  animation: toastIn 0.3s ease;
  display: flex; align-items: center; gap: 8px;
}
@keyframes toastIn { from { opacity:0; transform: translateX(20px); } to { opacity:1; transform:translateX(0); } }
.toast.ok::before { content: '✓'; color: var(--green); }
.toast.err::before { content: '✕'; color: var(--red); }

/* ===== EMPTY ===== */
.empty-state {
  text-align: center; padding: 40px 20px;
  color: var(--muted); font-family: 'DM Mono', monospace; font-size: 13px;
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 99px; }

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .sidebar { display: none; }
  .main {
    margin-left: 0;
    padding: 70px 16px calc(var(--bottomnav) + 16px);
  }
  .bottom-nav { display: flex; }
  .mobile-header { display: flex; }
  .comment-bar { display: none; }
  .streak-section { display: none; }
  .mobile-streak { display: flex; }
  .stat-grid { grid-template-columns: 1fr 1fr; }
  .content-grid { grid-template-columns: 1fr; }
  .fin-grid { grid-template-columns: 1fr; }
  .fin-section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .task-week-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .section-header { flex-direction: column; align-items: flex-start; }
  .section-header .btn { width: 100%; justify-content: center; }
  .card { padding: 18px; }
  .modal { max-width: 100%; padding: 22px; }
}
@media (max-width: 600px) {
  .stat-grid { grid-template-columns: 1fr 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .task-week-grid {
    display: flex;
    gap: 12px;
    padding-bottom: 6px;
  }
  .task-day { min-width: 240px; }
  .fin-stats { grid-template-columns: 1fr; gap: 10px; }
  .fin-stats .stat-card { padding: 16px; border-radius: 14px; }
  .fin-stats .stat-label { font-size: 9px; letter-spacing: 1.5px; }
  .fin-stats .stat-value { font-size: 26px; }
  .fin-stats .stat-sub { font-size: 10px; margin-top: 4px; }
  .btn { width: 100%; justify-content: center; }
  .btn-icon { width: 32px; height: 32px; }
  .task-row { padding: 10px; }
  .task-actions { gap: 4px; }
  .task-mobile-item { padding: 8px 10px; }
  .filter-row { flex-wrap: wrap; }
  .txn-full-item, .txn-item { flex-wrap: wrap; }
  .txn-actions { width: 100%; justify-content: flex-end; }
  .goal-row { gap: 10px; }
  .modal { border-radius: 16px; }
  .bottom-nav { height: 64px; }
}
@media (min-width: 901px) {
  .main { padding-bottom: 80px; }
}
</style>
</head>
<body>

<div class="toast-wrap" id="toastWrap"></div>

<!-- MOBILE HEADER -->
<header class="mobile-header">
  <div class="mobile-logo">MARCOS</div>
  <div class="mobile-date" id="mobileDate">—</div>
</header>

<!-- LAYOUT -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">VIDA EM<br>CONTROLE</div>
      <div class="logo-sub">painel pessoal</div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-item active" onclick="switchTab('habitos')" id="nav-habitos">
        <span class="nav-icon">⊙</span>
        <span class="nav-label">Hábitos</span>
        <span class="nav-badge" id="nb-habitos">—</span>
      </div>
      <div class="nav-item" onclick="switchTab('tarefas')" id="nav-tarefas">
        <span class="nav-icon">≡</span>
        <span class="nav-label">Tarefas</span>
        <span class="nav-badge" id="nb-tarefas">—</span>
      </div>
      <div class="nav-item" onclick="switchTab('financas')" id="nav-financas">
        <span class="nav-icon">◫</span>
        <span class="nav-label">Finanças</span>
      </div>
      <div class="nav-item" onclick="switchTab('metas')" id="nav-metas">
        <span class="nav-icon">◎</span>
        <span class="nav-label">Metas</span>
        <span class="nav-badge" id="nb-metas">—</span>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-date">
        <strong id="sidebarDate">—</strong>
        <span id="sidebarDay">—</span>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- ===== HÁBITOS ===== -->
    <div class="panel active" id="panel-habitos">

      <!-- Desktop streak -->
      <div class="streak-section">
        <div class="streak-circle-wrap">
          <svg width="130" height="130" viewBox="0 0 130 130">
            <circle class="streak-circle-bg" cx="65" cy="65" r="58"/>
            <circle class="streak-circle-fg" id="streakCircle" cx="65" cy="65" r="58"
              stroke-dasharray="364.4" stroke-dashoffset="0"/>
          </svg>
          <div class="streak-number">
            <span class="streak-num" id="streakNum">0</span>
            <span class="streak-label">HABITOS</span>
          </div>
        </div>
        <div class="streak-info">
          <div class="streak-headline" id="streakHeadline">HOJE: <span>0/0 HÁBITOS</span></div>
          <div class="streak-sub" id="streakSub">Se faltar 1, zera.</div>
          <div class="streak-quote" id="streakQuote">"Hoje você vai se trair de novo?"</div>
        </div>
      </div>

      <!-- Mobile streak -->
      <div class="mobile-streak">
        <div class="streak-circle-wrap" style="margin-bottom:20px">
          <svg width="110" height="110" viewBox="0 0 130 130">
            <circle class="streak-circle-bg" cx="65" cy="65" r="58"/>
            <circle class="streak-circle-fg" id="streakCircleMob" cx="65" cy="65" r="58"
              stroke-dasharray="364.4" stroke-dashoffset="0"/>
          </svg>
          <div class="streak-number">
            <span class="streak-num" id="streakNumMob" style="font-size:46px">0</span>
            <span class="streak-label">DIAS</span>
          </div>
        </div>
        <div class="streak-headline" id="streakHeadlineMob" style="font-size:28px;margin-bottom:6px">HOJE: 0/0 HÁBITOS</div>
        <div style="font-size:13px;color:var(--muted);font-family:'DM Mono',monospace;margin-bottom:12px">Se faltar 1, zera.</div>
        <div style="font-size:13px;color:var(--muted2);font-style:italic">"Hoje você vai se trair de novo?"</div>
      </div>

      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
          <div class="card-title" style="margin:0">HÁBITOS</div>
          <button class="btn btn-ghost btn-sm" onclick="openHabitModal()">✏</button>
        </div>
        <div class="habit-list" id="habitList">
          <div class="empty-state">Nenhum hábito cadastrado.</div>
        </div>
        <button class="add-habit-btn" style="margin-top:10px" onclick="openHabitModal()">
          <div class="add-plus">+</div>
          Adicionar hábito
        </button>
      </div>
    </div>

    <!-- ===== TAREFAS ===== -->
    <div class="panel" id="panel-tarefas">
      <div class="section-header">
        <div class="section-title">TAREFAS</div>
        <button class="btn btn-primary" onclick="openTaskModal()">+ Nova Tarefa</button>
      </div>

      <div id="taskSections">
          <div class="empty-state">Carregando tarefas…</div>
      </div>
    </div>

    <!-- ===== FINANÇAS ===== -->
    <div class="panel" id="panel-financas">
      <div class="fin-section-header">
        <div class="fin-month-nav">
          <button class="month-btn" onclick="changeMonth(-1)">‹</button>
          <span class="month-label" id="finMonthLabel">—</span>
          <button class="month-btn" onclick="changeMonth(1)">›</button>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-primary" onclick="openTxnModal()">+ Lançamento</button>
          <button class="btn btn-ghost btn-sm" onclick="openInitialBalanceModal()">Saldo inicial</button>
          <button class="btn btn-ghost btn-sm" onclick="openCatModal()">⚙ Categorias</button>
        </div>
      </div>

      <div class="stat-grid fin-stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px">
        <div class="stat-card green">
          <div class="stat-label">Receitas</div>
          <div class="stat-value green" id="finIncome">R$ 0</div>
        </div>
        <div class="stat-card red">
          <div class="stat-label">Despesas</div>
          <div class="stat-value red" id="finExpense">R$ 0</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-label">Saldo</div>
          <div class="stat-value blue" id="finBalance">R$ 0</div>
          <div class="stat-sub" id="finInitialSub">saldo inicial: R$ 0</div>
        </div>
      </div>

      <div class="fin-grid">
        <div>
          <div class="filter-row">
            <button class="filter-chip active" onclick="setFinFilter('all',this)">TODOS</button>
            <button class="filter-chip" onclick="setFinFilter('income',this)">RECEITAS</button>
            <button class="filter-chip" onclick="setFinFilter('expense',this)">DESPESAS</button>
          </div>
          <div class="txn-full" id="txnFullList">
            <div class="empty-state">Nenhum lançamento.</div>
          </div>
        </div>
        <div class="card">
          <div class="card-title">POR CATEGORIA</div>
          <div class="cat-breakdown" id="catBreakdown">
            <div class="empty-state">Sem dados.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== METAS ===== -->
    <div class="panel" id="panel-metas">
      <div class="section-header">
        <div class="section-title">METAS</div>
        <button class="btn btn-primary" onclick="openGoalModal()">+ Nova Meta</button>
      </div>
      <div class="goals-list" id="goalsGrid">
        <div class="empty-state">Nenhuma meta cadastrada.</div>
      </div>
    </div>

  </main>
</div>


<!-- BOTTOM NAV (mobile) -->
<nav class="bottom-nav">
  <button class="bottom-nav-item active" onclick="switchTab('habitos')" id="bn-habitos">
    <span class="bottom-nav-icon">⊙</span>
    <span class="bottom-nav-label">Hábitos</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('tarefas')" id="bn-tarefas">
    <span class="bottom-nav-icon">≡</span>
    <span class="bottom-nav-label">Tarefas</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('financas')" id="bn-financas">
    <span class="bottom-nav-icon">◫</span>
    <span class="bottom-nav-label">Finanças</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('metas')" id="bn-metas">
    <span class="bottom-nav-icon">◎</span>
    <span class="bottom-nav-label">Metas</span>
  </button>
</nav>

<!-- ===== MODAL: HABIT ===== -->
<div class="modal-overlay" id="habitModal">
  <div class="modal">
    <div class="modal-title">Novo Hábito</div>
    <div class="form-group">
      <label class="form-label">NOME</label>
      <input type="text" id="h-title" class="form-control" placeholder="Ex: Tomar remédio, Meditar…">
    </div>
    <input type="hidden" id="h-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('habitModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveHabit()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: TASK ===== -->
<div class="modal-overlay" id="taskModal">
  <div class="modal">
    <div class="modal-title" id="taskModalTitle">Nova Tarefa</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="t-title" class="form-control" placeholder="Ex: Revisar código, Enviar relatório…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">RECORRÊNCIA</label>
        <select id="t-recurrence" class="form-control" onchange="toggleRecDay()">
            <option value="daily">Todo dia</option>
          <option value="weekly">Toda semana</option>
          <option value="monthly">Todo mês</option>
          <option value="once">Não repetir</option>
        </select>
      </div>
      <div class="form-group" id="t-recday-group">
        <label class="form-label" id="t-recday-label">DIA DA SEMANA</label>
        <select id="t-recday" class="form-control"></select>
      </div>
    </div>
      <div class="form-group" id="t-once-group" style="display:none">
        <label class="form-label">DATA</label>
        <input type="date" id="t-once-date" class="form-control">
      </div>
    <input type="hidden" id="t-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('taskModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveTask()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: TRANSACTION ===== -->
<div class="modal-overlay" id="txnModal">
  <div class="modal">
    <div class="modal-title">Novo Lançamento</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">TIPO</label>
        <select id="txn-type" class="form-control" onchange="loadCatsInModal()">
          <option value="expense">Despesa</option>
          <option value="income">Receita</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">VALOR (R$)</label>
        <input type="number" id="txn-amount" class="form-control" placeholder="0,00" min="0" step="0.01">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">DESCRIÇÃO</label>
      <input type="text" id="txn-desc" class="form-control" placeholder="Ex: Supermercado, Salário…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">CATEGORIA</label>
        <select id="txn-cat" class="form-control"><option value="">— sem categoria —</option></select>
      </div>
      <div class="form-group">
        <label class="form-label">DATA</label>
        <input type="date" id="txn-date" class="form-control">
      </div>
    </div>
    <input type="hidden" id="txn-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('txnModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveTxn()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: CATEGORY ===== -->
<div class="modal-overlay" id="catModal">
  <div class="modal">
    <div class="modal-title">Categorias</div>
    <div id="catListModal" style="margin-bottom:20px;display:flex;flex-direction:column;gap:8px"></div>
    <hr style="border:none;border-top:1px solid var(--border);margin-bottom:20px">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">NOME</label>
        <input type="text" id="cat-name" class="form-control" placeholder="Alimentação">
      </div>
      <div class="form-group">
        <label class="form-label">TIPO</label>
        <select id="cat-type" class="form-control">
          <option value="expense">Despesa</option>
          <option value="income">Receita</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">COR</label>
      <div class="color-row" id="catColorRow"></div>
      <input type="hidden" id="cat-color-val" value="#10d9a0">
    </div>
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('catModal')">Fechar</button>
      <button class="btn btn-primary" onclick="saveCat()">Adicionar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: GOAL ===== -->
<div class="modal-overlay" id="goalModal">
  <div class="modal">
    <div class="modal-title" id="goalModalTitle">Nova Meta</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="g-title" class="form-control" placeholder="Ex: Reserva de emergência…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">VALOR ALVO (R$)</label>
        <input type="number" id="g-target" class="form-control" placeholder="0,00">
      </div>
      <div class="form-group">
        <label class="form-label">JÁ TENHO (R$)</label>
        <input type="number" id="g-current" class="form-control" placeholder="0,00">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">PRAZO</label>
        <input type="date" id="g-deadline" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">COR</label>
        <div class="color-row" id="goalColorRow"></div>
        <input type="hidden" id="g-color" value="#10d9a0">
      </div>
    </div>
    <input type="hidden" id="g-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('goalModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveGoal()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: DEPOSIT ===== -->
<div class="modal-overlay" id="depositModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-title">Adicionar à Meta</div>
    <div class="form-group">
      <label class="form-label">VALOR (R$)</label>
      <input type="number" id="dep-amount" class="form-control" placeholder="0,00" min="0" step="0.01">
    </div>
    <input type="hidden" id="dep-goal-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('depositModal')">Cancelar</button>
      <button class="btn btn-green" onclick="doDeposit()">Adicionar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: INITIAL BALANCE ===== -->
<div class="modal-overlay" id="initialBalanceModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-title">Saldo Inicial</div>
    <div class="form-group">
      <label class="form-label">VALOR (R$)</label>
      <input type="number" id="initial-balance" class="form-control" placeholder="0,00" step="0.01">
    </div>
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('initialBalanceModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveInitialBalance()">Salvar</button>
    </div>
  </div>
</div>

<script>
// ===== CONFIG =====
const COLORS = ['#10d9a0','#4da6ff','#a78bfa','#f5c842','#ff4d6d','#fb923c','#e879f9','#34d399','#f87171','#ffffff'];
const DAYS_WEEK = ['','Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
const MONTHS_PT = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const MONTHS_FULL = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const STREAK_DAYS = 7; // configurable

let allTasks = [], allTxns = [], allCats = [], allGoals = [];
let allHabits = [];
let finFilter = 'all';
let currentMonth = new Date();

// ===== UTILS =====
function fmtBRL(v) {
  return 'R$ ' + parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function fmtDate(d) {
  if (!d) return '';
  const p = d.split('-');
  return p[2]+'/'+p[1]+'/'+p[0];
}
function esc(s) {
  return (s||'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
const API_URL = 'api_lifeos.php';
function api(action, method='GET', body=null, params='') {
  const url = `${API_URL}?api=${action}${params}`;
  const opts = { method, headers: {'Content-Type':'application/json'} };
  if (body) opts.body = JSON.stringify(body);
  return fetch(url, opts).then(r=>r.json().catch(() => ({ ok:false, error:'Resposta invalida da API.' })));
}
function toast(msg, type='ok') {
  const w = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.textContent = msg;
  w.appendChild(t);
  setTimeout(()=>t.remove(), 3000);
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function getMonthStr() {
  return currentMonth.getFullYear()+'-'+String(currentMonth.getMonth()+1).padStart(2,'0');
}
function toISODate(d) { return d.toISOString().split('T')[0]; }
function dayIndexFromDate(d) { const j = d.getDay(); return j===0?7:j; }

function taskAppliesToDate(t, dateObj) {
  const rec = t.recurrence||'weekly';
  const di = dayIndexFromDate(dateObj);
  if (rec==='daily') return true;
  if (rec==='weekly') return parseInt(t.recurrence_day||0)===di;
  if (rec==='once') return t.due_date && toISODate(dateObj)===t.due_date;
  if (rec==='monthly') return parseInt(t.recurrence_day||0)===dateObj.getDate();
  return false;
}

// ===== DATES =====
function initDates() {
  const now = new Date();
  const days = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
  const dateStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;
  const dayStr = days[now.getDay()];
  document.getElementById('sidebarDate').textContent = dateStr;
  document.getElementById('sidebarDay').textContent = dayStr;
  document.getElementById('mobileDate').textContent = dateStr;
  document.getElementById('finMonthLabel').textContent = MONTHS_FULL[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear();
}

// ===== TABS =====
function switchTab(tab) {
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.querySelectorAll('.bottom-nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('panel-'+tab).classList.add('active');
  const nv = document.getElementById('nav-'+tab);
  if (nv) nv.classList.add('active');
  const bn = document.getElementById('bn-'+tab);
  if (bn) bn.classList.add('active');
  if (tab==='habitos') loadHabits();
  if (tab==='financas') loadFinance();
  if (tab==='metas') loadGoals();
}

// ===== STREAK =====
let streakView = 'hoje';
function setStreakView(v) {
  streakView = v;
  document.querySelectorAll('.streak-btn').forEach(b=>{
    b.classList.toggle('active', b.textContent.toLowerCase()===v);
  });
  renderStreak();
}

function renderStreak() {
  const total = allHabits.length;
  const done = allHabits.filter(h=>h._done).length;
  const pct = total > 0 ? done/total : 0;
  const circ = 364.4;
  const offset = circ * (1 - pct);

  document.getElementById('streakCircle').style.strokeDashoffset = offset;
  document.getElementById('streakCircleMob').style.strokeDashoffset = offset;
  document.getElementById('streakNum').textContent = STREAK_DAYS;
  document.getElementById('streakNumMob').textContent = STREAK_DAYS;

  const headline = `HOJE: ${done}/${total} HÁBITOS`;
  document.getElementById('streakHeadline').innerHTML = `HOJE: <span>${done}/${total} HÁBITOS</span>`;
  document.getElementById('streakHeadlineMob').textContent = headline;

  document.getElementById('hStatHoje').textContent = `${done}/${total}`;
  document.getElementById('hStatStreak').textContent = STREAK_DAYS;
  document.getElementById('hStatSemana').textContent = total > 0 ? Math.round(done/total*100)+'%' : '0%';

  document.getElementById('nb-habitos').textContent = `${done}/${total}`;
}

// ===== HABITS =====
async function loadHabits() {
  const res = await api('habits_list');
  if (!res.ok) {
    const el = document.getElementById('habitList');
    if (el) {
      el.innerHTML = '<div class="empty-state">Erro ao carregar hábitos.</div>';
    }
    toast(res.error || 'Erro ao carregar hábitos.', 'err');
    return;
  }
  const today = toISODate(new Date());
  allHabits = (res.data || []).map(h => {
    let dates = [];
    try { dates = JSON.parse(h.checked_dates || '[]'); } catch (e) { dates = []; }
    return {
      id: h.id,
      title: h.name,
      _done: Array.isArray(dates) && dates.includes(today)
    };
  });
  renderHabits();
  renderStreak();
}

function renderHabits() {
  const el = document.getElementById('habitList');
  if (!allHabits.length) {
    el.innerHTML = '<div class="empty-state">Nenhum hábito cadastrado.</div>';
    return;
  }
  el.innerHTML = allHabits.map(h => `
    <div class="habit-item${h._done?' done':''}" onclick="toggleHabit(${h.id})">
      <div class="habit-check">${h._done?'✓':''}</div>
      <div class="habit-info">
        <div class="habit-name">${esc(h.title)}</div>
      </div>
      <div class="habit-actions">
        <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteHabit(${h.id})">✕</button>
      </div>
    </div>
  `).join('');
}

function openHabitModal() {
  document.getElementById('h-id').value = '';
  document.getElementById('h-title').value = '';
  openModal('habitModal');
}
async function saveHabit() {
  const title = document.getElementById('h-title').value.trim();
  if (!title) { toast('Informe o nome','err'); return; }
  const res = await api('habit_save','POST',{name:title});
  if (res.ok) { toast('Hábito adicionado!'); closeModal('habitModal'); loadHabits(); }
  else toast(res.error||'Erro','err');
}
async function toggleHabit(id) {
  const today = toISODate(new Date());
  const res = await api('habit_toggle','POST',{id, date: today});
  if (res.ok) { loadHabits(); }
  else toast(res.error||'Erro','err');
}
async function deleteHabit(id) {
  if (!confirm('Excluir?')) return;
  const res = await api('habit_delete','POST',{id});
  if (res.ok) { loadHabits(); }
  else toast(res.error||'Erro','err');
}

// ===== TASKS =====
async function loadTasks() {
  const res = await api('tasks_list');
  if (!res.ok) {
    toast(res.error || 'Erro ao carregar tarefas.', 'err');
    return;
  }
  allTasks = res.data || [];
  renderTasks();
  const overdue = allTasks.filter(t => t.due_date && t.due_date < toISODate(new Date()) && t.recurrence==='once' && !t.status).length;
  document.getElementById('hStatVencidas').textContent = overdue;
  document.getElementById('nb-tarefas').textContent = allTasks.length;
}

function renderTasks() {
  const el = document.getElementById('taskSections');
  const today = new Date();
  const todayISO = toISODate(today);

  function getRecurrenceDay(t) {
    const raw = parseInt(t.recurrence_day || 0, 10);
    if (raw > 0) return raw;
    if (t.recurrence === 'weekly') return dayIndexFromDate(today);
    if (t.recurrence === 'monthly') return today.getDate();
    return null;
  }

  const weekStart = new Date(today);
  weekStart.setDate(today.getDate() - ((today.getDay() + 6) % 7));
  const weekDates = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart);
    d.setDate(weekStart.getDate() + i);
    return d;
  });

  const dayBuckets = weekDates.map(() => []);
  const outOfWeek = [];

  allTasks.forEach(t => {
    const rec = t.recurrence || 'weekly';
    if (rec === 'daily') {
      dayBuckets.forEach((list, i) => list.push({ task: t, date: weekDates[i] }));
      return;
    }
    if (rec === 'weekly') {
      const idx = Math.max(1, getRecurrenceDay(t) || 1) - 1;
      if (idx >= 0 && idx < 7) dayBuckets[idx].push({ task: t, date: weekDates[idx] });
      return;
    }
    if (rec === 'monthly') {
      const dayNum = getRecurrenceDay(t) || today.getDate();
      const idx = weekDates.findIndex(d => d.getDate() === dayNum);
      if (idx >= 0) dayBuckets[idx].push({ task: t, date: weekDates[idx] });
      else outOfWeek.push(t);
      return;
    }
    if (rec === 'once') {
      if (t.due_date) {
        const idx = weekDates.findIndex(d => toISODate(d) === t.due_date);
        if (idx >= 0) dayBuckets[idx].push({ task: t, date: weekDates[idx] });
        else outOfWeek.push(t);
      } else {
        const idx = dayIndexFromDate(today) - 1;
        dayBuckets[idx].push({ task: t, date: weekDates[idx] });
      }
    }
  });

  const grid = `<div class="task-week-wrap"><div class="task-week-grid">${weekDates.map((d, i) => {
    const dayLabel = DAYS_WEEK[i + 1] || '';
    const list = dayBuckets[i];
    const items = list.length ? list.map(entry => taskRowHTML(entry.task, entry.date, todayISO)).join('') : '<div class="task-day-empty">Sem tarefas</div>';
    return `<div class="task-day">
      <div class="task-day-header"><strong>${dayLabel}</strong></div>
      <div class="task-day-list">${items}</div>
    </div>`;
  }).join('')}</div></div>`;
  if (window.innerWidth <= 600) {
    const mobile = `<div class="task-mobile-list">${weekDates.map((d, i) => {
      const dayLabel = DAYS_WEEK[i + 1] || '';
      const list = dayBuckets[i];
      const items = list.length ? list.map(entry => taskMobileItemHTML(entry.task)).join('') : '<div class="task-day-empty">Sem tarefas</div>';
      return `<div class="task-mobile-card">
        <div class="task-mobile-title">${dayLabel}<span class="task-mobile-count">${list.length}</span></div>
        <div class="task-mobile-items">${items}</div>
      </div>`;
    }).join('')}</div>`;
    el.innerHTML = mobile;
  } else {
    el.innerHTML = grid;
  }
}

function taskMobileItemHTML(t) {
  return `<div class="task-mobile-item">
    <div class="task-mobile-item-title">${esc(t.title)}</div>
    <div class="task-mobile-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="editTask(${t.id})">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="deleteTask(${t.id})">✕</button>
    </div>
  </div>`;
}

function taskRowHTML(t, dateObj, todayISO, compact=false) {
  const isToday = dateObj ? toISODate(dateObj) === todayISO : false;
  const dueMatch = dateObj && t.due_date && toISODate(dateObj) === t.due_date;
  const isDone = (isToday || dueMatch) && parseInt(t.done_today || 0, 10) === 1;
  const isOverdue = t.recurrence === 'once' && t.due_date && t.due_date < todayISO && !t.status;
  const dateLabel = t.due_date ? `<span class="task-date${isOverdue ? ' overdue' : ''}">⊙ ${fmtDate(t.due_date)}</span>` : '';
  const recLabel = t.recurrence === 'daily' ? 'Diaria' :
    t.recurrence === 'weekly' ? 'Semanal' :
    t.recurrence === 'monthly' ? 'Mensal' :
    t.recurrence === 'once' ? 'Sem repetir' : 'Semanal';
  const meta = `<div class="task-row-meta">${recLabel}</div>`;
  return `<div class="task-row${isDone ? ' done' : ''}${isOverdue ? ' overdue' : ''}">
    <div class="task-title">
      <div>${esc(t.title)}</div>
      ${meta}
    </div>
    <div class="task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="editTask(${t.id})">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="deleteTask(${t.id})">✕</button>
    </div>
  </div>`;
}

async function toggleTask(id) {
  await api('task_toggle','POST',{id});
  loadTasks();
}

function openTaskModal(editData=null) {
  document.getElementById('t-id').value = editData?.id||'';
  document.getElementById('t-title').value = editData?.title||'';
  const rec = editData?.recurrence||'weekly';
  document.getElementById('t-recurrence').value = rec;
  document.getElementById('t-once-date').value = editData?.due_date || '';
  toggleRecDay(editData?.recurrence_day);
  document.getElementById('taskModalTitle').textContent = editData ? 'Editar Tarefa' : 'Nova Tarefa';
  openModal('taskModal');
}
function editTask(id) { const t = allTasks.find(x=>x.id==id); if(t) openTaskModal(t); }
function toggleRecDay(selected=null) {
  const rec = document.getElementById('t-recurrence').value;
  const sel = document.getElementById('t-recday');
  const label = document.getElementById('t-recday-label');
  const recGroup = document.getElementById('t-recday-group');
  const onceGroup = document.getElementById('t-once-group');
  recGroup.style.display = (rec === 'weekly' || rec === 'monthly') ? 'block' : 'none';
  onceGroup.style.display = rec === 'once' ? 'block' : 'none';
  if (rec === 'daily') return;
  if (rec === 'once') return;
  if (rec==='monthly') {
    label.textContent = 'DIA DO MÊS';
    const pick = selected || new Date().getDate();
    sel.innerHTML = Array.from({length:31},(_,i)=>`<option value="${i+1}" ${pick==i+1?'selected':''}>${i+1}</option>`).join('');
  } else {
    label.textContent = 'DIA DA SEMANA';
    const pick = selected || dayIndexFromDate(new Date());
    sel.innerHTML = DAYS_WEEK.map((d,i)=>i===0?'':
      `<option value="${i}" ${pick==i?'selected':''}>${d}</option>`).join('');
  }
}
async function saveTask() {
  const title = document.getElementById('t-title').value.trim();
  if (!title) { toast('Informe o título','err'); return; }
  const rec = document.getElementById('t-recurrence').value;
  const recDay = document.getElementById('t-recday').value;
  const dueDate = rec === 'once' ? document.getElementById('t-once-date').value : null;
  const body = {
    id: document.getElementById('t-id').value,
    title,
    recurrence: rec,
    recurrence_day: (rec === 'weekly' || rec === 'monthly') ? recDay : null,
    color: '#ffffff',
    due_date: rec === 'once' ? dueDate : null
  };
  const res = await api('task_save','POST',body);
  if (res.ok) { toast('Salvo!'); closeModal('taskModal'); loadTasks(); }
  else toast(res.error||'Erro','err');
}
async function deleteTask(id) {
  if (!confirm('Excluir?')) return;
  await api('task_delete','POST',{id});
  loadTasks();
}

// ===== FINANCE =====
function updateMonthLabel() {
  document.getElementById('finMonthLabel').textContent = MONTHS_FULL[currentMonth.getMonth()]+' '+currentMonth.getFullYear();
}
function changeMonth(dir) {
  currentMonth.setMonth(currentMonth.getMonth()+dir);
  updateMonthLabel();
  loadFinance();
}

async function loadFinance() {
  updateMonthLabel();
  const month = getMonthStr();
  const [sumRes, txnRes] = await Promise.all([
    api('fin_summary','GET',null,`&month=${month}`),
    api('fin_transactions','GET',null,`&month=${month}`)
  ]);
  if (sumRes.ok) {
    const s = sumRes.data;
    document.getElementById('finIncome').textContent = fmtBRL(s.income);
    document.getElementById('finExpense').textContent = fmtBRL(s.expense);
    const balEl = document.getElementById('finBalance');
    balEl.textContent = fmtBRL(s.balance);
    balEl.className = 'stat-value '+(s.balance>=0?'green':'red');
    document.getElementById('finInitialSub').textContent = `saldo inicial: ${fmtBRL(s.initial_balance||0)}`;
    renderCatBreakdown(s.by_category||[]);
    renderOvTxns(txnRes.data||[]);
  }
  if (txnRes.ok) {
    allTxns = txnRes.data||[];
    renderTxnFull();
  }
}

let finFilterVal = 'all';
function setFinFilter(f, el) {
  finFilterVal = f;
  document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
  el.classList.add('active');
  renderTxnFull();
}

function renderTxnFull() {
  const el = document.getElementById('txnFullList');
  let txns = allTxns;
  if (finFilterVal!=='all') txns = txns.filter(t=>t.type===finFilterVal);
  if (!txns.length) { el.innerHTML = '<div class="empty-state">Nenhum lançamento.</div>'; return; }
  el.innerHTML = txns.map(t => {
    const sign = t.type==='income'?'+':'-';
    return `<div class="txn-full-item">
      <div style="width:8px;height:8px;border-radius:50%;background:${t.cat_color||'var(--muted)'};flex-shrink:0"></div>
      <div class="txn-info" style="flex:1">
        <div class="txn-desc">${esc(t.description||'—')}</div>
        <div class="txn-cat">${esc(t.cat_name||'sem categoria')} · ${fmtDate(t.transaction_date)}</div>
      </div>
      <div class="txn-amount ${t.type}">${sign}${fmtBRL(t.amount)}</div>
      <div class="txn-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editTxn(${t.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteTxn(${t.id})">✕</button>
      </div>
    </div>`;
  }).join('');
}

function renderOvTxns(txns) {
  const el = document.getElementById('ovTxnList');
  if (!el) return;
  const recent = txns.slice(0,4);
  if (!recent.length) { el.innerHTML = '<div class="empty-state">Sem transações.</div>'; return; }
  el.innerHTML = recent.map(t=>{
    const sign = t.type==='income'?'+':'-';
    return `<div class="txn-item">
      <div class="txn-dot" style="background:${t.cat_color||'var(--muted)'}"></div>
      <div class="txn-info">
        <div class="txn-desc">${esc(t.description||'—')}</div>
        <div class="txn-cat">${esc(t.cat_name||'sem categoria')}</div>
      </div>
      <div class="txn-amount ${t.type}">${sign}${fmtBRL(t.amount)}</div>
    </div>`;
  }).join('');
}

function renderCatBreakdown(cats) {
  const el = document.getElementById('catBreakdown');
  const expenses = cats.filter(c=>c.type==='expense');
  if (!expenses.length) { el.innerHTML = '<div class="empty-state">Sem despesas.</div>'; return; }
  const max = Math.max(...expenses.map(c=>parseFloat(c.total)));
  el.innerHTML = expenses.map(c=>{
    const pct = max>0?Math.round(parseFloat(c.total)/max*100):0;
    return `<div class="cat-item">
      <div class="cat-header">
        <div class="cat-name">
          <div class="cat-dot" style="background:${c.color||'var(--muted)'}"></div>
          ${esc(c.name||'sem cat')}
        </div>
        <div class="cat-val">${fmtBRL(c.total)}</div>
      </div>
      <div class="progress-track">
        <div class="progress-fill" style="width:${pct}%;background:${c.color||'var(--muted)'}"></div>
      </div>
    </div>`;
  }).join('');
}

function openTxnModal() {
  document.getElementById('txn-id').value='';
  document.getElementById('txn-amount').value='';
  document.getElementById('txn-desc').value='';
  document.getElementById('txn-date').value=new Date().toISOString().split('T')[0];
  loadCatsInModal();
  openModal('txnModal');
}
function editTxn(id) {
  const t = allTxns.find(x=>x.id==id); if(!t) return;
  document.getElementById('txn-id').value=t.id;
  document.getElementById('txn-type').value=t.type;
  document.getElementById('txn-amount').value=t.amount;
  document.getElementById('txn-desc').value=t.description||'';
  document.getElementById('txn-date').value=t.transaction_date;
  loadCatsInModal().then(()=>{ document.getElementById('txn-cat').value=t.category_id||''; });
  openModal('txnModal');
}
async function loadCatsInModal() {
  const type = document.getElementById('txn-type').value;
  const sel = document.getElementById('txn-cat');
  sel.innerHTML = '<option value="">— sem categoria —</option>';
  allCats.filter(c=>c.type===type).forEach(c=>{
    const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; sel.appendChild(o);
  });
}
async function saveTxn() {
  const amount = parseFloat(document.getElementById('txn-amount').value);
  if (!amount||amount<=0) { toast('Valor inválido','err'); return; }
  const body = {
    id: document.getElementById('txn-id').value,
    type: document.getElementById('txn-type').value,
    amount,
    description: document.getElementById('txn-desc').value,
    category_id: document.getElementById('txn-cat').value||null,
    date: document.getElementById('txn-date').value
  };
  const res = await api('fin_save','POST',body);
  if (res.ok) { toast('Lançamento salvo!'); closeModal('txnModal'); loadFinance(); }
  else toast(res.error||'Erro','err');
}
async function deleteTxn(id) {
  if (!confirm('Excluir?')) return;
  await api('fin_delete','POST',{id});
  loadFinance();
}

// ===== CATEGORIES =====
async function loadCats() {
  const res = await api('cats_list');
  allCats = res.data||[];
}
function buildColorRow(rowId, selected, onPick) {
  const row = document.getElementById(rowId);
  row.innerHTML = '';
  COLORS.forEach(c=>{
    const s=document.createElement('div');
    s.className='color-swatch'+(c===selected?' selected':'');
    s.style.background=c;
    s.onclick=()=>{ row.querySelectorAll('.color-swatch').forEach(x=>x.classList.remove('selected')); s.classList.add('selected'); onPick(c); };
    row.appendChild(s);
  });
}
function openCatModal() {
  buildColorRow('catColorRow','#10d9a0',c=>document.getElementById('cat-color-val').value=c);
  renderCatListModal();
  openModal('catModal');
}
function renderCatListModal() {
  const el = document.getElementById('catListModal');
  if (!allCats.length) { el.innerHTML='<div class="empty-state">Nenhuma categoria.</div>'; return; }
  el.innerHTML = allCats.map(c=>`
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface2);border-radius:10px">
      <div style="width:8px;height:8px;border-radius:50%;background:${c.color};flex-shrink:0"></div>
      <span style="flex:1;font-size:13px">${esc(c.name)}</span>
      <span style="font-size:10px;font-family:'DM Mono',monospace;color:${c.type==='income'?'var(--green)':'var(--red)'}">${c.type==='income'?'RECEITA':'DESPESA'}</span>
    </div>`).join('');
}
async function saveCat() {
  const name = document.getElementById('cat-name').value.trim();
  if (!name) { toast('Informe o nome','err'); return; }
  const res = await api('cat_save','POST',{name,type:document.getElementById('cat-type').value,color:document.getElementById('cat-color-val').value||'#10d9a0'});
  if (res.ok) { toast('Categoria criada!'); document.getElementById('cat-name').value=''; await loadCats(); renderCatListModal(); }
  else toast(res.error||'Erro','err');
}

// ===== GOALS =====
async function loadGoals() {
  const res = await api('goals_list');
  allGoals = res.data||[];
  renderGoals();
  document.getElementById('nb-metas').textContent = allGoals.length;
}
function renderGoals() {
  const el = document.getElementById('goalsGrid');
  if (!allGoals.length) { el.innerHTML='<div class="empty-state">Nenhuma meta cadastrada.</div>'; return; }
  const ordered = [...allGoals].sort((a,b)=>{
    const ad = parseInt(a.status || 0, 10);
    const bd = parseInt(b.status || 0, 10);
    if (ad !== bd) return ad - bd;
    return b.id - a.id;
  });
  el.innerHTML = ordered.map(g=>{
    const done = parseInt(g.status || 0, 10) === 1;
    return `<div class="goal-row${done ? ' done' : ''}">
      <div class="goal-check" onclick="toggleGoal(${g.id})">${done ? '✓' : ''}</div>
      <div class="goal-title">${esc(g.title)}</div>
      <div class="goal-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editGoal(${g.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteGoal(${g.id})">✕</button>
      </div>
    </div>`;
  }).join('');
}
function renderOvGoals() {
  const el = document.getElementById('ovGoalList');
  if (!el) return;
  if (!allGoals.length) { el.innerHTML='<div class="empty-state">Sem metas.</div>'; return; }
  el.innerHTML = allGoals.slice(0,3).map(g=>{
    const target = parseFloat(g.target_amount) || 0;
    const current = parseFloat(g.current_amount) || 0;
    const pct = target > 0 ? Math.min(100, Math.round(current / target * 100)) : 0;
    return `<div class="goal-item">
      <div class="goal-header">
        <div class="goal-name">${esc(g.title)}</div>
        <div class="goal-pct">${pct}%</div>
      </div>
      <div class="progress-track">
        <div class="progress-fill" style="width:${pct}%;background:${g.color||'#10d9a0'}"></div>
      </div>
    </div>`;
  }).join('');
}
function openGoalModal(editData=null) {
  document.getElementById('g-id').value=editData?.id||'';
  document.getElementById('g-title').value=editData?.title||'';
  document.getElementById('g-target').value=editData?.target_amount||'';
  document.getElementById('g-current').value=editData?.current_amount||'';
  document.getElementById('g-deadline').value=editData?.deadline||'';
  const color=editData?.color||'#10d9a0';
  document.getElementById('g-color').value=color;
  buildColorRow('goalColorRow',color,c=>document.getElementById('g-color').value=c);
  document.getElementById('goalModalTitle').textContent=editData?'Editar Meta':'Nova Meta';
  openModal('goalModal');
}
function editGoal(id) { const g=allGoals.find(x=>x.id==id); if(g) openGoalModal(g); }
async function toggleGoal(id) {
  await api('goal_toggle','POST',{id});
  loadGoals();
}
async function saveGoal() {
  const title=document.getElementById('g-title').value.trim();
  const target=parseFloat(document.getElementById('g-target').value);
  if (!title||!target) { toast('Preencha título e valor','err'); return; }
  const body={id:document.getElementById('g-id').value,title,target_amount:target,current_amount:parseFloat(document.getElementById('g-current').value)||0,deadline:document.getElementById('g-deadline').value||null,color:document.getElementById('g-color').value};
  const res=await api('goal_save','POST',body);
  if (res.ok) { toast('Meta salva!'); closeModal('goalModal'); loadGoals(); }
  else toast(res.error||'Erro','err');
}
async function deleteGoal(id) {
  if (!confirm('Excluir?')) return;
  await api('goal_delete','POST',{id});
  loadGoals();
}
function openDeposit(id) {
  document.getElementById('dep-goal-id').value=id;
  document.getElementById('dep-amount').value='';
  openModal('depositModal');
}
async function doDeposit() {
  const amount=parseFloat(document.getElementById('dep-amount').value);
  if (!amount||amount<=0) { toast('Informe um valor','err'); return; }
  const id=document.getElementById('dep-goal-id').value;
  const res=await api('goal_deposit','POST',{id,amount});
  if (res.ok) { toast('Valor adicionado!'); closeModal('depositModal'); loadGoals(); }
  else toast(res.error||'Erro','err');
}
function openInitialBalanceModal() {
  api('fin_settings_get').then(res=>{
    document.getElementById('initial-balance').value=res.ok?res.data.initial_balance:0;
    openModal('initialBalanceModal');
  });
}
async function saveInitialBalance() {
  const val=parseFloat(document.getElementById('initial-balance').value);
  if (isNaN(val)) { toast('Valor inválido','err'); return; }
  const res=await api('fin_settings_save','POST',{initial_balance:val});
  if (res.ok) { toast('Saldo inicial salvo!'); closeModal('initialBalanceModal'); loadFinance(); }
  else toast(res.error||'Erro','err');
}

// Close on outside click
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click',e=>{ if(e.target===o) o.classList.remove('open'); });
});

// ===== INIT =====
async function init() {
  initDates();
  await loadCats();
  await Promise.all([loadTasks(), loadFinance(), loadGoals()]);
  await loadHabits();
}
init();
</script>
</body>
</html>