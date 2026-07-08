<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Vida em Controle — Marcos</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0a0a0a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Vida em Controle">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.svg">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'%3E%3Crect fill='%230a0a0a' width='192' height='192'/%3E%3Crect x='28' y='36' width='136' height='128' rx='18' stroke='%23ffffff' stroke-width='8' fill='none'/%3E%3Crect x='28' y='56' width='136' height='26' fill='%231a1a1a'/%3E%3Cpath d='M56 28v20M136 28v20' stroke='%23ffffff' stroke-width='8' stroke-linecap='round'/%3E%3Cpath d='M56 104l14 14L98 90' stroke='%23ffffff' stroke-width='8' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M106 104l14 14L148 90' stroke='%23ffffff' stroke-width='8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
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
.sidebar-cal-link {
  display: block;
  margin-top: 14px;
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted2);
  text-decoration: none;
  border: 1px dashed var(--border2);
  border-radius: var(--radius-sm);
  padding: 8px 10px;
  text-align: center;
  transition: all 0.15s;
}
.sidebar-cal-link:hover { color: var(--text); border-color: var(--text); }

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
.panel > .card + .card { margin-top: 20px; }

/* ===== INÍCIO: GRID DE CONTROLE ===== */
.inicio-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; margin-top: 20px; }
.inicio-main, .inicio-sidebar { display: flex; flex-direction: column; gap: 20px; }
.inicio-subgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.ov-fin-balance { font-family: 'DM Mono', monospace; font-size: 28px; font-weight: 700; }
.ov-fin-sub { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 2px; }
.reward-history-title {
  font-family: 'DM Mono', monospace; font-size: 10px; text-transform: uppercase;
  letter-spacing: 2px; color: var(--muted); margin: 24px 0 12px;
  padding-top: 20px; border-top: 1px solid var(--border);
}
.ov-corpo-label { font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); letter-spacing: 1px; }
.ov-corpo-text {
  font-size: 13px; color: var(--text); margin-top: 4px;
  white-space: pre-wrap; max-height: 60px; overflow: hidden;
}
@media (max-width: 1100px) {
  .inicio-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .inicio-subgrid { grid-template-columns: 1fr; }
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
.habit-section { display: flex; flex-direction: column; gap: 8px; }
.habit-section + .habit-section { margin-top: 16px; }
.habit-section-title {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--muted);
  margin: 4px 0 6px;
}

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
.habit-streak {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted2);
  min-width: 68px;
  text-align: right;
  white-space: nowrap;
}

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
.task-day.is-today {
  border-color: rgba(255,255,255,0.3);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.08);
}
.task-day.is-today .task-day-header {
  background: var(--surface3);
  padding: 6px 8px;
  border-radius: 10px;
  color: var(--text);
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
.task-row.habit {
  border-style: dashed;
}
.task-row.habit .task-row-meta {
  color: var(--yellow);
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
.task-mobile-card.is-today {
  border-color: rgba(255,255,255,0.3);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.08);
}
.task-mobile-card.is-today .task-mobile-title {
  color: var(--text);
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
.task-mobile-item.done { opacity: 0.5; }
.task-mobile-item.done .task-mobile-item-title { text-decoration: line-through; }
.task-mobile-item-title { font-size: 13px; }
.task-mobile-actions { display: flex; gap: 6px; }
.task-row.done { opacity: 0.5; }
.task-row.overdue { border-color: rgba(255,77,109,0.2); }
.task-title { flex: 1; font-size: 13px; }
.task-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.task-row:hover .task-actions { opacity: 1; }
.task-item:hover .task-actions { opacity: 1; }

/* ===== EVENTS ===== */
.events-layout {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 20px;
}
.calendar-wrap {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  overflow-x: auto;
}
.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.calendar-title {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
}
.calendar-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}
.calendar-nav button {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--surface2);
  color: var(--text);
  cursor: pointer;
}
.calendar-month {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
}
.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  margin-bottom: 6px;
}
.calendar-weekdays div {
  font-size: 10px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  text-align: center;
}
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  min-width: 560px;
}
.calendar-mobile-list {
  display: none;
  flex-direction: column;
  gap: 8px;
}
.calendar-mobile-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  cursor: pointer;
}
.calendar-mobile-row.is-selected {
  border-color: rgba(255,255,255,0.3);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.12) inset;
}
.calendar-mobile-day {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  min-width: 70px;
}
.calendar-mobile-events {
  flex: 1;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.calendar-day {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  min-height: 88px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  cursor: pointer;
}
.calendar-day.outside { opacity: 0.45; }
.calendar-day.is-today { border-color: rgba(255,255,255,0.3); }
.calendar-day.is-selected { box-shadow: 0 0 0 1px rgba(255,255,255,0.18) inset; }
.calendar-day-number {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
}
.calendar-day-number strong { color: var(--text); }
.event-chip {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
  background: var(--surface3);
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.event-chip.more { background: transparent; color: var(--muted); }
.event-list {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.event-list-title {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
}
.event-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: var(--surface2);
  border-radius: 10px;
  border: 1px solid var(--border);
}
.event-item-title { font-size: 13px; }
.event-item-meta { font-size: 10px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 4px; }
.event-item-actions { display: flex; gap: 6px; }
.event-days {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 6px;
}
.event-day-check {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--surface2);
  font-size: 11px;
  font-family: 'DM Mono', monospace;
  color: var(--muted);
}
.event-day-check input { accent-color: #ffffff; }

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
.goals-split-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}
@media (max-width: 900px) {
  .goals-split-grid { grid-template-columns: 1fr; }
}
.form-check {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted2);
}
.form-check input { width: 16px; height: 16px; }

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
  padding: 0 4px;
  justify-content: space-around;
  align-items: center;
  overflow-x: auto;
}
.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 8px 10px;
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
  .events-layout { grid-template-columns: 1fr; }
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
  .calendar-weekdays { display: none; }
  .calendar-grid { display: none; }
  .calendar-mobile-list { display: flex; }
  .event-days { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (min-width: 901px) {
  .main { padding-bottom: 80px; }
}

/* ===== WEEKLY PLANNER (accordion) ===== */
.weekly-planner { display: flex; flex-direction: column; gap: 0; }
.week-summary {
  display: flex; align-items: center; gap: 14px; margin-bottom: 28px;
}
.week-progress-bar {
  flex: 1; height: 3px; background: var(--surface3); border-radius: 99px; overflow: hidden;
}
.week-progress-fill {
  height: 100%; background: var(--green); border-radius: 99px; transition: width 0.5s ease;
}
.week-summary-text {
  font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted);
  text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;
}
.week-days { display: flex; flex-direction: column; }
.week-day-row {
  border-top: 1px solid var(--border);
}
.week-day-row:last-child { border-bottom: 1px solid var(--border); }
.week-day-hdr {
  display: flex; align-items: center; gap: 14px; padding: 14px 4px; cursor: pointer;
  transition: background 0.1s;
}
.week-day-hdr:hover { background: rgba(255,255,255,0.02); border-radius: 10px; }
.week-day-hdr:hover .week-day-addBtn { opacity: 1; }
.week-day-icon {
  width: 38px; height: 38px; min-width: 38px; border-radius: 12px;
  background: var(--surface2); border: 1px solid var(--border);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  font-family: 'DM Mono', monospace;
}
.week-day-icon .wdi-num {
  font-size: 14px; font-weight: 700; line-height: 1; color: var(--muted2);
}
.week-day-icon .wdi-mon {
  font-size: 8px; color: var(--muted); letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.4;
}
.week-day-row.is-today .week-day-icon {
  background: var(--text); border-color: var(--text);
}
.week-day-row.is-today .week-day-icon .wdi-num { color: var(--bg); }
.week-day-row.is-today .week-day-icon .wdi-mon { color: rgba(0,0,0,0.5); }
.week-day-name-wrap { flex: 1; min-width: 0; }
.week-day-name {
  font-size: 14px; font-weight: 600; color: var(--muted2);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.week-day-row.is-today .week-day-name { color: var(--text); }
.week-day-row.is-past .week-day-name { color: var(--muted); }
.week-day-sub {
  font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); letter-spacing: 0.3px; margin-top: 1px;
}
.week-day-count {
  font-family: 'DM Mono', monospace; font-size: 10px;
  color: var(--muted); background: var(--surface2);
  border: 1px solid var(--border); padding: 3px 10px; border-radius: 999px;
  white-space: nowrap; flex-shrink: 0;
}
.week-day-row.is-today .week-day-count { color: var(--muted2); border-color: var(--border2); }
.week-day-addBtn {
  opacity: 0; width: 28px; height: 28px; border-radius: 8px;
  border: 1px solid var(--border2); background: transparent; color: var(--muted2);
  cursor: pointer; font-size: 18px; line-height: 1; display: flex; align-items: center;
  justify-content: center; flex-shrink: 0; transition: all 0.15s;
}
.week-day-addBtn:hover { background: var(--surface2); color: var(--text); }
.week-day-chevron {
  color: var(--muted); font-size: 14px; flex-shrink: 0;
  transition: transform 0.2s; transform: rotate(0deg);
}
.week-day-row.expanded .week-day-chevron { transform: rotate(90deg); }
.week-day-body {
  display: none; flex-direction: column; gap: 8px;
  padding: 0 4px 16px 52px;
}
.week-day-row.expanded .week-day-body { display: flex; }
.week-day-empty-label {
  font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted);
  padding: 6px 0; letter-spacing: 0.5px;
}
.week-task-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; background: var(--surface2);
  border: 1px solid var(--border); border-radius: 12px; cursor: pointer;
  transition: border-color 0.15s;
}
.week-task-item:hover { border-color: var(--border2); }
.week-task-item:hover .week-task-actions { opacity: 1; }
.week-task-item.done { opacity: 0.45; }
.week-task-item.habit { border-style: dashed; }
.week-task-check {
  width: 20px; height: 20px; min-width: 20px; border-radius: 50%;
  border: 1.5px solid var(--border2); display: flex; align-items: center;
  justify-content: center; font-size: 11px; color: transparent; transition: all 0.15s;
}
.week-task-item.done .week-task-check { background: var(--green); border-color: var(--green); color: var(--bg); }
.week-task-item.habit .week-task-check { border-color: rgba(245,200,66,0.4); }
.week-task-item.habit.done .week-task-check { background: var(--yellow); border-color: var(--yellow); color: var(--bg); }
.week-task-title {
  flex: 1; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.week-task-item.done .week-task-title { text-decoration: line-through; }
.week-task-badge {
  font-family: 'DM Mono', monospace; font-size: 9px; color: var(--muted);
  background: var(--surface3); padding: 2px 8px; border-radius: 999px;
  white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.5px;
}
.week-task-item.habit .week-task-badge { color: var(--yellow); background: rgba(245,200,66,0.08); }
.week-task-actions { display: flex; gap: 4px; opacity: 0; flex-shrink: 0; transition: opacity 0.2s; }
@media (max-width: 700px) {
  .week-day-body { padding-left: 0; }
  .week-day-addBtn { opacity: 1; }
  .week-task-actions { opacity: 1; }
  .week-day-hdr { gap: 10px; padding: 12px 2px; }
  .week-day-icon { width: 34px; height: 34px; min-width: 34px; border-radius: 10px; }
}

/* ===== A FAZER (undated tasks) ===== */
.undated-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 20px;
  margin-bottom: 24px;
}
.undated-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.undated-title {
  font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted);
  text-transform: uppercase; letter-spacing: 1px;
}
.undated-add {
  font-size: 18px; width: 26px; height: 26px; border-radius: 8px;
  border: 1px solid var(--border2); background: transparent; color: var(--muted2);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  line-height: 1; transition: all 0.15s;
}
.undated-add:hover { background: var(--surface2); color: var(--text); }
.undated-list { display: flex; flex-direction: column; gap: 8px; }
.undated-empty { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; }

