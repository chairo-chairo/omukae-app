<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

// POST: スケジュール保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    csrf_verify();
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $upsert = $pdo->prepare("
        INSERT INTO schedule_entries (entry_date, provider_id, pickup_override, no_school, note)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            provider_id = VALUES(provider_id),
            pickup_override = VALUES(pickup_override),
            no_school = VALUES(no_school),
            note = VALUES(note)
    ");

    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = (int)date('w', mktime(0, 0, 0, $month, $d, $year));
        if ($dow === 0 || $dow === 6) continue; // 土日スキップ

        $no_school       = isset($_POST['no_school'][$date_str]) ? 1 : 0;
        $provider_id_raw = $_POST['provider'][$date_str] ?? '';
        $pickup_raw      = $_POST['pickup'][$date_str] ?? '';
        $note            = trim($_POST['note'][$date_str] ?? '');

        $provider_id     = ($provider_id_raw === '' || $provider_id_raw === 'default') ? null : (int)$provider_id_raw;
        $pickup_override = ($pickup_raw === '') ? null : (int)$pickup_raw;

        if ($no_school || $provider_id !== null || $pickup_override !== null || $note !== '') {
            $upsert->execute([$date_str, $provider_id, $pickup_override, $no_school, $note ?: null]);
        } else {
            // デフォルトに戻す＝エントリ削除
            $pdo->prepare("DELETE FROM schedule_entries WHERE entry_date = ?")->execute([$date_str]);
        }
    }
    header("Location: schedule.php?y={$year}&m={$month}&saved=1");
    exit;
}

// POST: 祝日登録
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_holiday'])) {
    csrf_verify();
    $hdate = $_POST['holiday_date'] ?? '';
    $hlabel = trim($_POST['holiday_label'] ?? '');
    if ($hdate && $hlabel) {
        $pdo->prepare("INSERT INTO holidays (holiday_date, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)")
            ->execute([$hdate, $hlabel]);
    }
    header("Location: schedule.php?y={$year}&m={$month}");
    exit;
}

// POST: 祝日削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_holiday'])) {
    csrf_verify();
    $pdo->prepare("DELETE FROM holidays WHERE holiday_date = ?")->execute([$_POST['holiday_date']]);
    header("Location: schedule.php?y={$year}&m={$month}");
    exit;
}

// データ取得
$providers_by_id       = fetch_providers_by_id($pdo);
$default_provider_by_dow = fetch_default_provider_by_dow($pdo);

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day  = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$stmt = $pdo->prepare("SELECT * FROM schedule_entries WHERE entry_date BETWEEN ? AND ?");
$stmt->execute([$first_day, $last_day]);
$entries_by_date = [];
foreach ($stmt->fetchAll() as $e) $entries_by_date[$e['entry_date']] = $e;

$stmt = $pdo->prepare("SELECT * FROM holidays WHERE holiday_date BETWEEN ? AND ?");
$stmt->execute([$first_day, $last_day]);
$holidays_by_date = [];
foreach ($stmt->fetchAll() as $h) $holidays_by_date[$h['holiday_date']] = $h['label'];

$pm = $month - 1; $py = $year; if ($pm < 1)  { $pm = 12; $py--; }
$nm = $month + 1; $ny = $year; if ($nm > 12) { $nm = 1;  $ny++; }

admin_head('月次スケジュール');
admin_nav('schedule');
?>

<div class="flex items-center justify-between mb-4">
  <h2 class="text-base font-bold text-gray-700">月次スケジュール</h2>
  <div class="flex items-center gap-2">
    <a href="?y=<?= $py ?>&m=<?= $pm ?>" class="text-xs px-3 py-1 rounded-full bg-white border border-gray-200 text-gray-500">← <?= $pm ?>月</a>
    <span class="text-sm font-bold text-gray-600"><?= $year ?>年<?= $month ?>月</span>
    <a href="?y=<?= $ny ?>&m=<?= $nm ?>" class="text-xs px-3 py-1 rounded-full bg-white border border-gray-200 text-gray-500"><?= $nm ?>月 →</a>
  </div>
</div>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>

