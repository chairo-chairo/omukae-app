<?php
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Railway / 本番環境変数（MYSQLHOST が設定されていれば優先）
    if (getenv('MYSQLHOST') !== false) {
        $host = getenv('MYSQLHOST');
        $port = getenv('MYSQLPORT') ?: '3306';
        $name = getenv('MYSQLDATABASE');
        $user = getenv('MYSQLUSER');
        $pass = getenv('MYSQLPASSWORD');
    } else {
        // ローカル: config/db.php から取得
        $cfg  = require __DIR__ . '/../config/db.php';
        $host = $cfg['host'];
        $port = $cfg['port'] ?? '3306';
        $name = $cfg['name'];
        $user = $cfg['user'];
        $pass = $cfg['pass'];
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