/* ===== TASK WEEK HEADER ===== */
.task-week-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 8px;
}
.task-week-label {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  letter-spacing: 1px;
  text-transform: uppercase;
}
.task-day-date {
  font-family: 'DM Mono', monospace;
  font-size: 10px;
  color: var(--muted);
  margin-top: 2px;
}
.task-day.is-today .task-day-date { color: var(--muted2); }
.task-check {
  width: 18px; height: 18px; min-width: 18px;
  border-radius: 50%;
  border: 1.5px solid var(--border2);
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: transparent;
  transition: all 0.15s;
}
.task-row.done .task-check { background: var(--green); border-color: var(--green); color: var(--bg); }
.task-row.habit .task-check { border-color: rgba(245,200,66,0.4); }
.task-row.habit.done .task-check { background: var(--yellow); border-color: var(--yellow); }

/* Task day + chip selector in modal */
.day-chips {
  display: flex; gap: 6px; flex-wrap: wrap;
}
.day-chip {
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid var(--border2);
  background: var(--surface2);
  color: var(--muted2);
  font-size: 12px;
  font-family: 'DM Mono', monospace;
  cursor: pointer;
  transition: all 0.15s;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.day-chip.active {
  background: var(--text);
  border-color: var(--text);
  color: var(--bg);
}
.rec-toggle {
  display: flex; gap: 0; border: 1px solid var(--border2); border-radius: 999px; overflow: hidden;
}
.rec-toggle-btn {
  flex: 1; padding: 8px 14px;
  background: var(--surface2); color: var(--muted2);
  border: none; cursor: pointer;
  font-size: 12px; font-family: 'DM Mono', monospace;
  text-transform: uppercase; letter-spacing: 0.5px;
  transition: all 0.15s;
}
.rec-toggle-btn.active { background: var(--text); color: var(--bg); }

/* ===== CORPO PANEL ===== */
.corpo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.corpo-block {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.corpo-block-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 22px;
  letter-spacing: 1px;
  color: var(--text);
}
.corpo-textarea {
  width: 100%;
  min-height: 360px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-family: 'DM Mono', monospace;
  font-size: 13px;
  line-height: 1.7;
  padding: 14px 16px;
  resize: vertical;
  outline: none;
  transition: border-color 0.15s;
}
.corpo-textarea:focus { border-color: var(--border2); }
.corpo-save-hint {
  font-size: 11px;
  font-family: 'DM Mono', monospace;
  color: var(--muted);
  text-align: right;
  min-height: 16px;
  transition: color 0.3s;
}
.corpo-save-hint.saved { color: var(--green); }
@media (max-width: 900px) {
  .corpo-grid { grid-template-columns: 1fr; }
  .corpo-textarea { min-height: 260px; }
}

/* ===== INÍCIO / XP / RECOMPENSAS ===== */
.xp-hero { position: relative; overflow: visible; }
.xp-hero-top { display: flex; align-items: center; gap: 24px; }
.xp-level-badge {
  flex-shrink: 0;
  width: 78px; height: 78px;
  border-radius: 50%;
  background: linear-gradient(145deg, var(--surface2), var(--surface3));
  border: 2px solid var(--border2);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.xp-level-num { font-family: 'DM Mono', monospace; font-size: 26px; font-weight: 700; line-height: 1; }
.xp-level-label { font-size: 9px; color: var(--muted); letter-spacing: 1px; margin-top: 4px; }
.xp-hero-info { flex: 1; min-width: 0; }
.xp-hero-title { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted); letter-spacing: 1.5px; margin-bottom: 10px; }
.xp-bar-track { background: var(--surface2); border-radius: 99px; height: 14px; overflow: hidden; border: 1px solid var(--border); }
.xp-bar-fill {
  height: 100%; border-radius: 99px;
  background: linear-gradient(90deg, var(--green), #6fe8c9);
  transition: width 0.7s cubic-bezier(.22,1,.36,1);
}
.xp-bar-label { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted2); margin-top: 8px; }
.xp-points-wrap { flex-shrink: 0; text-align: right; position: relative; }
.xp-points-num { font-family: 'DM Mono', monospace; font-size: 30px; font-weight: 700; color: var(--green); line-height: 1; }
.xp-points-label { font-size: 9px; color: var(--muted); letter-spacing: 1px; margin-top: 4px; }
.xp-week-chart { display: flex; align-items: flex-end; gap: 6px; height: 54px; margin-top: 22px; }
.xp-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; }
.xp-bar-fillcol { width: 100%; max-width: 22px; background: var(--surface3); border-radius: 5px 5px 2px 2px; transition: height 0.5s ease; }
.xp-bar-col.is-today .xp-bar-fillcol { background: var(--green); }
.xp-bar-daylabel { font-family: 'DM Mono', monospace; font-size: 9px; color: var(--muted); }
.xp-actions-hdr { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); }

.points-pop {
  position: absolute; right: 8px; top: -6px;
  font-family: 'DM Mono', monospace; font-weight: 700; font-size: 15px;
  color: var(--green);
  pointer-events: none;
  animation: popFloat 1s ease forwards;
}
@keyframes popFloat {
  0% { opacity: 0; transform: translateY(6px) scale(0.9); }
  20% { opacity: 1; transform: translateY(-4px) scale(1.05); }
  100% { opacity: 0; transform: translateY(-34px) scale(1); }
}

.action-list { display: flex; flex-direction: column; gap: 8px; }
.action-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.15s;
}
.action-row:hover { border-color: var(--border2); }
.action-row.done { opacity: 0.55; }
.action-check {
  flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
  border: 2px solid var(--border2);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; color: var(--green);
  transition: all 0.15s;
}
.action-row.done .action-check { background: var(--green); border-color: var(--green); color: var(--bg); }
.action-title { flex: 1; font-size: 14px; }
.action-row.done .action-title { text-decoration: line-through; }
.action-badge {
  font-family: 'DM Mono', monospace; font-size: 11px; font-weight: 600;
  color: var(--green); background: rgba(16,217,160,0.1);
  padding: 3px 8px; border-radius: 99px; flex-shrink: 0;
}
.action-kind-tag { font-size: 10px; color: var(--muted); margin-left: 4px; }

.reward-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap: 12px; }
.reward-card {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px;
  text-align: center;
  transition: all 0.15s;
}
.reward-card.locked { opacity: 0.5; }
.reward-icon { font-size: 30px; margin-bottom: 8px; }
.reward-title { font-size: 13px; margin-bottom: 6px; }
.reward-cost { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--green); margin-bottom: 12px; }
.reward-actions { display: flex; gap: 6px; justify-content: center; align-items: center; }
.reward-actions .btn-primary { flex: 1; }

.redemption-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 0; border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.redemption-row:last-child { border-bottom: none; }
.redemption-cost { font-family: 'DM Mono', monospace; color: var(--red); font-size: 12px; }
.redemption-date { font-family: 'DM Mono', monospace; color: var(--muted); font-size: 11px; }

