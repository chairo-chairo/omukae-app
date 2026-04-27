<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// 月ナビゲーション
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// DB取得
$pdo = get_pdo();
$notice  = $pdo->query("SELECT content FROM notices ORDER BY updated_at DESC LIMIT 1")->fetch();
$message = $pdo->query("SELECT content FROM messages WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1")->fetch();

$providers_by_id       = fetch_providers_by_id($pdo);
$pdr                   = fetch_provider_day_rules($pdo);
$default_provider_by_dow = fetch_default_provider_by_dow($pdo);
$rules_by_dow          = fetch_dismissal_rules($pdo);

// 当月スケジュールエントリ
$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day  = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
$stmt = $pdo->prepare("SELECT * FROM schedule_entries WHERE entry_date BETWEEN ? AND ?");
$stmt->execute([$first_day, $last_day]);
$entries_by_date = [];
foreach ($stmt->fetchAll() as $e) {
    $entries_by_date[$e['entry_date']] = $e;
}

// 当月祝日
$stmt = $pdo->prepare("SELECT holiday_date, label FROM holidays WHERE holiday_date BETWEEN ? AND ?");
$stmt->execute([$first_day, $last_day]);
$holidays_by_date = [];
foreach ($stmt->fetchAll() as $h) {
    $holidays_by_date[$h['holiday_date']] = $h['label'];
}

// 月ナビ用
$pm = $month - 1; $py = $year; if ($pm < 1)  { $pm = 12; $py--; }
$nm = $month + 1; $ny = $year; if ($nm > 12) { $nm = 1;  $ny++; }

