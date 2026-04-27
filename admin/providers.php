<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = get_pdo();

// POST: 事業者の保存（新規・更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_provider'])) {
    csrf_verify();
    $id           = (int)($_POST['id'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $color_key    = trim($_POST['color_key'] ?? 'ef');
    $phone        = trim($_POST['phone'] ?? '') ?: null;
    $address      = trim($_POST['address'] ?? '') ?: null;
    $service_hours = trim($_POST['service_hours'] ?? '') ?: null;
    $sort_order   = (int)($_POST['sort_order'] ?? 0);

    if ($id > 0) {
        $pdo->prepare("UPDATE providers SET name=?, color_key=?, phone=?, address=?, service_hours=?, sort_order=? WHERE id=?")
            ->execute([$name, $color_key, $phone, $address, $service_hours, $sort_order, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO providers (name, color_key, phone, address, service_hours, sort_order) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $color_key, $phone, $address, $service_hours, $sort_order]);
        $id = (int)$pdo->lastInsertId();
    }

    // 曜日ルールを一旦削除して再登録
    $pdo->prepare("DELETE FROM provider_day_rules WHERE provider_id = ?")->execute([$id]);
    $ins = $pdo->prepare("INSERT INTO provider_day_rules (provider_id, day_of_week, pickup_available, early_leave, early_leave_time) VALUES (?,?,?,?,?)");
    foreach ([1,2,3,4,5] as $dow) {
        if (isset($_POST["dow_{$dow}"])) {
            $pickup   = isset($_POST["pickup_{$dow}"]) ? 1 : 0;
            $early    = isset($_POST["early_{$dow}"]) ? 1 : 0;
            $etime    = ($early && !empty($_POST["early_time_{$dow}"])) ? $_POST["early_time_{$dow}"] : null;
            $ins->execute([$id, $dow, $pickup, $early, $etime]);
        }
    }
    header('Location: providers.php?saved=1');
    exit;
}

// POST: 削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_provider'])) {
    csrf_verify();
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE providers SET active = 0 WHERE id = ?")->execute([$id]);
    header('Location: providers.php');
    exit;
}

$providers = $pdo->query("SELECT * FROM providers WHERE active = 1 ORDER BY sort_order")->fetchAll();
$pdr       = fetch_provider_day_rules($pdo);

// 編集対象
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_p  = null;
$edit_pdr = [];
if ($edit_id) {
    $edit_p   = $pdo->prepare("SELECT * FROM providers WHERE id = ?")->execute([$edit_id]) ? $pdo->query("SELECT * FROM providers WHERE id = {$edit_id}")->fetch() : null;
    $edit_pdr = $pdr[$edit_id] ?? [];
}

admin_head('事業者マスタ');
admin_nav('providers');
?>

<div class="flex items-center justify-between mb-4">
  <h2 class="text-base font-bold text-gray-700">事業者マスタ</h2>
  <a href="?new=1" class="btn-primary text-xs px-4 py-2 rounded-xl font-bold">＋ 追加</a>
</div>

<?php if (isset($_GET['saved'])): ?>
  <div class="mb-4 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-3">✓ 保存しました</div>
<?php endif; ?>

<!-- 事業者一覧 -->
<div class="space-y-3 mb-6">
  <?php foreach ($providers as $p):
    $style  = provider_style($p['color_key']);
    $p_days = $pdr[(int)$p['id']] ?? [];
  ?>
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 px-4 py-3" style="border-left:4px solid <?= $style['dot'] ?>;">
      <div class="flex-1 min-w-0">
        <div class="font-bold text-sm text-gray-700"><?= h($p['name']) ?></div>
        <div class="text-xs text-gray-400 mt-0.5">
          利用日：<?= implode('・', array_map(fn($d) => dow_name($d), array_keys($p_days))) ?: 'なし' ?>
          <?php if ($p['phone']): ?> ／ <?= h($p['phone']) ?><?php endif; ?>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <a href="?edit=<?= $p['id'] ?>"
           class="text-xs px-3 py-1 rounded-xl bg-gray-100 text-gray-500 hover:bg-gray-200">編集</a>
        <form method="post" class="inline">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="delete_provider" value="1">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button type="submit" class="text-xs px-3 py-1 rounded-xl text-red-400 hover:text-red-600"
                  onclick="return confirm('削除しますか？')">削除</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- 追加・編集フォーム -->