<!-- 祝日・休校管理 -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-5">
  <h3 class="text-sm font-bold text-gray-600 mb-3">祝日・休校日の登録</h3>
  <form method="post" class="flex flex-wrap gap-2 items-end">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="save_holiday" value="1">
    <div>
      <label class="block text-xs text-gray-400 mb-1">日付</label>
      <input type="date" name="holiday_date" required min="<?= $first_day ?>" max="<?= $last_day ?>"
             class="input" style="width:auto;">
    </div>
    <div class="flex-1 min-w-32">
      <label class="block text-xs text-gray-400 mb-1">名称</label>
      <input type="text" name="holiday_label" placeholder="例：憲法記念日" required class="input">
    </div>
    <button type="submit" class="btn-primary px-4 py-2 rounded-xl text-sm font-bold">追加</button>
  </form>
  <?php if (!empty($holidays_by_date)): ?>
  <div class="mt-3 space-y-1">
    <?php foreach ($holidays_by_date as $hd => $hl): ?>
    <form method="post" class="flex items-center gap-2">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="delete_holiday" value="1">
      <input type="hidden" name="holiday_date" value="<?= h($hd) ?>">
      <span class="text-xs text-gray-600"><?= h($hd) ?> — <?= h($hl) ?></span>
      <button type="submit" class="text-xs text-red-400 hover:text-red-600">削除</button>
    </form>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- スケジュール編集フォーム -->
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="save_schedule" value="1">

  <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-5">
    <?php for ($d = 1; $d <= $days_in_month; $d++):
      $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $ts  = mktime(0, 0, 0, $month, $d, $year);
      $dow = (int)date('w', $ts);
      if ($dow === 0 || $dow === 6) continue;
      if (isset($holidays_by_date[$date_str])) continue;

      $entry = $entries_by_date[$date_str] ?? null;
      $default_pid = $default_provider_by_dow[$dow] ?? null;

      $cur_no_school = $entry ? (bool)$entry['no_school'] : false;
      $cur_provider  = $entry ? $entry['provider_id'] : null;
      $cur_pickup    = $entry ? $entry['pickup_override'] : null;
      $cur_note      = $entry ? ($entry['note'] ?? '') : '';
    ?>
    <div class="flex flex-wrap items-start gap-3 px-3 py-3 border-b border-gray-100 <?= $cur_no_school ? 'bg-purple-50' : '' ?>">
      <!-- 日付 -->
      <div class="w-12 shrink-0">
        <div class="text-sm font-bold text-gray-700"><?= $month ?>/<?= $d ?></div>
        <div class="text-xs text-gray-400"><?= dow_name($dow) ?></div>
      </div>

      <!-- 休校チェックボックス -->
      <label class="flex items-center gap-1.5 text-xs text-gray-500 shrink-0 mt-1">
        <input type="checkbox" name="no_school[<?= $date_str ?>]"
               <?= $cur_no_school ? 'checked' : '' ?>
               class="rounded" onchange="toggleRow(this, '<?= $date_str ?>')">
        休校
      </label>

      <!-- 事業者 -->
      <div class="shrink-0" id="row-provider-<?= $date_str ?>">
        <select name="provider[<?= $date_str ?>]"
                class="input text-xs" style="width:auto; padding:0.25rem 0.5rem;">
          <option value="default" <?= $cur_provider === null ? 'selected' : '' ?>>デフォルト</option>
          <option value="" <?= ($entry !== null && $cur_provider === null && !$cur_no_school) ? 'selected' : '' ?>>なし</option>
          <?php foreach ($providers_by_id as $p): ?>
            <option value="<?= $p['id'] ?>"
              <?= ((int)$cur_provider === (int)$p['id']) ? 'selected' : '' ?>>
              <?= h($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 送迎 -->
      <div class="shrink-0" id="row-pickup-<?= $date_str ?>">
        <select name="pickup[<?= $date_str ?>]"
                class="input text-xs" style="width:auto; padding:0.25rem 0.5rem;">
          <option value="" <?= $cur_pickup === null ? 'selected' : '' ?>>送迎デフォルト</option>
          <option value="1" <?= $cur_pickup === '1' ? 'selected' : '' ?>>送迎あり</option>
          <option value="0" <?= $cur_pickup === '0' ? 'selected' : '' ?>>送迎なし</option>
        </select>
      </div>

      <!-- メモ -->
      <div class="flex-1 min-w-32" id="row-note-<?= $date_str ?>">
        <input type="text" name="note[<?= $date_str ?>]"
               value="<?= h($cur_note) ?>"
               placeholder="メモ（任意）"
               class="input text-xs" style="padding:0.25rem 0.5rem;">
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <div class="text-center">
    <button type="submit"
            class="btn-primary px-8 py-3 rounded-xl text-sm font-bold">
      保存する
    </button>
  </div>
</form>

<script>
function toggleRow(cb, date) {
  const disabled = cb.checked;
  ['provider','pickup','note'].forEach(f => {
    const el = document.querySelector(`#row-${f}-${date}`);
    if (el) el.style.opacity = disabled ? '0.3' : '1';
    const input = el && el.querySelector('input, select');
    if (input) input.disabled = disabled;
  });
}
// 初期状態を反映
document.querySelectorAll('input[name^="no_school"]').forEach(cb => {
  if (cb.checked) toggleRow(cb, cb.name.match(/\[(.+)\]/)[1]);
});
</script>

<?php admin_foot(); ?>
