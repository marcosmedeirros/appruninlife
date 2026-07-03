<?php
// ARQUIVO: calendar_feed.php
// Feed .ics somente leitura para assinatura (webcal://) no Calendário do iPhone.
require_once __DIR__ . '/config.php';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="vida-em-controle.ics"');

$userId = 1;
$today = date('Y-m-d');
$DAY_CODE = ['', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];

function ics_escape(string $s): string {
    return str_replace(["\\", "\n", ",", ";"], ["\\\\", "\\n", "\\,", "\\;"], $s);
}

function next_weekday(string $fromDate, int $isoDay): string {
    // isoDay: 1=Segunda ... 7=Domingo
    $cursor = new DateTime($fromDate);
    for ($i = 0; $i < 7; $i++) {
        if ((int)$cursor->format('N') === $isoDay) {
            return $cursor->format('Ymd');
        }
        $cursor->modify('+1 day');
    }
    return $cursor->format('Ymd');
}

$lines = [];
$lines[] = 'BEGIN:VCALENDAR';
$lines[] = 'VERSION:2.0';
$lines[] = 'PRODID:-//Vida em Controle//Feed//PT';
$lines[] = 'CALSCALE:GREGORIAN';
$lines[] = 'METHOD:PUBLISH';
$lines[] = 'X-WR-CALNAME:Vida em Controle';
$lines[] = 'X-WR-TIMEZONE:America/Sao_Paulo';
$lines[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT6H';

function add_alarm(array &$lines, string $summary): void {
    $lines[] = 'BEGIN:VALARM';
    $lines[] = 'ACTION:DISPLAY';
    $lines[] = 'DESCRIPTION:' . ics_escape($summary);
    $lines[] = 'TRIGGER:PT0S';
    $lines[] = 'END:VALARM';
}

// ===== TAREFAS (tasks) =====
$stmt = $pdo->prepare("SELECT id, title, recurrence, recurrence_day, due_date, status FROM tasks WHERE user_id = ?");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $t) {
    $summary = ics_escape($t['title']);
    $uid = 'task-' . $t['id'] . '@marcosmedeiros.page';

    if ($t['recurrence'] === 'once') {
        if (!$t['due_date'] || (int)$t['status'] === 1) {
            continue; // sem data ou ja concluida: nao polui o calendario
        }
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $t['due_date']);
        $lines[] = 'SUMMARY:' . $summary;
        add_alarm($lines, $t['title']);
        $lines[] = 'END:VEVENT';
        continue;
    }

    if ($t['recurrence'] === 'daily') {
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $today);
        $lines[] = 'RRULE:FREQ=DAILY';
        $lines[] = 'SUMMARY:' . $summary;
        add_alarm($lines, $t['title']);
        $lines[] = 'END:VEVENT';
        continue;
    }

    if ($t['recurrence'] === 'weekly' && $t['recurrence_day']) {
        $start = next_weekday($today, (int)$t['recurrence_day']);
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART;VALUE=DATE:' . $start;
        $lines[] = 'RRULE:FREQ=WEEKLY;BYDAY=' . $DAY_CODE[(int)$t['recurrence_day']];
        $lines[] = 'SUMMARY:' . $summary;
        add_alarm($lines, $t['title']);
        $lines[] = 'END:VEVENT';
    }
}

// ===== HABITOS (habits) =====
$stmt = $pdo->query("SELECT id, name, recurrence, recurrence_day FROM habits");
foreach ($stmt->fetchAll() as $h) {
    $summary = ics_escape('Hábito: ' . $h['name']);
    $uid = 'habit-' . $h['id'] . '@marcosmedeiros.page';

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');

    if ($h['recurrence'] === 'weekly' && $h['recurrence_day']) {
        $start = next_weekday($today, (int)$h['recurrence_day']);
        $lines[] = 'DTSTART;VALUE=DATE:' . $start;
        $lines[] = 'RRULE:FREQ=WEEKLY;BYDAY=' . $DAY_CODE[(int)$h['recurrence_day']];
    } else {
        $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $today);
        $lines[] = 'RRULE:FREQ=DAILY';
    }
    $lines[] = 'SUMMARY:' . $summary;
    add_alarm($lines, $h['name']);
    $lines[] = 'END:VEVENT';
}

// ===== METAS (goals) com prazo =====
$stmt = $pdo->prepare("SELECT id, title, deadline FROM goals WHERE user_id = ? AND status = 0 AND deadline IS NOT NULL");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $g) {
    $summary = ics_escape('Prazo da meta: ' . $g['title']);
    $uid = 'goal-' . $g['id'] . '@marcosmedeiros.page';
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
    $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $g['deadline']);
    $lines[] = 'SUMMARY:' . $summary;
    add_alarm($lines, $summary);
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

echo implode("\r\n", $lines) . "\r\n";
