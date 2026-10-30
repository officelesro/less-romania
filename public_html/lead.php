<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
$RO = false;
$M = ['Invalid request.' => 'Cerere invalidă.', 'Too many attempts.' => 'Prea multe încercări. Reveniți mai târziu.', 'Please choose what you are interested in.' => 'Alegeți subiectul de interes.', 'Please enter your name.' => 'Introduceți numele dumneavoastră.', 'Please enter a valid email address.' => 'Introduceți o adresă de e-mail validă.', 'Please tick the consent box to continue.' => 'Bifați acordul pentru a continua.', 'We could not process your request right now.' => 'Solicitarea nu a putut fi procesată acum.'];
function out($c, $m = '') { global $RO, $M; if ($RO && isset($M[$m])) $m = $M[$m]; http_response_code($c); echo json_encode($c === 200 ? ['ok' => true] : ['error' => $m]); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, 'Invalid request.');
$o = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($o !== '' && parse_url($o, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')) out(403, 'Invalid request.');
$b = json_decode(file_get_contents('php://input', false, null, 0, 10240), true);
if (!is_array($b)) out(400, 'Invalid request.');
$RO = (($b['lang'] ?? '') === 'ro');
function c($v, $n) { return mb_substr(trim(preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)($v ?? ''))), 0, $n); }
if (c($b['hp_url'] ?? '', 10) !== '') out(200);           // honeypot
$P = __DIR__ . '/private';
@mkdir($P . '/rate', 0755, true);
$f = $P . '/rate/' . md5($_SERVER['REMOTE_ADDR'] ?? '') . '.json';
$t = array_values(array_filter(json_decode((string)@file_get_contents($f), true) ?: [], function ($x) { return $x > time() - 600; }));
if (count($t) >= 5) out(429, 'Too many attempts.');
$t[] = time(); @file_put_contents($f, json_encode($t), LOCK_EX);
$opt = ['Becoming a member', 'Partnering with LES România', 'The Licensing Academy', 'Listing a technology or partner request'];
$l = ['interest' => c($b['interest'] ?? '', 80), 'name' => c($b['name'] ?? '', 120), 'organisation' => c($b['organisation'] ?? '', 160), 'email' => c($b['email'] ?? '', 200), 'date' => date('c')];
if (!in_array($l['interest'], $opt, true)) out(400, 'Please choose what you are interested in.');
if ($l['name'] === '') out(400, 'Please enter your name.');
if (!filter_var($l['email'], FILTER_VALIDATE_EMAIL)) out(400, 'Please enter a valid email address.');
if (($b['consent'] ?? false) !== true) out(400, 'Please tick the consent box to continue.');
$row = array_map(function ($v) { return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }, array_values($l));
$saved = false; $sent = false;
$h = @fopen($P . '/leads.csv', 'a'); if ($h) { $saved = fputcsv($h, $row) !== false; fclose($h); }
try {
  $cfg = require $P . '/config.php';
  require $P . '/phpmailer/Exception.php'; require $P . '/phpmailer/PHPMailer.php'; require $P . '/phpmailer/SMTP.php';
  $m = new \PHPMailer\PHPMailer\PHPMailer(true);
  $m->isSMTP(); $m->Host = $cfg['host']; $m->SMTPAuth = true; $m->Username = $cfg['user']; $m->Password = $cfg['pass'];
  $m->SMTPSecure = 'ssl'; $m->Port = 465; $m->CharSet = 'UTF-8';
  $m->setFrom($cfg['from'], 'LES România website'); $m->addAddress($cfg['to']); $m->addReplyTo($l['email'], $l['name']);
  $m->Subject = 'LES România website: ' . $l['interest'];
  $m->Body = "Interest: {$l['interest']}\nName: {$l['name']}\nOrganisation: {$l['organisation']}\nEmail: {$l['email']}\nLanguage: " . ($RO ? 'RO' : 'EN') . "\nGDPR consent: yes ({$l['date']})";
  $m->send(); $sent = true;
} catch (\Throwable $x) { @file_put_contents($P . '/error.log', date('c') . ' ' . $x->getMessage() . "\n", FILE_APPEND); }
if (!$saved && !$sent) out(500, 'We could not process your request right now.');
out(200);