/* ===== CHAT / AGUA / COMIDA ===== */
.chat-log {
  display: flex; flex-direction: column; gap: 10px;
  max-height: 320px; overflow-y: auto; margin-bottom: 14px;
  padding-right: 4px;
}
.chat-bubble {
  max-width: 85%; padding: 10px 14px; border-radius: 14px;
  font-size: 13.5px; line-height: 1.5; white-space: pre-wrap; word-break: break-word;
}
.chat-bubble.user { align-self: flex-end; background: var(--text); color: var(--bg); border-bottom-right-radius: 4px; }
.chat-bubble.assistant { align-self: flex-start; background: var(--surface2); color: var(--text); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
.chat-bubble.pending { color: var(--muted); font-style: italic; }
.chat-input-row { display: flex; gap: 10px; }
.chat-input-row .form-control { flex: 1; min-width: 0; }
.chat-input-row .btn { flex-shrink: 0; width: auto; }
.card-title + .btn { flex-shrink: 0; width: auto; }

@media (max-width: 900px) {
  .xp-hero-top { flex-wrap: wrap; }
  .xp-points-wrap { text-align: left; }
  .chat-log { max-height: 260px; }
}
@media (max-width: 480px) {
  .chat-input-row .btn { padding: 9px 12px; }
  .reward-grid { grid-template-columns: repeat(auto-fill, minmax(120px,1fr)); }
}
</style>
<script>
if ('serviceWorker' in navigator) {
  let _swRefreshing = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!_swRefreshing) { _swRefreshing = true; window.location.reload(); }
  });
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('./sw.js', { updateViaCache: 'none' })
      .then(reg => reg.update())
      .catch(() => {});
  });
}
</script>
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
      <div class="nav-item active" onclick="switchTab('inicio')" id="nav-inicio">
        <span class="nav-icon">⚡</span>
        <span class="nav-label">Início</span>
        <span class="nav-badge" id="nb-inicio">—</span>
      </div>
      <div class="nav-item" onclick="switchTab('habitos')" id="nav-habitos">
        <span class="nav-icon">⊙</span>
        <span class="nav-label">Hábitos</span>
        <span class="nav-badge" id="nb-habitos">—</span>
      </div>
      <div class="nav-item" onclick="switchTab('tarefas')" id="nav-tarefas">
        <span class="nav-icon">≡</span>
        <span class="nav-label">Tarefas</span>
        <span class="nav-badge" id="nb-tarefas">—</span>
      </div>
      <div class="nav-item" onclick="switchTab('eventos')" id="nav-eventos">
        <span class="nav-icon">✶</span>
        <span class="nav-label">Eventos</span>
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
      <div class="nav-item" onclick="switchTab('corpo')" id="nav-corpo">
        <span class="nav-icon">♡</span>
        <span class="nav-label">Corpo</span>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-date">
        <strong id="sidebarDate">—</strong>
        <span id="sidebarDay">—</span>
      </div>
      <a class="sidebar-cal-link" id="calSyncLink" href="#">📅 Sincronizar Calendário</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- ===== INÍCIO ===== -->
    <div class="panel active" id="panel-inicio">

      <div class="card xp-hero">
        <div class="xp-hero-top">
          <div class="xp-level-badge">
            <div class="xp-level-num" id="xpLevel">1</div>
            <div class="xp-level-label">NÍVEL</div>
          </div>
          <div class="xp-hero-info">
            <div class="xp-hero-title">SEU PROGRESSO</div>
            <div class="xp-bar-track">
              <div class="xp-bar-fill" id="xpBarFill" style="width:0%"></div>
            </div>
            <div class="xp-bar-label" id="xpBarLabel">0 / 100 XP</div>
          </div>
          <div class="xp-points-wrap" id="xpPointsWrap">
            <div class="xp-points-num" id="pointsBalance">0</div>
            <div class="xp-points-label">PONTOS</div>
          </div>
        </div>
        <div class="xp-week-chart" id="xpWeekChart"></div>
      </div>

      <div class="inicio-grid">
        <div class="inicio-main">
          <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap">
              <div class="card-title" style="margin:0">AÇÕES DE HOJE</div>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="xp-actions-hdr" id="inicioActionsHdr">0/0 hoje</div>
                <button class="btn btn-ghost btn-sm" onclick="openActionModal()">+ Nova Ação</button>
              </div>
            </div>
            <div class="action-list" id="inicioActions">
              <div class="empty-state">Carregando…</div>
            </div>
          </div>

          <div class="inicio-subgrid">
            <div class="card">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div class="card-title" style="margin:0">FINANÇAS</div>
                <button class="btn btn-ghost btn-sm" onclick="switchTab('financas')">Ver tudo</button>
              </div>
              <div class="ov-fin-balance" id="ovFinBalance">R$ 0,00</div>
              <div class="ov-fin-sub">saldo do mês</div>
              <div id="ovTxnList" style="margin-top:14px"></div>
            </div>

            <div class="card">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div class="card-title" style="margin:0">METAS</div>
                <button class="btn btn-ghost btn-sm" onclick="switchTab('metas')">Ver tudo</button>
              </div>
              <div id="ovGoalList"></div>
            </div>
          </div>

          <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
              <div class="card-title" style="margin:0">RECOMPENSAS</div>
              <button class="btn btn-ghost btn-sm" onclick="openRewardModal()">+ Nova</button>
            </div>
            <div class="reward-grid" id="rewardsList">
              <div class="empty-state">Carregando…</div>
            </div>
            <div class="reward-history-title">ÚLTIMOS RESGATES</div>
            <div id="redemptionsList">
              <div class="empty-state">Nenhum resgate ainda.</div>
            </div>
          </div>
        </div>

        <div class="inicio-sidebar">
          <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
              <div class="card-title" style="margin:0">PRÓXIMOS EVENTOS</div>
              <button class="btn btn-ghost btn-sm" onclick="switchTab('eventos')">Ver tudo</button>
            </div>
            <div id="ovEventList">
              <div class="empty-state">Sem eventos próximos.</div>
            </div>
          </div>

          <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
              <div class="card-title" style="margin:0">CORPO HOJE</div>
              <button class="btn btn-ghost btn-sm" onclick="switchTab('corpo')">Editar</button>
            </div>
            <div class="ov-corpo-label">TREINO</div>
            <div class="ov-corpo-text" id="ovCorpoTreino">—</div>
            <div class="ov-corpo-label" style="margin-top:12px">DIETA</div>
            <div class="ov-corpo-text" id="ovCorpoDieta">—</div>
          </div>
        </div>
      </div>

    </div>

    <!-- ===== HÁBITOS ===== -->
    <div class="panel" id="panel-habitos">

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
        <div>
          <div class="section-title">SEMANA</div>
          <div class="task-week-label" id="taskWeekLabel">—</div>
        </div>
        <button class="btn btn-primary" onclick="openTaskModal()">+ Atividade</button>
      </div>

      <div id="taskSections">
          <div class="empty-state">Carregando…</div>
      </div>
    </div>

    <!-- ===== EVENTOS ===== -->
    <div class="panel" id="panel-eventos">
      <div class="section-header">
        <div class="section-title">EVENTOS</div>
        <button class="btn btn-primary" onclick="openEventModal()">+ Novo Evento</button>
      </div>
      <div class="events-layout">
        <div class="calendar-wrap">
          <div class="calendar-header">
            <div class="calendar-title">Calendario</div>
            <div class="calendar-nav">
              <button onclick="changeEventMonth(-1)">‹</button>
              <span class="calendar-month" id="eventMonthLabel">—</span>
              <button onclick="changeEventMonth(1)">›</button>
            </div>
          </div>
          <div class="calendar-weekdays" id="eventWeekdays"></div>
          <div class="calendar-grid" id="eventCalendar"></div>
          <div class="calendar-mobile-list" id="eventCalendarMobile"></div>
        </div>
        <div class="event-list">
          <div class="event-list-title" id="eventListTitle">Eventos do dia</div>
          <div id="eventDayList">
            <div class="empty-state">Sem eventos.</div>
          </div>
        </div>
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
      <div class="goals-split-grid">
        <div class="card">
          <div class="card-title" style="margin-bottom:12px">CURTO PRAZO</div>
          <div class="goals-list" id="goalsShort">
            <div class="empty-state">Sem metas de curto prazo.</div>
          </div>
        </div>
        <div class="card">
          <div class="card-title" style="margin-bottom:12px">LONGO PRAZO</div>
          <div class="goals-list" id="goalsLong">
            <div class="empty-state">Sem metas de longo prazo.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== CORPO ===== -->
    <div class="panel" id="panel-corpo">
      <div class="section-header" style="margin-bottom:24px">
        <div class="section-title">CORPO</div>
      </div>
      <div class="corpo-grid">
        <div class="corpo-block">
          <div class="corpo-block-title">MEU TREINO</div>
          <textarea class="corpo-textarea" id="corpoTreino" placeholder="Descreva seu treino da semana, exercícios, séries, cargas…" oninput="scheduleCorpoSave('treino')"></textarea>
          <div class="corpo-save-hint" id="corpoTreinoHint"></div>
        </div>
        <div class="corpo-block">
          <div class="corpo-block-title">MINHA DIETA</div>
          <textarea class="corpo-textarea" id="corpoDieta" placeholder="Descreva sua dieta, refeições, macros, metas…" oninput="scheduleCorpoSave('dieta')"></textarea>
          <div class="corpo-save-hint" id="corpoDietaHint"></div>
        </div>
      </div>
    </div>

  </main>
</div>


<!-- BOTTOM NAV (mobile) -->
<nav class="bottom-nav">
  <button class="bottom-nav-item active" onclick="switchTab('inicio')" id="bn-inicio">
    <span class="bottom-nav-icon">⚡</span>
    <span class="bottom-nav-label">Início</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('habitos')" id="bn-habitos">
    <span class="bottom-nav-icon">⊙</span>
    <span class="bottom-nav-label">Hábitos</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('tarefas')" id="bn-tarefas">
    <span class="bottom-nav-icon">≡</span>
    <span class="bottom-nav-label">Tarefas</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('eventos')" id="bn-eventos">
    <span class="bottom-nav-icon">✶</span>
    <span class="bottom-nav-label">Eventos</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('financas')" id="bn-financas">
    <span class="bottom-nav-icon">◫</span>
    <span class="bottom-nav-label">Finanças</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('metas')" id="bn-metas">
    <span class="bottom-nav-icon">◎</span>
    <span class="bottom-nav-label">Metas</span>
  </button>
  <button class="bottom-nav-item" onclick="switchTab('corpo')" id="bn-corpo">
    <span class="bottom-nav-icon">♡</span>
    <span class="bottom-nav-label">Corpo</span>
  </button>
</nav>

