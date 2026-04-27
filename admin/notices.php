<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $content = trim($_POST['content'] ?? '');
    $existing = $pdo->query("SELECT id FROM notices LIMIT 1")->fetch();
    if ($existing) {
        $pdo->prepare("UPDATE notices SET content = ? WHERE id = ?")->execute([$content, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO notices (content) VALUES (?)")->execute([$content]);
    }
    header('Location: notices.php?saved=1');
    exit;
}

$notice = $pdo->query("SELECT * FROM notices ORDER BY updated_at DESC LIMIT 1")->fetch();

admin_head('注意事項');
admin_nav('notices');
?>

<h2 class="text-base font-bold text-gray-700 mb-4">注意事項</h2>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-4">
  <p class="text-xs text-gray-400 mb-3">スケジュール画面上部に表示される注意事項テキストです。空欄にすると非表示になります。</p>
  <form method="post" class="space-y-3">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="text" name="content" value="<?= h($notice['content'] ?? '') ?>"
           placeholder="例：教室まで迎えにきてください"
           class="input">
    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-bold">保存</button>
  </form>
</div>

<?php admin_foot(); ?>
