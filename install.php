<?php
/**
 * 初回セットアップ: テーブル作成 + 初期データ投入
 * 実行後はこのファイルをサーバーから削除してください。
 */
require_once __DIR__ . '/includes/db.php';

$pdo = get_pdo();
$errors = [];
$done   = [];

$sqls = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'notices' => "CREATE TABLE IF NOT EXISTS notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content TEXT NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    'dismissal_rules' => "CREATE TABLE IF NOT EXISTS dismissal_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week TINYINT NOT NULL,
        label VARCHAR(100) NOT NULL,
        dismissal_time TIME NOT NULL,
        pickup_start TIME NOT NULL,
        pickup_end TIME NOT NULL,
        UNIQUE KEY uq_dow (day_of_week)
    )",
    'providers' => "CREATE TABLE IF NOT EXISTS providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        color_key VARCHAR(50) NOT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        service_hours VARCHAR(100) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    )",
    'provider_day_rules' => "CREATE TABLE IF NOT EXISTS provider_day_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        day_of_week TINYINT NOT NULL,
        pickup_available TINYINT(1) NOT NULL DEFAULT 0,
        early_leave TINYINT(1) NOT NULL DEFAULT 0,
        early_leave_time TIME DEFAULT NULL,
        UNIQUE KEY uq_provider_dow (provider_id, day_of_week),
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
    )",
    'schedule_entries' => "CREATE TABLE IF NOT EXISTS schedule_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_date DATE NOT NULL,
        provider_id INT DEFAULT NULL,
        pickup_override TINYINT(1) DEFAULT NULL,
        no_school TINYINT(1) NOT NULL DEFAULT 0,
        note VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY uq_date (entry_date),
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL
    )",
    'holidays' => "CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_date DATE NOT NULL UNIQUE,
        label VARCHAR(100) NOT NULL
    )",
    'messages' => "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content TEXT NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
];

$seeds = [
    "INSERT IGNORE INTO notices (id, content) VALUES (1, '教室まで迎えにきてください')",
    "INSERT IGNORE INTO dismissal_rules (day_of_week, label, dismissal_time, pickup_start, pickup_end) VALUES
        (1,'月・火・木・金（5時間授業）','14:35:00','14:40:00','14:50:00'),
        (2,'月・火・木・金（5時間授業）','14:35:00','14:40:00','14:50:00'),
        (3,'水曜日（5時間授業・掃除なし時間短縮）','14:20:00','14:25:00','14:30:00'),
        (4,'月・火・木・金（5時間授業）','14:35:00','14:40:00','14:50:00'),
        (5,'月・火・木・金（5時間授業）','14:35:00','14:40:00','14:50:00')",
    "INSERT IGNORE INTO providers (id,name,color_key,phone,address,service_hours,sort_order) VALUES
        (1,'放課後デイ エフ','ef','050-XXXX-XXXX','住所を入力してください','10:00〜19:00',1),
        (2,'ウェルハニーバン','honey','086-XXX-XXXX','住所を入力してください','9:00〜19:00',2),
        (3,'あるてみす','arte',NULL,'住所を入力してください','9:00〜18:00',3)",
    "INSERT IGNORE INTO provider_day_rules (provider_id,day_of_week,pickup_available,early_leave,early_leave_time) VALUES
        (1,1,0,0,NULL),
        (2,2,1,1,'13:30:00'),
        (2,4,0,1,'13:30:00'),
        (3,3,1,0,NULL),
        (3,5,1,0,NULL)",
    "INSERT IGNORE INTO messages (id,content,is_active) VALUES (1,'現在、伝達事項はありません',1)",
];

foreach ($sqls as $table => $sql) {
    try {
        $pdo->exec($sql);
        $done[] = "✓ テーブル作成: {$table}";
    } catch (PDOException $e) {
        $errors[] = "✗ {$table}: " . $e->getMessage();
    }
}

foreach ($seeds as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $errors[] = "✗ 初期データ: " . $e->getMessage();
    }
}
$done[] = "✓ 初期データ投入完了";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>インストール</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { background:#f0ece6; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm p-6">
    <h1 class="text-lg font-bold text-gray-700 mb-4">セットアップ</h1>

    <?php if (empty($errors)): ?>
      <div class="text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3 mb-4">
        ✓ セットアップ完了！
      </div>
    <?php else: ?>
      <div class="text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3 mb-4">
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="space-y-1 text-xs text-gray-500 mb-6">
      <?php foreach ($done as $d): ?>
        <div><?= htmlspecialchars($d) ?></div>
      <?php endforeach; ?>
    </div>

    <p class="text-xs text-red-500 mb-4">⚠️ このファイルは使用後に削除してください</p>

    <div class="flex gap-3">
      <a href="/setup.php" class="flex-1 text-center py-2.5 rounded-xl text-white text-sm font-bold"
         style="background:#7a7ab0;">→ パスワード設定へ</a>
      <a href="/" class="flex-1 text-center py-2.5 rounded-xl border border-gray-200 text-gray-500 text-sm">
        トップへ</a>
    </div>
  </div>
</body>
</html>
