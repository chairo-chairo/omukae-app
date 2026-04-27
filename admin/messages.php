<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action  = $_POST['action'] ?? '';
    $content = trim($_POST['content'] ?? '');

    if ($action === 'add' && $content) {
        // 既存を非アクティブにして新規追加
        $pdo->exec("UPDATE messages SET is_active = 0");
        $pdo->prepare("INSERT INTO messages (content, is_active) VALUES (?, 1)")->execute([$content]);
    } elseif ($action === 'deactivate') {
        $pdo->exec("UPDATE messages SET is_active = 0");
        $pdo->prepare("INSERT INTO messages (content, is_active) VALUES (?, 1)")
            ->execute(['現在、伝達事項はありません']);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([(int)$_POST['id']]);
    }
    header('Location: messages.php?saved=1');
    exit;
}

$active  = $pdo->query("SELECT * FROM messages WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1")->fetch();
$history = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 20")->fetchAll();

admin_head('ママからの伝達');
admin_nav('messages');
?>

<h2 class="text-base font-bold text-gray-700 mb-4">ママからの伝達</h2>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>

<!-- 現在の伝達 -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-5">
  <h3 class="text-sm font-bold text-gray-600 mb-2">現在の表示内容</h3>
  <?php if ($active): ?>
    <p class="text-sm text-gray-700 whitespace-pre-wrap mb-3"><?= h($active['content']) ?></p>
    <form method="post" class="inline">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="deactivate">
      <button type="submit"
              class="text-xs px-4 py-2 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50">
        「現在、伝達事項はありません」に戻す
      </button>
    </form>
  <?php else: ?>
    <p class="text-xs text-gray-400">表示中の伝達はありません</p>
  <?php endif; ?>
</div>

<!-- 新規伝達入力 -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-5">
  <h3 class="text-sm font-bold text-gray-600 mb-3">新しい伝達を投稿</h3>
  <form method="post" class="space-y-3">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <textarea name="content" rows="4" required placeholder="伝達内容を入力してください"
              class="input resize-none"><?= h($_POST['content'] ?? '') ?></textarea>
    <button type="submit"
            class="btn-primary px-6 py-2.5 rounded-xl text-sm font-bold">
      投稿して表示する
    </button>
  </form>
</div>

<!-- 履歴 -->
<?php if (!empty($history)): ?>
<div class="bg-white rounded-2xl shadow-sm p-4">
  <h3 class="text-sm font-bold text-gray-600 mb-3">投稿履歴</h3>
  <div class="space-y-2">
    <?php foreach ($history as $m): ?>
    <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0">
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-600 whitespace-pre-wrap"><?= h($m['content']) ?></p>
        <p class="text-xs text-gray-300 mt-0.5">
          <?= h($m['created_at']) ?>
          <?php if ($m['is_active']): ?><span class="text-green-500 ml-1">● 表示中</span><?php endif; ?>
        </p>
      </div>
      <form method="post" class="shrink-0">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <button type="submit" class="text-xs text-red-300 hover:text-red-500"
                onclick="return confirm('削除しますか？')">削除</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php admin_foot(); ?>
