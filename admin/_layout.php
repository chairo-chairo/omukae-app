<?php
// 管理画面共通レイアウトヘルパー
function admin_head(string $title): void
{ ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - 管理画面</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background-color: #f0ece6; }
    .btn-primary { background:#7a7ab0; color:#fff; }
    .btn-primary:hover { background:#6a6aa0; }
    .btn-danger { background:#c07070; color:#fff; }
    .btn-danger:hover { background:#a05050; }
    .input { border:1px solid #e5e7eb; border-radius:0.75rem; padding:0.5rem 0.75rem; font-size:0.875rem; width:100%; }
    .input:focus { outline:none; box-shadow:0 0 0 2px #c7d2fe; }
  </style>
<?php } ?>
<?php
function admin_nav(string $current = ''): void
{
    $user = current_user();
    $links = [
        'schedule'  => ['href' => 'schedule.php',  'label' => '月次スケジュール'],
        'providers' => ['href' => 'providers.php',  'label' => '事業者'],
        'rules'     => ['href' => 'rules.php',      'label' => '下校時間'],
        'notices'   => ['href' => 'notices.php',    'label' => '注意事項'],
        'messages'  => ['href' => 'messages.php',   'label' => '伝達'],
        'users'     => ['href' => 'users.php',      'label' => 'ユーザー'],
    ];
?>
<body class="text-gray-700 text-sm">
  <nav class="bg-white border-b border-gray-100 px-4 py-3 flex items-center gap-3 flex-wrap sticky top-0 z-10 shadow-sm">
    <a href="/admin/" class="font-bold text-gray-600 mr-2 shrink-0">管理</a>
    <?php foreach ($links as $key => $l): ?>
      <a href="<?= $l['href'] ?>"
         class="text-xs px-3 py-1 rounded-full <?= $current === $key ? 'bg-[#7a7ab0] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
        <?= $l['label'] ?>
      </a>
    <?php endforeach; ?>
    <div class="ml-auto flex items-center gap-3 shrink-0">
      <a href="/" class="text-xs text-gray-400 hover:underline">表示</a>
      <a href="/logout.php" class="text-xs text-gray-400 hover:underline">ログアウト</a>
    </div>
  </nav>
  <div class="max-w-3xl mx-auto p-4 sm:p-6">
<?php } ?>
<?php
function admin_foot(): void
{ ?>
  </div>
</body>
</html>
<?php }