$user = current_user();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h("{$year}年{$month}月 お迎え予定") ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background-color: #f0ece6; }
    .row-sat      { background-color: #d8e8f0; }
    .row-sun      { background-color: #f0dcd8; }
    .row-holiday  { background-color: #f0dcd8; }
    .badge-ok       { background-color: #c8e0c8; color: #2d5030; }
    .badge-ng       { background-color: #f0d0cc; color: #7a3028; }
    .badge-early    { background-color: #f0e8a0; color: #7a6010; }
    .badge-noschool { background-color: #e8e0f0; color: #5a4080; }
    span.rounded-full { display: inline-block !important; }
    .w-2  { width:  0.5rem !important; }
    .h-2  { height: 0.5rem !important; }
    .px-1\.5 { padding-left: 0.375rem !important; padding-right: 0.375rem !important; }
    .py-0\.5 { padding-top: 0.125rem !important; padding-bottom: 0.125rem !important; }
    .badge-ok, .badge-ng, .badge-early, .badge-noschool { white-space: nowrap !important; line-height: 1.4 !important; }
  </style>
</head>
<body class="text-gray-700 text-sm leading-relaxed p-3 sm:p-5">

  <!-- ヘッダー -->
  <div class="flex flex-col items-center mb-4">
    <h1 class="text-xl font-bold text-gray-700"><?= h("{$year}年{$month}月 お迎え予定") ?></h1>
    <p class="text-gray-400 text-xs mt-0.5">津島小学校</p>
    <div class="flex items-center gap-4 mt-2">
      <a href="?y=<?= $py ?>&m=<?= $pm ?>"
         class="text-xs px-3 py-1 rounded-full border border-gray-200 bg-white text-gray-400 hover:text-gray-600">
        ← <?= $pm ?>月
      </a>
      <span class="text-xs font-bold text-gray-500"><?= $year ?>年<?= $month ?>月</span>
      <a href="?y=<?= $ny ?>&m=<?= $nm ?>"
         class="text-xs px-3 py-1 rounded-full border border-gray-200 bg-white text-gray-400 hover:text-gray-600">
        <?= $nm ?>月 →
      </a>
    </div>
  </div>

  <?php if ($notice && $notice['content']): ?>
  <!-- 注意事項 -->
  <div class="max-w-2xl mx-auto mb-4 rounded-r-xl px-4 py-3 text-xs font-bold border-l-4"
       style="background:#f5e8cc; border-color:#c8a050; color:#7a5a1a;">
    <svg class="inline-block align-middle mr-1" width="14" height="14" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3L21 20H3L12 3Z"/><line x1="12" y1="10" x2="12" y2="15"/>
      <circle cx="12" cy="18.5" r="0.6" fill="currentColor" stroke="none"/>
    </svg>
    注意事項：<?= h($notice['content']) ?>
  </div>
  <?php endif; ?>

  <!-- 下校時間ルール -->
  <?php
  $unique_rules = [];
  foreach ($rules_by_dow as $r) {
      $unique_rules[$r['label']] = $r;
  }
  $rule_colors = [['#edf3f8','#b0c8d8','#3a6a8a'], ['#edeaf8','#b0a8d8','#35408a']];
  $ci = 0;
  ?>
  <div class="max-w-2xl mx-auto mb-5 grid grid-cols-2 gap-3 text-xs">
    <?php foreach ($unique_rules as $r):
      [$bg, $border, $tc] = $rule_colors[$ci++ % 2]; ?>
    <div class="rounded-xl p-3 border" style="background:<?= $bg ?>; border-color:<?= $border ?>;">
      <p class="font-bold mb-1" style="color:<?= $tc ?>;"><?= h($r['label']) ?></p>
      <p>下校：<span class="font-bold" style="color:#b06030;"><?= format_time($r['dismissal_time']) ?></span></p>
      <p>お迎え：<span class="font-bold" style="color:#b06030;"><?= format_time($r['pickup_start']) ?>〜<?= format_time($r['pickup_end']) ?></span></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- メインリスト -->
  <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <?php for ($day = 1; $day <= $days_in_month; $day++):
      $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
      $ts  = mktime(0, 0, 0, $month, $day, $year);
      $dow = (int)date('w', $ts);

      $is_sat     = ($dow === 6);
      $is_sun     = ($dow === 0);
      $is_holiday = isset($holidays_by_date[$date_str]);
      $is_weekend = $is_sat || $is_sun;

      $entry     = $entries_by_date[$date_str] ?? null;
      $no_school = $entry && (bool)$entry['no_school'];

      $row_class = '';
      if ($is_sat)         $row_class = 'row-sat';
      elseif ($is_sun)     $row_class = 'row-sun';
      elseif ($is_holiday) $row_class = 'row-holiday';

      $dow_color = $is_sat ? '#3a6a8a' : (($is_sun || $is_holiday) ? '#8a3828' : '#9ca3af');

      // 事業者を決定
      $provider = null;
      $pdr_rule = null;
      if (!$is_weekend && !$is_holiday && !$no_school) {
          $provider_id = null;
          if ($entry && $entry['provider_id'] !== null) {
              $provider_id = (int)$entry['provider_id'];
          } elseif ($entry === null && isset($default_provider_by_dow[$dow])) {
              $provider_id = $default_provider_by_dow[$dow];
          } elseif ($entry !== null && $entry['provider_id'] === null) {
              $provider_id = null; // 明示的に「事業者なし」
          } else {
              $provider_id = $default_provider_by_dow[$dow] ?? null;
          }
          if ($provider_id && isset($providers_by_id[$provider_id])) {
              $provider = $providers_by_id[$provider_id];
              $pdr_rule = $pdr[$provider_id][$dow] ?? null;
          }
      }

      $rule = $rules_by_dow[$dow] ?? null;

      // 送迎判定
      $pickup_available = null;
      if ($pdr_rule) {
          if ($entry && $entry['pickup_override'] !== null) {
              $pickup_available = (bool)$entry['pickup_override'];
          } else {
              $pickup_available = (bool)$pdr_rule['pickup_available'];
          }
      }

      $style = $provider ? provider_style($provider['color_key']) : null;
    ?>
    <div class="flex items-center gap-3 px-3 py-3 border-b border-gray-100 <?= $row_class ?>">
      <!-- 日付 -->
      <div class="w-10 shrink-0 text-center">
        <div class="text-base font-bold leading-tight"
             style="color:<?= ($is_weekend || $is_holiday) ? $dow_color : '#374151' ?>;">
          <?= $month ?>/<?= $day ?>
        </div>
        <div class="text-xs font-bold" style="color:<?= $dow_color ?>;"><?= dow_name($dow) ?></div>
      </div>

      <!-- 内容 -->
      <div class="flex-1 min-w-0">
        <?php if ($is_weekend): ?>
          <div class="text-xs font-bold" style="color:<?= $dow_color ?>;">
            <?= $is_sat ? '土曜日' : '日曜日' ?>
          </div>

        <?php elseif ($is_holiday): ?>
          <div class="text-xs font-bold" style="color:#8a3828;">
            <?= h($holidays_by_date[$date_str]) ?>
          </div>

        <?php elseif ($no_school): ?>
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs px-1.5 py-0.5 rounded-full badge-noschool shrink-0">
              <?= h($entry['note'] ?: '休校') ?>
            </span>
          </div>

        <?php elseif ($provider && $style): ?>
          <div class="flex items-center gap-1.5 mb-1 flex-wrap">
            <span class="w-2 h-2 rounded-full shrink-0" style="background:<?= $style['dot'] ?>;"></span>
            <span class="font-bold text-sm shrink-0" style="color:<?= $style['text'] ?>;"><?= h($provider['name']) ?></span>
            <?php if ($pickup_available !== null): ?>
              <?php if ($pickup_available): ?>
                <span class="text-xs px-1.5 py-0.5 rounded-full badge-ok shrink-0">送迎あり</span>
              <?php else: ?>
                <span class="text-xs px-1.5 py-0.5 rounded-full badge-ng shrink-0">送迎なし</span>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($pdr_rule && $pdr_rule['early_leave']): ?>
              <span class="text-xs px-1.5 py-0.5 rounded-full badge-early shrink-0">小学校早退</span>
            <?php endif; ?>
            <?php if ($pickup_available === false): ?>
              <span class="text-xs font-medium shrink-0" style="color:#b07090;">母お迎え</span>
            <?php endif; ?>
          </div>
          <div class="text-xs text-gray-400">
            <?php if ($rule): ?>
              下校 <span class="font-bold" style="color:#b06030;"><?= format_time($rule['dismissal_time']) ?></span>
              <span class="mx-1.5 text-gray-200">|</span>
              <?php if ($pdr_rule && $pdr_rule['early_leave'] && $pdr_rule['early_leave_time']): ?>
                お迎え <span class="font-bold" style="color:#b06030;"><?= format_time($pdr_rule['early_leave_time']) ?></span>
              <?php else: ?>
                お迎え <span class="font-bold" style="color:#b06030;"><?= format_time($rule['pickup_start']) ?>〜<?= format_time($rule['pickup_end']) ?></span>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($entry && $entry['note'] && !$no_school): ?>
              <span class="mx-1 text-gray-300">|</span><?= h($entry['note']) ?>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <!-- 事業者なし（通常下校） -->
          <?php if ($rule): ?>
          <div class="text-xs text-gray-500">
            下校 <span class="font-bold" style="color:#b06030;"><?= format_time($rule['dismissal_time']) ?></span>
            <span class="mx-1.5 text-gray-200">|</span>
            お迎え <span class="font-bold" style="color:#b06030;"><?= format_time($rule['pickup_start']) ?>〜<?= format_time($rule['pickup_end']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($entry && $entry['note']): ?>
            <div class="text-xs text-gray-400 mt-0.5"><?= h($entry['note']) ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <!-- 事業者カード -->
  <?php foreach ($providers_by_id as $p):
    $style  = provider_style($p['color_key']);
    $p_days = $pdr[(int)$p['id']] ?? [];
  ?>
  <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
    <div class="px-4 py-3 text-white font-bold text-sm" style="background:<?= $style['header'] ?>;">
      <svg class="inline-block align-middle mr-1" width="15" height="15" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13" rx="1"/><polyline points="16 8 20 8 23 11 23 16 16 16 16 8"/>
        <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
      </svg>
      <?= h($p['name']) ?>
    </div>
    <div class="px-4 py-3 text-sm space-y-1.5">
      <?php if (!empty($p_days)): ?>
        <p class="text-xs text-gray-500">
          利用日：<span class="font-medium text-gray-700">
            <?= implode('・', array_map(fn($d) => dow_name($d) . '曜日', array_keys($p_days))) ?>
          </span>
        </p>
      <?php endif; ?>
      <?php if ($p['service_hours']): ?>
        <p class="text-xs text-gray-500">
          サービス提供時間：<span class="font-bold" style="color:#b06030;"><?= h($p['service_hours']) ?></span>
        </p>
      <?php endif; ?>
      <?php foreach ($p_days as $dow => $r): ?>
        <div class="flex items-center gap-2 text-xs">
          <span class="text-gray-400"><?= dow_name($dow) ?>：</span>
          <?php if ($r['pickup_available']): ?>
            <span class="px-1.5 py-0.5 rounded-full badge-ok">送迎あり</span>
          <?php else: ?>
            <span class="px-1.5 py-0.5 rounded-full badge-ng">送迎なし</span>
          <?php endif; ?>
          <?php if ($r['early_leave']): ?>
            <span class="px-1.5 py-0.5 rounded-full badge-early">小学校早退</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($p['phone']): ?>
        <p class="text-xs text-gray-500">
          📞 <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $p['phone'])) ?>"
               class="underline underline-offset-2"><?= h($p['phone']) ?></a>
        </p>
      <?php endif; ?>
      <?php if ($p['address']): ?>
        <p class="text-xs text-gray-400"><?= h($p['address']) ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- ママからの伝達 -->
  <?php if ($message): ?>
  <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-4 py-3 text-white font-bold text-sm" style="background:#b8836a;">
      <svg class="inline-block align-middle mr-1" width="17" height="17" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 11l19-9-9 19-2-8-8-2z"/>
      </svg>
      ママからの伝達
    </div>
    <div class="px-4 py-4">
      <p class="text-sm <?= $message['content'] === '現在、伝達事項はありません' ? 'text-gray-400 italic' : 'text-gray-700' ?>">
        <?= nl2br(h($message['content'])) ?>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($user && $user['role'] === 'admin'): ?>
  <div class="max-w-2xl mx-auto text-center mb-4">
    <a href="/admin/" class="text-xs text-gray-400 underline">管理画面</a>
  </div>
  <?php endif; ?>

  <footer class="text-center text-gray-300 text-xs pb-6">
    <a href="/login.php" class="text-gray-300 hover:text-gray-400">管理者ログイン</a>
  </footer>

</body>
</html>