<?php if (isset($_GET['new']) || $edit_id): ?>
<?php
$fp = $edit_p ?? [];
$fp_id = $edit_id ?? 0;
?>
<div class="bg-white rounded-2xl shadow-sm p-4">
  <h3 class="text-sm font-bold text-gray-600 mb-4"><?= $edit_id ? '事業者を編集' : '新しい事業者を追加' ?></h3>
  <form method="post" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="save_provider" value="1">
    <input type="hidden" name="id" value="<?= $fp_id ?>">

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-400 mb-1">事業者名 *</label>
        <input type="text" name="name" required value="<?= h($fp['name'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="block text-xs text-gray-400 mb-1">カラーキー</label>
        <select name="color_key" class="input">
          <?php foreach (['ef' => 'エフ（テラコッタ）', 'honey' => 'ウェルハニーバン（セージ）', 'arte' => 'あるてみす（スレートブルー）', 'other' => 'その他（グレー）'] as $ck => $cl): ?>
            <option value="<?= $ck ?>" <?= ($fp['color_key'] ?? '') === $ck ? 'selected' : '' ?>><?= $cl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-400 mb-1">電話番号</label>
        <input type="tel" name="phone" value="<?= h($fp['phone'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="block text-xs text-gray-400 mb-1">サービス提供時間</label>
        <input type="text" name="service_hours" value="<?= h($fp['service_hours'] ?? '') ?>"
               placeholder="例：9:00〜19:00" class="input">
      </div>
    </div>
    <div>
      <label class="block text-xs text-gray-400 mb-1">住所</label>
      <input type="text" name="address" value="<?= h($fp['address'] ?? '') ?>" class="input">
    </div>
    <div>
      <label class="block text-xs text-gray-400 mb-1">表示順</label>
      <input type="number" name="sort_order" value="<?= (int)($fp['sort_order'] ?? 0) ?>"
             class="input" style="width:80px;">
    </div>

    <!-- 曜日ルール -->
    <div>
      <label class="block text-xs text-gray-500 font-bold mb-2">利用曜日・送迎・早退設定</label>
      <div class="space-y-2">
        <?php foreach ([1=>'月',2=>'火',3=>'水',4=>'木',5=>'金'] as $dow => $dn):
          $r = $edit_pdr[$dow] ?? null;
        ?>
        <div class="flex flex-wrap items-center gap-3 py-2 border-b border-gray-50">
          <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 w-16">
            <input type="checkbox" name="dow_<?= $dow ?>"
                   <?= $r ? 'checked' : '' ?>
                   onchange="toggleDow(this, <?= $dow ?>)">
            <?= $dn ?>曜
          </label>
          <div id="dow-options-<?= $dow ?>" class="flex flex-wrap items-center gap-3 <?= $r ? '' : 'opacity-30 pointer-events-none' ?>">
            <label class="flex items-center gap-1 text-xs text-gray-500">
              <input type="checkbox" name="pickup_<?= $dow ?>" <?= ($r && $r['pickup_available']) ? 'checked' : '' ?>>
              送迎あり
            </label>
            <label class="flex items-center gap-1 text-xs text-gray-500">
              <input type="checkbox" name="early_<?= $dow ?>"
                     <?= ($r && $r['early_leave']) ? 'checked' : '' ?>
                     onchange="toggleEarlyTime(this, <?= $dow ?>)">
              小学校早退
            </label>
            <div id="early-time-<?= $dow ?>" class="<?= ($r && $r['early_leave']) ? '' : 'hidden' ?>">
              <label class="text-xs text-gray-400 mr-1">お迎え時間</label>
              <input type="time" name="early_time_<?= $dow ?>"
                     value="<?= h(format_time($r['early_leave_time'] ?? '')) ?>"
                     class="input" style="width:auto; padding:0.25rem 0.5rem; font-size:0.75rem;">
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-bold">保存</button>
      <a href="providers.php" class="px-6 py-2.5 rounded-xl text-sm text-gray-500 border border-gray-200 hover:bg-gray-50">キャンセル</a>
    </div>
  </form>
</div>
<script>
function toggleDow(cb, dow) {
  document.getElementById('dow-options-' + dow).classList.toggle('opacity-30', !cb.checked);
  document.getElementById('dow-options-' + dow).classList.toggle('pointer-events-none', !cb.checked);
}
function toggleEarlyTime(cb, dow) {
  document.getElementById('early-time-' + dow).classList.toggle('hidden', !cb.checked);
}
</script>
<?php endif; ?>

<?php admin_foot(); ?>
