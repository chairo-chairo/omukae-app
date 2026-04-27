<?php
/**
 * 初回セットアップ: 管理者パスワードのハッシュを生成します。
 * 本番環境では使用後にこのファイルを削除してください。
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (strlen($password) >= 8) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $sql  = "UPDATE users SET password_hash = '{$hash}' WHERE email = '" . htmlspecialchars($_POST['email']) . "';";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>セットアップ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { background: #f0ece6; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-sm p-6">
    <h1 class="text-lg font-bold text-gray-700 mb-2">管理者パスワード設定</h1>
    <p class="text-xs text-red-500 mb-4">⚠️ 使用後はこのファイルをサーバーから削除してください</p>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-xs text-gray-500 mb-1">メールアドレス（usersテーブルに登録済みのもの）</label>
        <input type="email" name="email" required
               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">新しいパスワード（8文字以上）</label>
        <input type="password" name="password" required minlength="8"
               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
      </div>
      <button type="submit"
              class="w-full py-2.5 rounded-xl text-white font-bold text-sm"
              style="background:#7a7ab0;">ハッシュを生成</button>
    </form>

    <?php if (!empty($hash)): ?>
    <div class="mt-6 p-4 bg-gray-50 rounded-xl text-xs space-y-2">
      <p class="font-bold text-gray-700">生成されたSQL（MySQLで実行してください）:</p>
      <code class="block break-all text-gray-600"><?= htmlspecialchars($sql) ?></code>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>
