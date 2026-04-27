<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_init();
if (is_admin()) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = get_pdo()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ];
            header('Location: /admin/');
            exit;
        }
    }
    $error = 'メールアドレスまたはパスワードが正しくありません';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { background-color: #f0ece6; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm p-6">
    <h1 class="text-lg font-bold text-gray-700 mb-6 text-center">管理者ログイン</h1>
    <?php if ($error): ?>
      <div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-xs text-gray-500 mb-1">メールアドレス</label>
        <input type="email" name="email" required autocomplete="email"
               value="<?= h($_POST['email'] ?? '') ?>"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">パスワード</label>
        <input type="password" name="password" required autocomplete="current-password"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
      </div>
      <button type="submit"
              class="w-full py-2.5 rounded-xl text-white font-bold text-sm transition-colors"
              style="background:#7a7ab0;" onmouseover="this.style.background='#6a6aa0'" onmouseout="this.style.background='#7a7ab0'">
        ログイン
      </button>
    </form>
    <div class="mt-5 text-center">
      <a href="/" class="text-xs text-gray-400 underline">← スケジュールに戻る</a>
    </div>
  </div>
</body>
</html>
