<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin','viewer']) ? $_POST['role'] : 'viewer';

        if ($name && $email && strlen($password) >= 8) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            try {
                $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)")
                    ->execute([$name, $email, $hash, $role]);
                header('Location: users.php?saved=1');
                exit;
            } catch (PDOException $e) {
                $error = 'このメールアドレスはすでに登録されています';
            }
        } else {
            $error = 'パスワードは8文字以上で入力してください';
        }
    } elseif ($action === 'delete') {
        $del_id = (int)$_POST['id'];
        if ($del_id !== (int)current_user()['id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        }
        header('Location: users.php');
        exit;
    } elseif ($action === 'change_password') {
        $uid      = (int)$_POST['id'];
        $password = $_POST['password'] ?? '';
        if (strlen($password) >= 8) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
            header('Location: users.php?saved=1');
            exit;
        } else {
            $error = 'パスワードは8文字以上で入力してください';
        }
    }
}

$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id")->fetchAll();

admin_head('ユーザー管理');
admin_nav('users');
?>

<h2 class="text-base font-bold text-gray-700 mb-4">ユーザー管理</h2>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"><?= h($error) ?></div>
<?php endif; ?>

<!-- ユーザー一覧 -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
  <?php foreach ($users as $u): ?>
  <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-0">
    <div class="flex-1 min-w-0">
      <div class="text-sm font-bold text-gray-700"><?= h($u['name']) ?></div>
      <div class="text-xs text-gray-400"><?= h($u['email']) ?> ／
        <span class="<?= $u['role'] === 'admin' ? 'text-purple-500' : 'text-gray-400' ?>">
          <?= $u['role'] === 'admin' ? '管理者' : '閲覧者' ?>
        </span>
      </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <!-- パスワード変更 -->
      <button onclick="togglePwForm(<?= $u['id'] ?>)"
              class="text-xs px-3 py-1 rounded-xl bg-gray-100 text-gray-500 hover:bg-gray-200">PW変更</button>
      <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
      <form method="post" class="inline">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button type="submit" class="text-xs text-red-400 hover:text-red-600"
                onclick="return confirm('削除しますか？')">削除</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <!-- PW変更フォーム（非表示） -->
  <div id="pw-form-<?= $u['id'] ?>" class="hidden px-4 pb-3">
    <form method="post" class="flex gap-2">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="id" value="<?= $u['id'] ?>">
      <input type="password" name="password" placeholder="新パスワード（8文字以上）" required minlength="8"
             class="input flex-1" style="padding:0.375rem 0.75rem;">
      <button type="submit" class="btn-primary text-xs px-4 py-2 rounded-xl font-bold">変更</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<!-- 新規ユーザー追加 -->
<div class="bg-white rounded-2xl shadow-sm p-4">
  <h3 class="text-sm font-bold text-gray-600 mb-3">新しいユーザーを追加</h3>
  <form method="post" class="space-y-3">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-400 mb-1">名前</label>
        <input type="text" name="name" required class="input" value="<?= h($_POST['name'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-xs text-gray-400 mb-1">ロール</label>
        <select name="role" class="input">
          <option value="viewer">閲覧者</option>
          <option value="admin">管理者</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-xs text-gray-400 mb-1">メールアドレス</label>
      <input type="email" name="email" required class="input" value="<?= h($_POST['email'] ?? '') ?>">
    </div>
    <div>
      <label class="block text-xs text-gray-400 mb-1">パスワード（8文字以上）</label>
      <input type="password" name="password" required minlength="8" class="input">
    </div>
    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-bold">追加</button>
  </form>
</div>

<script>
function togglePwForm(id) {
  const el = document.getElementById('pw-form-' + id);
  el.classList.toggle('hidden');
}
</script>

<?php admin_foot(); ?>