<!-- ===== MODAL: HABIT ===== -->
<div class="modal-overlay" id="habitModal">
  <div class="modal">
    <div class="modal-title" id="habitModalTitle">Novo Hábito</div>
    <div class="form-group">
      <label class="form-label">NOME</label>
      <input type="text" id="h-title" class="form-control" placeholder="Ex: Tomar remédio, Meditar…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">FREQUÊNCIA</label>
        <select id="h-recurrence" class="form-control" onchange="toggleHabitRecurrence()">
          <option value="daily">Diário</option>
          <option value="weekly">Semanal</option>
        </select>
      </div>
      <div class="form-group" id="h-recday-group" style="display:none">
        <label class="form-label">DIA DA SEMANA</label>
        <select id="h-recday" class="form-control"></select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-check">
        <input type="checkbox" id="h-show-tasks">
        Mostrar em tarefas
      </label>
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
    <div class="modal-title" id="taskModalTitle">Nova Atividade</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="t-title" class="form-control" placeholder="Ex: Academia, Relatório, Reunião…">
    </div>
    <div class="form-group">
      <label class="form-label">QUANDO</label>
      <div class="rec-toggle">
        <button class="rec-toggle-btn active" id="rec-weekly" onclick="setRecToggle('weekly')">Toda semana</button>
        <button class="rec-toggle-btn" id="rec-once" onclick="setRecToggle('once')">Esta semana</button>
        <button class="rec-toggle-btn" id="rec-undated" onclick="setRecToggle('undated')">Sem dia</button>
      </div>
    </div>
    <div class="form-group" id="t-day-group">
      <label class="form-label">DIA DA SEMANA</label>
      <div class="day-chips" id="t-day-chips"></div>
    </div>
    <input type="hidden" id="t-id">
    <input type="hidden" id="t-recurrence" value="weekly">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('taskModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveTask()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: EVENT ===== -->
<div class="modal-overlay" id="eventModal">
  <div class="modal">
    <div class="modal-title" id="eventModalTitle">Novo Evento</div>
    <div class="form-group">
      <label class="form-label">TITULO</label>
      <input type="text" id="e-title" class="form-control" placeholder="Ex: Reuniao, Consulta">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">DATA INICIAL</label>
        <input type="date" id="e-date" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">RECORRENCIA</label>
        <select id="e-recurrence" class="form-control" onchange="toggleEventRecurrence()">
          <option value="none">Sem recorrencia</option>
          <option value="daily">Diario</option>
          <option value="weekly">Semanal</option>
          <option value="monthly">Mensal</option>
        </select>
      </div>
    </div>
    <div class="form-group" id="e-daily-group" style="display:none">
      <label class="form-label">DIAS DA SEMANA</label>
      <div class="event-days" id="e-daily-days"></div>
    </div>
    <input type="hidden" id="e-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('eventModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveEvent()">Salvar</button>
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
    <div class="form-group">
      <label class="form-label">PRAZO</label>
      <select id="g-term" class="form-control">
        <option value="short">Curto prazo</option>
        <option value="long">Longo prazo</option>
      </select>
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

<!-- ===== MODAL: NOVA AÇÃO (INÍCIO) ===== -->
<div class="modal-overlay" id="actionModal">
  <div class="modal">
    <div class="modal-title">Nova Ação</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="a-title" class="form-control" placeholder="Ex: Beber água, Ler 10 páginas…">
    </div>
    <div class="form-group">
      <label class="form-label">FREQUÊNCIA</label>
      <div class="rec-toggle">
        <button class="rec-toggle-btn active" id="a-rec-daily" onclick="setActionRecToggle('daily')">Diária</button>
        <button class="rec-toggle-btn" id="a-rec-weekly" onclick="setActionRecToggle('weekly')">Semanal</button>
      </div>
    </div>
    <div class="form-group" id="a-day-group" style="display:none">
      <label class="form-label">DIA DA SEMANA</label>
      <div class="day-chips" id="a-day-chips"></div>
    </div>
    <input type="hidden" id="a-recurrence" value="daily">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('actionModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveAction()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: REWARD ===== -->
<div class="modal-overlay" id="rewardModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-title" id="rewardModalTitle">Nova Recompensa</div>
    <div class="form-row">
      <div class="form-group" style="max-width:90px">
        <label class="form-label">ÍCONE</label>
        <input type="text" id="r-icon" class="form-control" maxlength="4" placeholder="🎁" style="text-align:center;font-size:20px">
      </div>
      <div class="form-group">
        <label class="form-label">RECOMPENSA</label>
        <input type="text" id="r-title" class="form-control" placeholder="Ex: Ver um filme, Pedir comida…">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">CUSTO (PONTOS)</label>
      <input type="number" id="r-cost" class="form-control" placeholder="50" min="1" step="1">
    </div>
    <input type="hidden" id="r-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('rewardModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveReward()">Salvar</button>
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
const DAYS_WEEK_SHORT = ['','Seg','Ter','Qua','Qui','Sex','Sab','Dom'];
const MONTHS_PT = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const MONTHS_FULL = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const STREAK_DAYS = 7; // configurable
const EVENT_STORAGE_KEY = 'lifeos_events_v1';
const APP_TIMEZONE = 'America/Sao_Paulo';

let allTasks = [], allTxns = [], allCats = [], allGoals = [];
let allHabits = [];
let allRewards = [], allRedemptions = [];
let pointsData = { balance: 0, total_earned: 0, level: 1, xp_into_level: 0, xp_for_level: 100, week: [] };
let allEvents = [];
let finFilter = 'all';
let currentMonth = getSaoPauloTodayDate();
let eventMonth = getSaoPauloTodayDate();
let selectedEventDate = getSaoPauloTodayISO();

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

function getSaoPauloDateParts(d = new Date()) {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: APP_TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).formatToParts(d);
  const map = {};
  parts.forEach(p => {
    if (p.type !== 'literal') map[p.type] = p.value;
  });
  return {
    year: parseInt(map.year, 10),
    month: parseInt(map.month, 10),
    day: parseInt(map.day, 10)
  };
}

function getSaoPauloTodayISO() {
  const p = getSaoPauloDateParts(new Date());
  return `${p.year}-${String(p.month).padStart(2,'0')}-${String(p.day).padStart(2,'0')}`;
}

function getSaoPauloTodayDate() {
  const p = getSaoPauloDateParts(new Date());
  return new Date(p.year, p.month - 1, p.day);
}

function getMonthStr() {
  return currentMonth.getFullYear()+'-'+String(currentMonth.getMonth()+1).padStart(2,'0');
}
function toISODate(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
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
  const now = getSaoPauloTodayDate();
  const days = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
  const dateStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;
  const dayStr = days[now.getDay()];
  document.getElementById('sidebarDate').textContent = dateStr;
  document.getElementById('sidebarDay').textContent = dayStr;
  document.getElementById('mobileDate').textContent = dateStr;
  document.getElementById('finMonthLabel').textContent = MONTHS_FULL[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear();

  const calLink = document.getElementById('calSyncLink');
  if (calLink) {
    const resolved = new URL('calendar_feed.php', location.href).toString();
    calLink.href = resolved.replace(/^https?:/, 'webcal:');
  }
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
  if (tab==='inicio') { loadPoints(); loadRewards(); loadFinance(); loadGoals(); loadEvents(); loadCorpo(); renderInicioActions(); }
  if (tab==='habitos') loadHabits();
  if (tab==='tarefas') loadTasks();
  if (tab==='eventos') loadEvents();
  if (tab==='financas') loadFinance();
  if (tab==='metas') loadGoals();
  if (tab==='corpo') loadCorpo();
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

  const hStatHoje = document.getElementById('hStatHoje');
  const hStatStreak = document.getElementById('hStatStreak');
  const hStatSemana = document.getElementById('hStatSemana');
  if (hStatHoje) hStatHoje.textContent = `${done}/${total}`;
  if (hStatStreak) hStatStreak.textContent = STREAK_DAYS;
  if (hStatSemana) hStatSemana.textContent = total > 0 ? Math.round(done/total*100)+'%' : '0%';

  document.getElementById('nb-habitos').textContent = `${done}/${total}`;
}

// ===== INÍCIO (XP / PONTOS / RECOMPENSAS) =====
async function loadPoints() {
  const res = await api('points_summary');
  if (res.ok) { pointsData = res.data; renderPoints(); }
}

function renderPoints() {
  const level = pointsData.level || 1;
  const into = pointsData.xp_into_level || 0;
  const need = pointsData.xp_for_level || 100;
  const pct = Math.max(0, Math.min(100, Math.round(into/need*100)));
  document.getElementById('xpLevel').textContent = level;
  document.getElementById('xpBarFill').style.width = pct + '%';
  document.getElementById('xpBarLabel').textContent = `${into} / ${need} XP`;
  const balEl = document.getElementById('pointsBalance');
  balEl.textContent = pointsData.balance ?? 0;
  balEl.style.color = (pointsData.balance||0) < 0 ? 'var(--red)' : '';

  const weekWrap = document.getElementById('xpWeekChart');
  if (weekWrap) {
    const week = pointsData.week || [];
    const maxP = Math.max(10, ...week.map(w=>w.points));
    const DAYS_SHORT = ['D','S','T','Q','Q','S','S'];
    const todayISO = getSaoPauloTodayISO();
    weekWrap.innerHTML = week.map(w=>{
      const h = Math.max(4, Math.round((w.points/maxP)*100));
      const d = new Date(w.date+'T00:00:00');
      const isToday = w.date === todayISO;
      return `<div class="xp-bar-col${isToday?' is-today':''}" title="${w.points} pts">
        <div class="xp-bar-fillcol" style="height:${h}%"></div>
        <div class="xp-bar-daylabel">${DAYS_SHORT[d.getDay()]}</div>
      </div>`;
    }).join('');
  }
}

function popPoints(amount) {
  const wrap = document.getElementById('xpPointsWrap');
  if (!wrap) return;
  const el = document.createElement('div');
  el.className = 'points-pop';
  el.textContent = (amount > 0 ? '+' : '') + amount;
  wrap.appendChild(el);
  setTimeout(()=>el.remove(), 1000);
}

async function loadRewards() {
  const res = await api('rewards_list');
  if (res.ok) { allRewards = res.data || []; renderRewards(); }
  const res2 = await api('redemptions_list');
  if (res2.ok) { allRedemptions = res2.data || []; renderRedemptions(); }
}

function renderRewards() {
  const el = document.getElementById('rewardsList');
  if (!el) return;
  if (!allRewards.length) {
    el.innerHTML = '<div class="empty-state">Nenhuma recompensa cadastrada. Crie a sua!</div>';
    return;
  }
  el.innerHTML = allRewards.map(r=>{
    const afford = (pointsData.balance||0) >= parseInt(r.cost,10);
    return `<div class="reward-card${afford?'':' locked'}">
      <div class="reward-icon">${esc(r.icon||'🎁')}</div>
      <div class="reward-title">${esc(r.title)}</div>
      <div class="reward-cost">${r.cost} pts</div>
      <div class="reward-actions">
        <button class="btn btn-primary btn-sm" ${afford?'':'disabled'} onclick="redeemReward(${r.id})">Resgatar</button>
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editReward(${r.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteReward(${r.id})">✕</button>
      </div>
    </div>`;
  }).join('');
}

function renderRedemptions() {
  const el = document.getElementById('redemptionsList');
  if (!el) return;
  if (!allRedemptions.length) {
    el.innerHTML = '<div class="empty-state">Nenhum resgate ainda.</div>';
    return;
  }
  el.innerHTML = allRedemptions.map(r=>{
    const d = (r.redeemed_at||'').split(' ')[0];
    return `<div class="redemption-row">
      <span>${esc(r.title)}</span>
      <span>
        <span class="redemption-cost">-${r.cost} pts</span>
        <span class="redemption-date">${fmtDate(d)}</span>
      </span>
    </div>`;
  }).join('');
}

function openRewardModal(editData=null) {
  document.getElementById('r-id').value = editData?.id || '';
  document.getElementById('r-title').value = editData?.title || '';
  document.getElementById('r-cost').value = editData?.cost || '';
  document.getElementById('r-icon').value = editData?.icon || '🎁';
  document.getElementById('rewardModalTitle').textContent = editData ? 'Editar Recompensa' : 'Nova Recompensa';
  openModal('rewardModal');
}
function editReward(id) { const r = allRewards.find(x=>x.id==id); if (r) openRewardModal(r); }
async function saveReward() {
  const title = document.getElementById('r-title').value.trim();
  const cost = parseInt(document.getElementById('r-cost').value, 10);
  if (!title) { toast('Informe o nome da recompensa','err'); return; }
  if (!cost || cost <= 0) { toast('Informe um custo valido','err'); return; }
  const body = {
    id: document.getElementById('r-id').value,
    title, cost,
    icon: document.getElementById('r-icon').value.trim() || '🎁'
  };
  const res = await api('reward_save','POST',body);
  if (res.ok) { toast('Recompensa salva!'); closeModal('rewardModal'); loadRewards(); }
  else toast(res.error||'Erro','err');
}
async function deleteReward(id) {
  if (!confirm('Excluir esta recompensa?')) return;
  const res = await api('reward_delete','POST',{id});
  if (res.ok) { loadRewards(); }
  else toast(res.error||'Erro','err');
}
async function redeemReward(id) {
  const r = allRewards.find(x=>x.id==id);
  if (!r) return;
  if (!confirm(`Resgatar "${r.title}" por ${r.cost} pontos?`)) return;
  const res = await api('reward_redeem','POST',{id});
  if (res.ok) {
    toast('Recompensa resgatada! Aproveite 🎉');
    await loadPoints();
    await loadRewards();
  } else {
    toast(res.error || 'Erro ao resgatar.', 'err');
  }
}

function renderInicioActions() {
  const el = document.getElementById('inicioActions');
  if (!el) return;
  const todayDate = getSaoPauloTodayDate();
  const items = [];
  allHabits.forEach(h => {
    if (habitAppliesToDate(h, todayDate)) {
      items.push({ kind: 'habit', id: h.id, title: h.title, done: !!h._done, points: 10 });
    }
  });
  allTasks.forEach(t => {
    if (taskAppliesToDate(t, todayDate)) {
      items.push({ kind: 'task', id: t.id, title: t.title, done: parseInt(t.done_today||0,10)===1, points: 10 });
    }
  });

  const total = items.length;
  const done = items.filter(i=>i.done).length;
  const hdr = document.getElementById('inicioActionsHdr');
  if (hdr) hdr.textContent = `${done}/${total} hoje`;
  const nb = document.getElementById('nb-inicio');
  if (nb) nb.textContent = `${done}/${total}`;

  if (!items.length) {
    el.innerHTML = '<div class="empty-state">Nenhuma ação para hoje. Cadastre hábitos ou tarefas.</div>';
    return;
  }
  items.sort((a,b) => (a.done - b.done) || a.title.localeCompare(b.title));
  el.innerHTML = items.map(i => {
    const fn = i.kind === 'habit' ? `inicioToggleHabit(${i.id})` : `inicioToggleTask(${i.id})`;
    const tag = i.kind === 'habit' ? 'hábito' : 'tarefa';
    return `<div class="action-row${i.done?' done':''}" onclick="${fn}">
      <div class="action-check">${i.done?'✓':''}</div>
      <div class="action-title">${esc(i.title)}<span class="action-kind-tag">· ${tag}</span></div>
      <div class="action-badge">+${i.points}</div>
    </div>`;
  }).join('');
}

async function inicioToggleHabit(id) {
  const h = allHabits.find(x=>x.id==id);
  const wasDone = !!h?._done;
  await toggleHabit(id);
  await loadPoints();
  if (!wasDone) popPoints(10);
}
async function inicioToggleTask(id) {
  const t = allTasks.find(x=>x.id==id);
  const wasDone = t ? parseInt(t.done_today||0,10)===1 : false;
  await toggleTask(id, getSaoPauloTodayISO());
  await loadPoints();
  if (!wasDone) popPoints(10);
}

let _actionSelectedDay = null;
function openActionModal() {
  document.getElementById('a-title').value = '';
  setActionRecToggle('daily');
  openModal('actionModal');
}
function setActionRecToggle(type) {
  document.getElementById('a-recurrence').value = type;
  document.getElementById('a-rec-daily').classList.toggle('active', type === 'daily');
  document.getElementById('a-rec-weekly').classList.toggle('active', type === 'weekly');
  document.getElementById('a-day-group').style.display = type === 'weekly' ? 'block' : 'none';
  if (type === 'weekly') {
    renderActionDayChips(_actionSelectedDay || dayIndexFromDate(getSaoPauloTodayDate()));
  }
}
function renderActionDayChips(selected) {
  _actionSelectedDay = selected;
  const wrap = document.getElementById('a-day-chips');
  wrap.innerHTML = DAYS_WEEK.map((d,i) => i===0 ? '' :
    `<button class="day-chip${i==selected?' active':''}" onclick="selectActionDayChip(${i})">${d.substring(0,3)}</button>`).join('');
}
function selectActionDayChip(i) { renderActionDayChips(i); }
async function saveAction() {
  const title = document.getElementById('a-title').value.trim();
  if (!title) { toast('Informe o título da ação','err'); return; }
  const recurrence = document.getElementById('a-recurrence').value;
  const body = {
    title,
    recurrence,
    recurrence_day: recurrence === 'weekly' ? (_actionSelectedDay || dayIndexFromDate(getSaoPauloTodayDate())) : null
  };
  const res = await api('task_save','POST',body);
  if (res.ok) { toast('Ação criada!'); closeModal('actionModal'); await loadTasks(); }
  else toast(res.error||'Erro','err');
}

// ===== CHAT =====
async function loadChatHistory() {
  const res = await api('chat_history');
  const log = document.getElementById('chatLog');
  if (!log) return;
  if (!res.ok || !res.data.length) {
    log.innerHTML = '<div class="chat-bubble assistant">Oi! Me conta como foi seu dia, o que comeu, quanta água bebeu ou o que já resolveu — eu vou atualizando o app pra você. Também pode me perguntar coisas, tipo "quanto de água bebi essa semana?".</div>';
    return;
  }
  log.innerHTML = res.data.map(m => `<div class="chat-bubble ${m.role}">${esc(m.content)}</div>`).join('');
  log.scrollTop = log.scrollHeight;
}
async function sendChatMessage() {
  const input = document.getElementById('chatInput');
  const btn = document.getElementById('chatSendBtn');
  const message = input.value.trim();
  if (!message) return;
  const log = document.getElementById('chatLog');

  log.insertAdjacentHTML('beforeend', `<div class="chat-bubble user">${esc(message)}</div>`);
  const pendingId = 'pending-' + Date.now();
  log.insertAdjacentHTML('beforeend', `<div class="chat-bubble assistant pending" id="${pendingId}">pensando…</div>`);
  log.scrollTop = log.scrollHeight;
  input.value = '';
  input.disabled = true;
  btn.disabled = true;

  try {
    const raw = await fetch('ai_chat.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({message})
    });
    const data = await raw.json().catch(() => ({ ok:false, error:'Resposta inválida da IA.' }));
    const pendingEl = document.getElementById(pendingId);
    if (data.ok) {
      if (pendingEl) { pendingEl.textContent = data.reply; pendingEl.classList.remove('pending'); }
      await Promise.all([loadPoints(), loadTasks(), loadHabits()]);
    } else {
      if (pendingEl) { pendingEl.textContent = data.error || 'Erro ao falar com a IA.'; pendingEl.classList.remove('pending'); }
    }
  } catch (e) {
    const pendingEl = document.getElementById(pendingId);
    if (pendingEl) { pendingEl.textContent = 'Erro de conexão.'; pendingEl.classList.remove('pending'); }
  }

  log.scrollTop = log.scrollHeight;
  input.disabled = false;
  btn.disabled = false;
  input.focus();
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
  const today = getSaoPauloTodayISO();
  allHabits = (res.data || []).map(h => {
    let dates = [];
    try { dates = JSON.parse(h.checked_dates || '[]'); } catch (e) { dates = []; }
    const rec = h.recurrence || 'daily';
    const recDay = parseInt(h.recurrence_day || 0, 10) || null;
    const showInTasks = parseInt(h.show_in_tasks || 0, 10) === 1;
    return {
      id: h.id,
      title: h.name,
      _done: Array.isArray(dates) && dates.includes(today),
      _dates: Array.isArray(dates) ? dates : [],
      _recurrence: rec,
      _recurrence_day: recDay,
      _show_in_tasks: showInTasks
    };
  });
  renderHabits();
  renderStreak();
  renderTasks();
  renderInicioActions();
}

function renderHabits() {
  const el = document.getElementById('habitList');
  if (!allHabits.length) {
    el.innerHTML = '<div class="empty-state">Nenhum hábito cadastrado.</div>';
    return;
  }
  const daily = allHabits.filter(h => h._recurrence !== 'weekly');
  const weekly = allHabits.filter(h => h._recurrence === 'weekly');

  const renderHabitItem = h => `
    <div class="habit-item${h._done?' done':''}" onclick="toggleHabit(${h.id})">
      <div class="habit-check">${h._done?'✓':''}</div>
      <div class="habit-info">
        <div class="habit-name">${esc(h.title)}</div>
        <div class="habit-meta">${habitRecLabel(h)}${h._show_in_tasks ? ' · Em tarefas' : ''}</div>
      </div>
      <div class="habit-streak">${habitStreak(h)}</div>
      <div class="habit-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editHabit(${h.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteHabit(${h.id})">✕</button>
      </div>
    </div>
  `;

  const dailyBlock = `
    <div class="habit-section">
      <div class="habit-section-title">Diario</div>
      ${daily.length ? daily.map(renderHabitItem).join('') : '<div class="empty-state" style="padding:12px 0">Sem habitos diarios.</div>'}
    </div>
  `;
  const weeklyBlock = `
    <div class="habit-section">
      <div class="habit-section-title">Semanal</div>
      ${weekly.length ? weekly.map(renderHabitItem).join('') : '<div class="empty-state" style="padding:12px 0">Sem habitos semanais.</div>'}
    </div>
  `;

  el.innerHTML = dailyBlock + weeklyBlock;
}

function openHabitModal(editData=null) {
  document.getElementById('h-id').value = editData?.id || '';
  document.getElementById('h-title').value = editData?.title || '';
  document.getElementById('h-recurrence').value = editData?._recurrence || 'daily';
  document.getElementById('h-show-tasks').checked = !!editData?._show_in_tasks;
  toggleHabitRecurrence(editData?._recurrence_day || null);
  document.getElementById('habitModalTitle').textContent = editData ? 'Editar Hábito' : 'Novo Hábito';
  openModal('habitModal');
}
function editHabit(id) { const h = allHabits.find(x=>x.id==id); if (h) openHabitModal(h); }
function toggleHabitRecurrence(selected=null) {
  const rec = document.getElementById('h-recurrence').value;
  const group = document.getElementById('h-recday-group');
  const sel = document.getElementById('h-recday');
  group.style.display = rec === 'weekly' ? 'block' : 'none';
  if (rec !== 'weekly') return;
  const pick = selected || dayIndexFromDate(getSaoPauloTodayDate());
  sel.innerHTML = DAYS_WEEK.map((d,i)=>i===0?'':
    `<option value="${i}" ${pick==i?'selected':''}>${d}</option>`).join('');
}
async function saveHabit() {
  const title = document.getElementById('h-title').value.trim();
  if (!title) { toast('Informe o nome','err'); return; }
  const rec = document.getElementById('h-recurrence').value;
  const recDay = document.getElementById('h-recday').value;
  const body = {
    id: document.getElementById('h-id').value,
    name: title,
    recurrence: rec,
    recurrence_day: rec === 'weekly' ? recDay : null,
    show_in_tasks: document.getElementById('h-show-tasks').checked ? 1 : 0
  };
  const res = await api('habit_save','POST',body);
  if (res.ok) { toast('Hábito salvo!'); closeModal('habitModal'); loadHabits(); }
  else toast(res.error||'Erro','err');
}
async function toggleHabit(id) {
  const today = getSaoPauloTodayISO();
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
  renderInicioActions();
  document.getElementById('nb-tarefas').textContent = allTasks.length || '—';
}

function habitAppliesToDate(h, dateObj) {
  if (!h) return false;
  if (h._recurrence === 'daily') return true;
  if (h._recurrence === 'weekly') {
    return parseInt(h._recurrence_day || 0, 10) === dayIndexFromDate(dateObj);
  }
  return false;
}

function habitDoneOnDate(h, dateObj) {
  const iso = toISODate(dateObj);
  return Array.isArray(h._dates) && h._dates.includes(iso);
}

function habitStreak(h, baseDate = getSaoPauloTodayDate()) {
  if (!h || !Array.isArray(h._dates) || h._dates.length === 0) return 0;

  const cursor = new Date(baseDate);
  cursor.setHours(0, 0, 0, 0);
  let count = 0;

  if (h._recurrence === 'weekly') {
    const recDay = parseInt(h._recurrence_day || 0, 10);
    if (recDay < 1 || recDay > 7) return 0;

    while (dayIndexFromDate(cursor) !== recDay) {
      cursor.setDate(cursor.getDate() - 1);
    }

    while (habitDoneOnDate(h, cursor)) {
      count += 1;
      cursor.setDate(cursor.getDate() - 7);
    }

    return count;
  }

  if (!habitDoneOnDate(h, cursor)) {
    cursor.setDate(cursor.getDate() - 1);
  }

  while (habitDoneOnDate(h, cursor)) {
    count += 1;
    cursor.setDate(cursor.getDate() - 1);
  }

  return count;
}

function habitRecLabel(h) {
  if (h._recurrence === 'weekly') {
    const dayLabel = DAYS_WEEK[h._recurrence_day] || '—';
    return `Semanal · ${dayLabel}`;
  }
  return 'Diário';
}

function renderDayEntry(entry, todayISO) {
  if (entry.kind === 'habit') return habitRowHTML(entry.habit, entry.date, todayISO);
  return taskRowHTML(entry.task, entry.date, todayISO);
}

function renderMobileEntry(entry, todayISO) {
  if (entry.kind === 'habit') return habitMobileItemHTML(entry.habit, entry.date);
  return taskMobileItemHTML(entry.task, entry.date, todayISO);
}

const _expandedDays = new Set();
let _expandedInit = false;

function renderTasks() {
  const el = document.getElementById('taskSections');
  const today = getSaoPauloTodayDate();
  const todayISO = toISODate(today);

  const weekStart = new Date(today);
  weekStart.setDate(today.getDate() - ((today.getDay() + 6) % 7));
  const weekEnd = new Date(weekStart);
  weekEnd.setDate(weekStart.getDate() + 6);
  const weekDates = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart);
    d.setDate(weekStart.getDate() + i);
    return d;
  });

  const fmtShort = d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
  const weekLabel = document.getElementById('taskWeekLabel');
  if (weekLabel) weekLabel.textContent = `${fmtShort(weekStart)} – ${fmtShort(weekEnd)}`;

  if (!_expandedInit) { _expandedDays.add(todayISO); _expandedInit = true; }

  function getRecDay(t) {
    const raw = parseInt(t.recurrence_day || 0, 10);
    if (raw > 0) return raw;
    return dayIndexFromDate(today);
  }

  const dayBuckets = weekDates.map(() => []);
  allTasks.forEach(t => {
    const rec = t.recurrence || 'weekly';
    if (rec === 'daily') { dayBuckets.forEach((list, i) => list.push({ kind: 'task', task: t, date: weekDates[i] })); return; }
    if (rec === 'weekly') { const idx = Math.max(1, getRecDay(t)) - 1; if (idx >= 0 && idx < 7) dayBuckets[idx].push({ kind: 'task', task: t, date: weekDates[idx] }); return; }
    if (rec === 'once' && t.due_date) { const idx = weekDates.findIndex(d => toISODate(d) === t.due_date); if (idx >= 0) dayBuckets[idx].push({ kind: 'task', task: t, date: weekDates[idx] }); }
  });

  let totalTasks = 0, doneTasks = 0;
  dayBuckets.forEach((list, i) => {
    list.forEach(e => {
      if (e.kind !== 'task') return;
      totalTasks++;
      const dMatch = e.task.due_date && toISODate(weekDates[i]) === e.task.due_date;
      const isTod = toISODate(weekDates[i]) === todayISO;
      if ((isTod || dMatch) && parseInt(e.task.done_today || 0) === 1) doneTasks++;
    });
  });
  const pct = totalTasks > 0 ? Math.round(doneTasks / totalTasks * 100) : 0;

  const MONTHS_SHORT = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];

  const daysHTML = weekDates.map((d, i) => {
    const iso = toISODate(d);
    const isToday = iso === todayISO;
    const isPast = iso < todayISO;
    const list = dayBuckets[i];
    const expanded = _expandedDays.has(iso);
    const cls = ['week-day-row', isToday ? 'is-today' : '', isPast ? 'is-past' : '', expanded ? 'expanded' : ''].filter(Boolean).join(' ');
    const countLabel = list.length ? `${list.length} ${list.length === 1 ? 'tarefa' : 'tarefas'}` : 'livre';
    const dayName = isToday ? `HOJE — ${DAYS_WEEK[i+1].toUpperCase()}` : DAYS_WEEK[i+1].toUpperCase();
    const subLabel = `${fmtShort(d)}${isPast && !isToday ? ' · concluído' : ''}`;
    const tasksHTML = list.length ? list.map(e => weekTaskHTML(e.task, e.date, todayISO)).join('') : `<div class="week-day-empty-label">livre</div>`;
    return `<div class="${cls}" id="wday-${iso}">
      <div class="week-day-hdr" onclick="toggleWeekDay('${iso}')">
        <div class="week-day-icon"><div class="wdi-num">${d.getDate()}</div><div class="wdi-mon">${MONTHS_SHORT[d.getMonth()]}</div></div>
        <div class="week-day-name-wrap">
          <div class="week-day-name">${dayName}</div>
          <div class="week-day-sub">${subLabel}</div>
        </div>
        <div class="week-day-count">${countLabel}</div>
        <button class="week-day-addBtn" onclick="event.stopPropagation();openTaskModal(null,${i+1})">+</button>
        <div class="week-day-chevron">›</div>
      </div>
      <div class="week-day-body">${tasksHTML}</div>
    </div>`;
  }).join('');

  const undated = allTasks.filter(t => t.recurrence === 'once' && !t.due_date && parseInt(t.status || 0) === 0);
  const undatedHTML = undated.length
    ? undated.map(t => `<div class="week-task-item${parseInt(t.done_today||0)?' done':''}" onclick="toggleTask(${t.id},null)">
        <div class="week-task-check">✓</div>
        <div class="week-task-title">${esc(t.title)}</div>
        <div class="week-task-actions">
          <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editTask(${t.id})">✏</button>
          <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteTask(${t.id})">✕</button>
        </div>
      </div>`).join('')
    : `<div class="undated-empty">Nenhuma tarefa pendente.</div>`;

  const undatedSection = `<div class="undated-section">
    <div class="undated-header">
      <div class="undated-title">A fazer</div>
      <button class="undated-add" onclick="openTaskModal(null,null,true)">+</button>
    </div>
    <div class="undated-list">${undatedHTML}</div>
  </div>`;

  el.innerHTML = `<div class="weekly-planner">
    ${undatedSection}
    <div class="week-summary">
      <div class="week-progress-bar"><div class="week-progress-fill" style="width:${pct}%"></div></div>
      <div class="week-summary-text">${doneTasks} / ${totalTasks} feitas</div>
    </div>
    <div class="week-days">${daysHTML}</div>
  </div>`;
}

