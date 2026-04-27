<?php
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Railway: MYSQL_HOST (アンダースコア) または MYSQLHOST どちらでも対応
    $host_env = getenv('MYSQL_HOST') !== false ? 'MYSQL_HOST' : (getenv('MYSQLHOST') !== false ? 'MYSQLHOST' : null);
    if ($host_env !== null) {
        $host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST');
        $port = getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306';
        $name = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE');
        $user = getenv('MYSQL_USER') ?: getenv('MYSQLUSER');
        $pass = getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD');
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
