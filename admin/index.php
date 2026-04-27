<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

admin_head('管理画面トップ');
?>
<body class="text-gray-700 text-sm" style="background:#f0ece6;">
<div class="max-w-2xl mx-auto p-4 sm:p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-lg font-bold text-gray-700">管理画面</h1>
    <div class="flex items-center gap-3">
      <span class="text-xs text-gray-400"><?= h(current_user()['name']) ?></span>
      <a href="/logout.php"
         class="text-xs px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50">
        ログアウト
      </a>
    </div>
  </div>

  <div class="grid gap-3">
    <?php
    $menus = [
      ['href' => 'schedule.php',  'title' => '月次スケジュール', 'desc' => '日別の事業者・休校・メモの管理'],
      ['href' => 'messages.php',  'title' => 'ママからの伝達',   'desc' => '閲覧者向けのメッセージ'],
      ['href' => 'notices.php',   'title' => '注意事項',         'desc' => 'ページ上部の注意事項テキスト'],
      ['href' => 'providers.php', 'title' => '事業者マスタ',     'desc' => '事業者・利用日・送迎・早退の設定'],
      ['href' => 'rules.php',     'title' => '下校時間ルール',   'desc' => '曜日別の下校・お迎え時間'],
      ['href' => 'users.php',     'title' => 'ユーザー管理',     'desc' => '管理者・閲覧者アカウント'],
    ];
    foreach ($menus as $m):
    ?>
    <a href="<?= h($m['href']) ?>"
       class="flex items-center gap-4 bg-white rounded-2xl shadow-sm px-4 py-4 hover:shadow-md transition-shadow">
      <div class="flex-1 min-w-0">
        <div class="font-bold text-sm text-gray-700"><?= h($m['title']) ?></div>
        <div class="text-xs text-gray-400 mt-0.5"><?= h($m['desc']) ?></div>
      </div>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d1d5db"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-6 text-center">
    <a href="/" class="text-xs text-gray-400 underline">← スケジュール表示</a>
  </div>
</div>
</body>
</html>
