<?php
declare(strict_types=1);
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>OJT360 Setup Check</title><style>body{font-family:Arial,sans-serif;background:#f5f7fb;padding:30px;color:#172033}.card{max-width:760px;margin:auto;background:#fff;padding:25px;border-radius:16px;box-shadow:0 10px 30px #0001}.ok{color:#15803d}.bad{color:#b91c1c}.row{padding:12px 0;border-bottom:1px solid #eee}code{background:#f1f5f9;padding:2px 5px;border-radius:4px}</style></head><body><div class="card"><h1>OJT360 Setup Check</h1>';

try {
    $pdo = db();
    echo '<div class="row ok">Database connection: OK</div>';
    foreach (['users','students','companies','company_qr_codes','internships','attendance','journals','notifications','announcements','activity_logs','support_requests','assistant_messages'] as $table) {
        $safe = preg_replace('/[^a-z0-9_]/i','',$table);
        $exists = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='" . DB_NAME . "' AND table_name='" . $safe . "'")->fetchColumn();
        echo '<div class="row">Table <code>'.h($table).'</code>: '.($exists ? '<span class="ok">OK</span>' : '<span class="bad">Missing</span>').'</div>';
    }
    $ai=openai_config();
    $configured=!empty($ai['api_key']) && !str_contains((string)$ai['api_key'],'PASTE_YOUR_');
    echo '<div class="row">AI API key: '.($configured ? '<span class="ok">Configured</span>' : '<span class="bad">Not configured — setup mode is active</span>').'</div>';
    echo '<div class="row">AI model: <code>'.h((string)$ai['model']).'</code></div>';
    echo '<div class="row">AI web search: '.(!empty($ai['web_search']) ? '<span class="ok">Enabled</span>' : 'Disabled').'</div>';
    $qr=$pdo->prepare("SELECT COUNT(*) FROM company_qr_codes WHERE qr_value='OJT360-COMPANY-001' AND is_active=1"); $qr->execute();
    echo '<div class="row">Demo QR <code>OJT360-COMPANY-001</code>: '.((int)$qr->fetchColumn() ? '<span class="ok">Active</span>' : '<span class="bad">Missing</span>').'</div>';
} catch (Throwable $e) {
    echo '<div class="row bad">Setup check failed: '.h($e->getMessage()).'</div>';
}

echo '<p><a href="index.php">Back to dashboard</a></p></div></body></html>';
