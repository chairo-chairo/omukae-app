<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $pdo->prepare("
        INSERT INTO dismissal_rules (day_of_week, label, dismissal_time, pickup_start, pickup_end)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            label = VALUES(label),
            dismissal_time = VALUES(dismissal_time),
            pickup_start = VALUES(pickup_start),
            pickup_end = VALUES(pickup_end)
    ");
    foreach ([1,2,3,4,5] as $dow) {
        $label    = trim($_POST["label_{$dow}"] ?? '');
        $dismiss  = $_POST["dismissal_time_{$dow}"] ?? '';
        $ps       = $_POST["pickup_start_{$dow}"] ?? '';
        $pe       = $_POST["pickup_end_{$dow}"] ?? '';
        if ($label && $dismiss && $ps && $pe) {
            $stmt->execute([$dow, $label, $dismiss, $ps, $pe]);
        }
    }
    header('Location: rules.php?saved=1');
    exit;
}

$rules_by_dow = fetch_dismissal_rules($pdo);

admin_head('下校時間ルール');
admin_nav('rules');
?>

<h2 class="text-base font-bold text-gray-700 mb-4">下校時間ルール</h2>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
  <div class="space-y-3 mb-5">
    <?php foreach ([1=>'月',2=>'火',3=>'水',4=>'木',5=>'金'] as $dow => $day_name):
      $r = $rules_by_dow[$dow] ?? [];
    ?>
    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h3 class="text-sm font-bold text-gray-600 mb-3"><?= $day_name ?>曜日</h3>
      <div class="space-y-2">
        <div>
          <label class="block text-xs text-gray-400 mb-1">ラベル（複数曜日をまとめて表示）</label>
          <input type="text" name="label_<?= $dow ?>"
                 value="<?= h($r['label'] ?? '') ?>"
                 placeholder="例：月・火・木・金（5時間授業）"
                 class="input">
        </div>
        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-xs text-gray-400 mb-1">下校時間</label>
            <input type="time" name="dismissal_time_<?= $dow ?>"
                   value="<?= h(format_time($r['dismissal_time'] ?? '')) ?>"
                   class="input">
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">お迎え開始</label>
            <input type="time" name="pickup_start_<?= $dow ?>"
                   value="<?= h(format_time($r['pickup_start'] ?? '')) ?>"
                   class="input">
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">お迎え終了</label>
            <input type="time" name="pickup_end_<?= $dow ?>"
                   value="<?= h(format_time($r['pickup_end'] ?? '')) ?>"
                   class="input">
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center">
    <button type="submit" class="btn-primary px-8 py-3 rounded-xl text-sm font-bold">保存</button>
  </div>
</form>

<?php admin_foot(); ?>