function toggleWeekDay(iso) {
  const row = document.getElementById('wday-' + iso);
  if (!row) return;
  if (_expandedDays.has(iso)) { _expandedDays.delete(iso); row.classList.remove('expanded'); }
  else { _expandedDays.add(iso); row.classList.add('expanded'); }
}

function isTaskDone(t, dateObj) {
  if (!dateObj) return false;
  if (t.recurrence === 'once') return parseInt(t.done_today || 0) === 1;
  const iso = toISODate(dateObj);
  const dates = t.done_dates ? t.done_dates.split(',') : [];
  return dates.includes(iso);
}

function weekTaskHTML(t, dateObj, todayISO) {
  const done = isTaskDone(t, dateObj);
  const dateISO = toISODate(dateObj);
  const badge = t.recurrence === 'weekly' ? '∞ semanal' : '1×';
  return `<div class="week-task-item${done ? ' done' : ''}" onclick="toggleTask(${t.id},'${dateISO}')">
    <div class="week-task-check">✓</div>
    <div class="week-task-title">${esc(t.title)}</div>
    <div class="week-task-badge">${badge}</div>
    <div class="week-task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editTask(${t.id})">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteTask(${t.id})">✕</button>
    </div>
  </div>`;
}

function weekHabitHTML(h, dateObj) {
  const done = habitDoneOnDate(h, dateObj);
  const iso = toISODate(dateObj);
  return `<div class="week-task-item habit${done ? ' done' : ''}" onclick="toggleHabitForDate(${h.id}, '${iso}')">
    <div class="week-task-check">✓</div>
    <div class="week-task-title">${esc(h.title)}</div>
    <div class="week-task-badge">hábito</div>
    <div class="week-task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();toggleHabitForDate(${h.id}, '${iso}')">${done ? '✓' : '○'}</button>
    </div>
  </div>`;
}

