<?php
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function format_time(?string $t): string
{
    if ($t === null || $t === '') return '';
    return substr($t, 0, 5); // HH:MM
}

function dow_name(int $dow): string
{
    return ['日', '月', '火', '水', '木', '金', '土'][$dow] ?? '';
}

function provider_style(string $color_key): array
{
    return match ($color_key) {
        'ef'    => ['dot' => '#b8736a', 'text' => '#7a3d35', 'header' => '#b8736a', 'light' => '#f5e8e6'],
        'honey' => ['dot' => '#6a9e88', 'text' => '#2d6050', 'header' => '#6a9e88', 'light' => '#e4f0ea'],
        'arte'  => ['dot' => '#7a8ab8', 'text' => '#35408a', 'header' => '#7a8ab8', 'light' => '#e8ecf8'],
        default => ['dot' => '#9ca3af', 'text' => '#4b5563', 'header' => '#9ca3af', 'light' => '#f3f4f6'],
    };
}

// DB取得: 全事業者（id => 事業者）
function fetch_providers_by_id(PDO $pdo): array
{
    $result = [];
    foreach ($pdo->query("SELECT * FROM providers WHERE active = 1 ORDER BY sort_order") as $p) {
        $result[(int)$p['id']] = $p;
    }
    return $result;
}

// DB取得: 事業者デイルール [provider_id][day_of_week] => rule
function fetch_provider_day_rules(PDO $pdo): array
{
    $result = [];
    foreach ($pdo->query("SELECT * FROM provider_day_rules") as $r) {
        $result[(int)$r['provider_id']][(int)$r['day_of_week']] = $r;
    }
    return $result;
}

// DB取得: 曜日→デフォルト事業者ID
function fetch_default_provider_by_dow(PDO $pdo): array
{
    $result = [];
    foreach ($pdo->query("SELECT provider_id, day_of_week FROM provider_day_rules") as $r) {
        $result[(int)$r['day_of_week']] = (int)$r['provider_id'];
    }
    return $result;
}

// DB取得: 下校時間ルール [day_of_week] => rule
function fetch_dismissal_rules(PDO $pdo): array
{
    $result = [];
    foreach ($pdo->query("SELECT * FROM dismissal_rules") as $r) {
        $result[(int)$r['day_of_week']] = $r;
    }
    return $result;
}