function taskMobileItemHTML(t, dateObj, todayISO) {
  const done = isTaskDone(t, dateObj);
  const dateISO = toISODate(dateObj);
  return `<div class="task-mobile-item${done ? ' done' : ''}" onclick="toggleTask(${t.id},'${dateISO}')">
    <div class="task-mobile-item-title">${esc(t.title)}</div>
    <div class="task-mobile-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editTask(${t.id})">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteTask(${t.id})">✕</button>
    </div>
  </div>`;
}

function habitMobileItemHTML(h, dateObj) {
  const iso = toISODate(dateObj);
  const done = habitDoneOnDate(h, dateObj);
  return `<div class="task-mobile-item habit${done ? ' done' : ''}">
    <div class="task-mobile-item-title">${esc(h.title)}</div>
    <div class="task-mobile-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="toggleHabitForDate(${h.id}, '${iso}')">${done ? '✓' : '○'}</button>
    </div>
  </div>`;
}

function taskRowHTML(t, dateObj, todayISO, compact=false) {
  const isDone = isTaskDone(t, dateObj);
  const dateISO = toISODate(dateObj);
  const isOverdue = t.recurrence === 'once' && t.due_date && t.due_date < todayISO && !t.status;
  const recBadge = t.recurrence === 'weekly' ? '' : `<span style="font-size:9px;color:var(--muted);font-family:'DM Mono',monospace;margin-left:4px">1×</span>`;
  return `<div class="task-row${isDone ? ' done' : ''}${isOverdue ? ' overdue' : ''}" onclick="toggleTask(${t.id},'${dateISO}')">
    <div class="task-check">✓</div>
    <div class="task-title">
      <div>${esc(t.title)}${recBadge}</div>
    </div>
    <div class="task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editTask(${t.id})">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteTask(${t.id})">✕</button>
    </div>
  </div>`;
}

function habitRowHTML(h, dateObj, todayISO) {
  const done = habitDoneOnDate(h, dateObj);
  const iso = toISODate(dateObj);
  const meta = `<div class="task-row-meta">Hábito · ${habitRecLabel(h)}</div>`;
  return `<div class="task-row habit${done ? ' done' : ''}" onclick="toggleHabitForDate(${h.id}, '${iso}')">
    <div class="task-title">
      <div>${esc(h.title)}</div>
      ${meta}
    </div>
    <div class="task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();toggleHabitForDate(${h.id}, '${iso}')">${done ? '✓' : '○'}</button>
    </div>
  </div>`;
}

async function toggleHabitForDate(id, dateISO) {
  const res = await api('habit_toggle','POST',{id, date: dateISO});
  if (res.ok) { loadHabits(); }
  else toast(res.error || 'Erro', 'err');
}

async function toggleTask(id, dateISO) {
  const body = dateISO ? {id, date: dateISO} : {id};
  await api('task_toggle','POST', body);
  loadTasks();
}

let _taskSelectedDay = 1;
function openTaskModal(editData=null, prefillDay=null, undated=false) {
  document.getElementById('t-id').value = editData?.id||'';
  document.getElementById('t-title').value = editData?.title||'';
  document.getElementById('taskModalTitle').textContent = editData ? 'Editar Atividade' : 'Nova Atividade';
  _taskSelectedDay = editData?.recurrence_day || prefillDay || dayIndexFromDate(getSaoPauloTodayDate());
  let rec = editData?.recurrence || 'weekly';
  if (undated || (editData && editData.recurrence === 'once' && !editData.due_date && !editData.recurrence_day)) rec = 'undated';
  setRecToggle(rec, true);
  renderDayChips(_taskSelectedDay);
  openModal('taskModal');
}
function editTask(id) { const t = allTasks.find(x=>x.id==id); if(t) openTaskModal(t); }
function renderDayChips(selected) {
  const wrap = document.getElementById('t-day-chips');
  if (!wrap) return;
  wrap.innerHTML = DAYS_WEEK.map((d,i) => i===0 ? '' :
    `<button class="day-chip${i==selected?' active':''}" onclick="selectDayChip(${i})">${d.substring(0,3)}</button>`
  ).join('');
}
function selectDayChip(idx) {
  _taskSelectedDay = idx;
  renderDayChips(idx);
}
function setRecToggle(val, silent=false) {
  document.getElementById('t-recurrence').value = val;
  document.getElementById('rec-weekly').classList.toggle('active', val==='weekly');
  document.getElementById('rec-once').classList.toggle('active', val==='once');
  document.getElementById('rec-undated').classList.toggle('active', val==='undated');
  const dayGroup = document.getElementById('t-day-group');
  if (dayGroup) dayGroup.style.display = val === 'undated' ? 'none' : '';
}
async function saveTask() {
  const title = document.getElementById('t-title').value.trim();
  if (!title) { toast('Informe o título','err'); return; }
  const recToggleVal = document.getElementById('t-recurrence').value || 'weekly';
  const recDay = _taskSelectedDay || dayIndexFromDate(getSaoPauloTodayDate());

  let recurrence = recToggleVal, due_date = null, recurrence_day = null;

  if (recToggleVal === 'undated') {
    recurrence = 'once';
  } else if (recToggleVal === 'once') {
    recurrence = 'once';
    const today = getSaoPauloTodayDate();
    const weekStart = new Date(today);
    weekStart.setDate(today.getDate() - ((today.getDay() + 6) % 7));
    const target = new Date(weekStart);
    target.setDate(weekStart.getDate() + (recDay - 1));
    due_date = toISODate(target);
  } else {
    recurrence = 'weekly';
    recurrence_day = recDay;
  }

  const body = {
    id: document.getElementById('t-id').value,
    title,
    recurrence,
    recurrence_day,
    color: '#ffffff',
    due_date
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

// ===== EVENTS =====
function loadEvents() {
  allEvents = loadEventsFromStorage();
  if (!selectedEventDate) selectedEventDate = getSaoPauloTodayISO();
  buildEventWeekdays();
  updateEventMonthLabel();
  renderEventCalendar();
  renderEventDayList();
  renderOvEvents();
}

function renderOvEvents() {
  const el = document.getElementById('ovEventList');
  if (!el) return;
  const today = getSaoPauloTodayDate();
  const found = [];
  for (let i = 0; i < 21 && found.length < 4; i++) {
    const d = new Date(today);
    d.setDate(today.getDate() + i);
    getEventsForDate(d).forEach(ev => found.push({ ev, date: new Date(d) }));
  }
  if (!found.length) {
    el.innerHTML = '<div class="empty-state">Sem eventos próximos.</div>';
    return;
  }
  const MONTHS_SHORT = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
  el.innerHTML = found.map(({ev, date}) => `
    <div class="event-item">
      <div>
        <div class="event-item-title">${esc(ev.title)}</div>
        <div class="event-item-meta">${String(date.getDate()).padStart(2,'0')} ${MONTHS_SHORT[date.getMonth()]}</div>
      </div>
    </div>
  `).join('');
}

function loadEventsFromStorage() {
  try {
    const raw = localStorage.getItem(EVENT_STORAGE_KEY);
    const parsed = JSON.parse(raw || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    return [];
  }
}

function saveEventsToStorage() {
  localStorage.setItem(EVENT_STORAGE_KEY, JSON.stringify(allEvents));
}

function buildEventWeekdays() {
  const el = document.getElementById('eventWeekdays');
  if (!el) return;
  el.innerHTML = DAYS_WEEK_SHORT.slice(1).map(d => `<div>${d}</div>`).join('');
}

function updateEventMonthLabel() {
  const label = document.getElementById('eventMonthLabel');
  if (!label) return;
  label.textContent = MONTHS_FULL[eventMonth.getMonth()] + ' ' + eventMonth.getFullYear();
}

function changeEventMonth(dir) {
  eventMonth.setMonth(eventMonth.getMonth() + dir);
  updateEventMonthLabel();
  renderEventCalendar();
}

function setSelectedEventDate(dateISO) {
  selectedEventDate = dateISO;
  renderEventCalendar();
  renderEventDayList();
}

function eventAppliesToDate(ev, dateObj) {
  if (!ev || !ev.start_date) return false;
  const dateISO = toISODate(dateObj);
  if (ev.recurrence === 'none') return dateISO === ev.start_date;
  if (dateISO < ev.start_date) return false;
  const dayIndex = dayIndexFromDate(dateObj);
  if (ev.recurrence === 'daily') {
    if (Array.isArray(ev.days) && ev.days.length) return ev.days.includes(dayIndex);
    return true;
  }
  if (ev.recurrence === 'weekly') return parseInt(ev.weekday || 0, 10) === dayIndex;
  if (ev.recurrence === 'monthly') return parseInt(ev.monthday || 0, 10) === dateObj.getDate();
  return false;
}

function getEventsForDate(dateObj) {
  return allEvents.filter(ev => eventAppliesToDate(ev, dateObj));
}

function renderEventCalendar() {
  const grid = document.getElementById('eventCalendar');
  const mobile = document.getElementById('eventCalendarMobile');
  if (!grid) return;
  const year = eventMonth.getFullYear();
  const month = eventMonth.getMonth();
  const firstDay = new Date(year, month, 1);
  const startOffset = (firstDay.getDay() + 6) % 7;
  const startDate = new Date(year, month, 1 - startOffset);
  const todayISO = getSaoPauloTodayISO();

  const cells = Array.from({ length: 42 }, (_, i) => {
    const d = new Date(startDate);
    d.setDate(startDate.getDate() + i);
    return d;
  });

  grid.innerHTML = cells.map(d => {
    const iso = toISODate(d);
    const inMonth = d.getMonth() === month;
    const isToday = iso === todayISO;
    const isSelected = iso === selectedEventDate;
    const events = getEventsForDate(d);
    const chips = events.slice(0, 2).map(ev => `<div class="event-chip" title="${esc(ev.title)}">${esc(ev.title)}</div>`).join('');
    const more = events.length > 2 ? `<div class="event-chip more">+${events.length - 2}</div>` : '';
    return `
      <div class="calendar-day${inMonth ? '' : ' outside'}${isToday ? ' is-today' : ''}${isSelected ? ' is-selected' : ''}" onclick="setSelectedEventDate('${iso}')">
        <div class="calendar-day-number"><strong>${d.getDate()}</strong></div>
        ${chips}${more}
      </div>
    `;
  }).join('');

  if (!mobile) return;
  const monthDays = new Date(year, month + 1, 0).getDate();
  const rows = Array.from({ length: monthDays }, (_, i) => {
    const d = new Date(year, month, i + 1);
    const iso = toISODate(d);
    const isSelected = iso === selectedEventDate;
    const events = getEventsForDate(d);
    const chips = events.slice(0, 2).map(ev => `<div class="event-chip" title="${esc(ev.title)}">${esc(ev.title)}</div>`).join('');
    const more = events.length > 2 ? `<div class="event-chip more">+${events.length - 2}</div>` : '';
    const dayLabel = `${String(d.getDate()).padStart(2,'0')} ${DAYS_WEEK_SHORT[dayIndexFromDate(d)]}`;
    const empty = events.length ? '' : '<div class="event-chip more">Sem eventos</div>';
    return `
      <div class="calendar-mobile-row${isSelected ? ' is-selected' : ''}" onclick="setSelectedEventDate('${iso}')">
        <div class="calendar-mobile-day">${dayLabel}</div>
        <div class="calendar-mobile-events">${chips}${more}${empty}</div>
      </div>
    `;
  });
  mobile.innerHTML = rows.join('');
}

function eventRecLabel(ev) {
  if (ev.recurrence === 'none') return 'Sem recorrencia';
  if (ev.recurrence === 'daily') {
    const days = Array.isArray(ev.days) ? ev.days.map(d => DAYS_WEEK_SHORT[d]).filter(Boolean) : [];
    return days.length ? `Diario: ${days.join(', ')}` : 'Diario';
  }
  if (ev.recurrence === 'weekly') {
    return `Semanal: ${DAYS_WEEK[ev.weekday] || '—'}`;
  }
  if (ev.recurrence === 'monthly') {
    return `Mensal: dia ${ev.monthday || ''}`;
  }
  return 'Evento';
}

function renderEventDayList() {
  const list = document.getElementById('eventDayList');
  const title = document.getElementById('eventListTitle');
  if (!list || !title) return;
  const dateObj = new Date(selectedEventDate + 'T00:00:00');
  title.textContent = `Eventos do dia ${fmtDate(selectedEventDate)}`;
  const items = getEventsForDate(dateObj);
  if (!items.length) {
    list.innerHTML = '<div class="empty-state">Sem eventos.</div>';
    return;
  }
  list.innerHTML = items.map(ev => `
    <div class="event-item">
      <div>
        <div class="event-item-title">${esc(ev.title)}</div>
        <div class="event-item-meta">${eventRecLabel(ev)}</div>
      </div>
      <div class="event-item-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editEvent(${ev.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteEvent(${ev.id})">✕</button>
      </div>
    </div>
  `).join('');
}

function openEventModal(editData=null) {
  document.getElementById('e-id').value = editData?.id || '';
  document.getElementById('e-title').value = editData?.title || '';
  document.getElementById('e-date').value = editData?.start_date || getSaoPauloTodayISO();
  document.getElementById('e-recurrence').value = editData?.recurrence || 'none';
  buildEventDays(editData?.days || []);
  toggleEventRecurrence();
  document.getElementById('eventModalTitle').textContent = editData ? 'Editar Evento' : 'Novo Evento';
  openModal('eventModal');
}

function buildEventDays(selected) {
  const el = document.getElementById('e-daily-days');
  if (!el) return;
  el.innerHTML = DAYS_WEEK_SHORT.slice(1).map((d, idx) => {
    const dayVal = idx + 1;
    const checked = Array.isArray(selected) && selected.includes(dayVal) ? 'checked' : '';
    return `
      <label class="event-day-check">
        <input type="checkbox" data-day="${dayVal}" ${checked}>
        ${d}
      </label>
    `;
  }).join('');
}

function toggleEventRecurrence() {
  const rec = document.getElementById('e-recurrence').value;
  const dailyGroup = document.getElementById('e-daily-group');
  dailyGroup.style.display = rec === 'daily' ? 'block' : 'none';
}

function collectEventDays() {
  const el = document.getElementById('e-daily-days');
  if (!el) return [];
  const days = [];
  el.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    if (cb.checked) days.push(parseInt(cb.dataset.day, 10));
  });
  return days;
}

function saveEvent() {
  const title = document.getElementById('e-title').value.trim();
  const startDate = document.getElementById('e-date').value;
  const rec = document.getElementById('e-recurrence').value;
  if (!title) { toast('Informe o titulo','err'); return; }
  if (!startDate) { toast('Informe a data','err'); return; }
  const days = rec === 'daily' ? collectEventDays() : [];
  if (rec === 'daily' && !days.length) { toast('Selecione pelo menos um dia','err'); return; }

  const startObj = new Date(startDate + 'T00:00:00');
  const weekday = dayIndexFromDate(startObj);
  const monthday = startObj.getDate();

  const idRaw = document.getElementById('e-id').value;
  const id = idRaw ? parseInt(idRaw, 10) : Date.now();

  const payload = {
    id,
    title,
    start_date: startDate,
    recurrence: rec,
    days,
    weekday,
    monthday
  };

  const existingIdx = allEvents.findIndex(e => e.id === id);
  if (existingIdx >= 0) allEvents[existingIdx] = payload;
  else allEvents.unshift(payload);

  saveEventsToStorage();
  closeModal('eventModal');
  loadEvents();
  toast('Evento salvo!');
}

function editEvent(id) {
  const ev = allEvents.find(e => e.id === id);
  if (ev) openEventModal(ev);
}

function deleteEvent(id) {
  if (!confirm('Excluir?')) return;
  allEvents = allEvents.filter(e => e.id !== id);
  saveEventsToStorage();
  loadEvents();
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
    const ovBal = document.getElementById('ovFinBalance');
    if (ovBal) { ovBal.textContent = fmtBRL(s.balance); ovBal.style.color = s.balance>=0 ? 'var(--green)' : 'var(--red)'; }
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
  document.getElementById('txn-date').value=getSaoPauloTodayISO();
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
  renderOvGoals();
  document.getElementById('nb-metas').textContent = allGoals.length;
}
function renderGoals() {
  const shortEl = document.getElementById('goalsShort');
  const longEl = document.getElementById('goalsLong');
  if (!allGoals.length) {
    shortEl.innerHTML = '<div class="empty-state">Sem metas de curto prazo.</div>';
    longEl.innerHTML = '<div class="empty-state">Sem metas de longo prazo.</div>';
    return;
  }
  const ordered = [...allGoals].sort((a,b)=>{
    const ad = parseInt(a.status || 0, 10);
    const bd = parseInt(b.status || 0, 10);
    if (ad !== bd) return ad - bd;
    return b.id - a.id;
  });
  const shortGoals = ordered.filter(g => (g.goal_term || 'short') === 'short');
  const longGoals = ordered.filter(g => (g.goal_term || 'short') === 'long');
  shortEl.innerHTML = shortGoals.length ? shortGoals.map(g=>{
    const done = parseInt(g.status || 0, 10) === 1;
    return `<div class="goal-row${done ? ' done' : ''}">
      <div class="goal-check" onclick="toggleGoal(${g.id})">${done ? '✓' : ''}</div>
      <div class="goal-title">${esc(g.title)}</div>
      <div class="goal-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editGoal(${g.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteGoal(${g.id})">✕</button>
      </div>
    </div>`;
  }).join('') : '<div class="empty-state">Sem metas de curto prazo.</div>';
  longEl.innerHTML = longGoals.length ? longGoals.map(g=>{
    const done = parseInt(g.status || 0, 10) === 1;
    return `<div class="goal-row${done ? ' done' : ''}">
      <div class="goal-check" onclick="toggleGoal(${g.id})">${done ? '✓' : ''}</div>
      <div class="goal-title">${esc(g.title)}</div>
      <div class="goal-actions">
        <button class="btn btn-ghost btn-icon btn-sm" onclick="editGoal(${g.id})">✏</button>
        <button class="btn btn-danger btn-icon btn-sm" onclick="deleteGoal(${g.id})">✕</button>
      </div>
    </div>`;
  }).join('') : '<div class="empty-state">Sem metas de longo prazo.</div>';
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
  document.getElementById('g-term').value=editData?.goal_term||'short';
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
  if (!title) { toast('Preencha o nome da meta','err'); return; }
  const body={id:document.getElementById('g-id').value,title,goal_term:document.getElementById('g-term').value};
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

// ===== CORPO =====
const CORPO_KEYS = { treino: 'corpo_treino_v1', dieta: 'corpo_dieta_v1' };
const _corpoTimers = {};
function loadCorpo() {
  document.getElementById('corpoTreino').value = localStorage.getItem(CORPO_KEYS.treino) || '';
  document.getElementById('corpoDieta').value = localStorage.getItem(CORPO_KEYS.dieta) || '';
  renderOvCorpo();
}
function renderOvCorpo() {
  const treinoEl = document.getElementById('ovCorpoTreino');
  const dietaEl = document.getElementById('ovCorpoDieta');
  if (!treinoEl || !dietaEl) return;
  const t = (localStorage.getItem(CORPO_KEYS.treino) || '').trim();
  const d = (localStorage.getItem(CORPO_KEYS.dieta) || '').trim();
  treinoEl.textContent = t || '—';
  dietaEl.textContent = d || '—';
}
function scheduleCorpoSave(type) {
  clearTimeout(_corpoTimers[type]);
  _corpoTimers[type] = setTimeout(() => {
    const val = document.getElementById(type === 'treino' ? 'corpoTreino' : 'corpoDieta').value;
    localStorage.setItem(CORPO_KEYS[type], val);
    const hint = document.getElementById(type === 'treino' ? 'corpoTreinoHint' : 'corpoDietaHint');
    hint.textContent = 'salvo';
    hint.classList.add('saved');
    setTimeout(() => { hint.textContent = ''; hint.classList.remove('saved'); }, 1800);
  }, 600);
}

// ===== INIT =====
async function init() {
  initDates();
  loadCorpo();
  await loadCats();
  await loadHabits();
  await Promise.all([loadTasks(), loadEvents(), loadFinance(), loadGoals(), loadPoints(), loadRewards()]);
  renderInicioActions();
}
init();
</script>
</body>
</html>