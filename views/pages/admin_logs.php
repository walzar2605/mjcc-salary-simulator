<?php
exigerConnexion('admin');
$db = getDB(); $BP = BASE_PATH;
$page_n = max(1, (int)($_GET['p'] ?? 1));
$limit = ITEMS_PER_PAGE; $offset = ($page_n-1)*$limit;
$fAction = $_GET['action'] ?? '';
$fUser = (int)($_GET['user_id'] ?? 0);
$fDateDe = $_GET['date_de'] ?? '';
$fDateA  = $_GET['date_a']  ?? '';

$where = []; $params = [];
if ($fAction) { $where[] = 'l.action LIKE :act'; $params[':act'] = '%'.strtoupper($fAction).'%'; }
if ($fUser > 0) { $where[] = 'l.user_id = :uid'; $params[':uid'] = $fUser; }
if ($fDateDe) { $where[] = 'DATE(l.created_at) >= :dde'; $params[':dde'] = $fDateDe; }
if ($fDateA)  { $where[] = 'DATE(l.created_at) <= :da';  $params[':da']  = $fDateA; }
$wSQL = !empty($where) ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int)$db->prepare("SELECT COUNT(*) FROM logs l {$wSQL}")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM logs l {$wSQL}"): 0;
$stmtC = $db->prepare("SELECT COUNT(*) FROM logs l {$wSQL}"); $stmtC->execute($params); $total = (int)$stmtC->fetchColumn();
$totalPages = (int)ceil($total/$limit);

$stmtL = $db->prepare("SELECT l.*,u.nom AS user_nom FROM logs l LEFT JOIN users u ON l.user_id=u.id {$wSQL} ORDER BY l.created_at DESC LIMIT :lim OFFSET :off");
$stmtL->execute(array_merge($params,[':lim'=>$limit,':off'=>$offset]));
$logs = $stmtL->fetchAll();

$users = $db->query('SELECT id,nom FROM users ORDER BY nom')->fetchAll();
$filterQuery = http_build_query(array_filter(['action'=>$fAction,'user_id'=>$fUser?:null,'date_de'=>$fDateDe,'date_a'=>$fDateA],fn($v)=>$v!==null&&$v!==''));

$actionColor = fn($a) => match(true) {
    str_contains($a,'LOGIN')  => 'badge-navy',
    str_contains($a,'CREATE') => 'badge-success',
    str_contains($a,'LOGOUT') => 'badge-warning',
    str_contains($a,'DELETE') => 'badge-danger',
    default => 'badge-navy'
};
?>

<!-- Filter -->
<div class="card no-print" style="padding:18px 20px;margin-bottom:20px;">
  <form method="GET" action="<?=$BP?>/index.php" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
    <input type="hidden" name="page" value="admin_logs">
    <div><label class="label">Action</label><input type="text" name="action" class="input" value="<?=htmlspecialchars($fAction)?>" placeholder="LOGIN, CREATE..." style="width:150px;"></div>
    <div>
      <label class="label">Utilisateur</label>
      <select name="user_id" class="input" style="width:160px;"><option value="0">Tous</option>
        <?php foreach($users as $u):?><option value="<?=$u['id']?>" <?=$fUser==$u['id']?'selected':''?>><?=htmlspecialchars($u['nom'])?></option><?php endforeach;?>
      </select>
    </div>
    <div><label class="label">Du</label><input type="date" name="date_de" class="input" value="<?=htmlspecialchars($fDateDe)?>" style="width:140px;"></div>
    <div><label class="label">Au</label><input type="date" name="date_a" class="input" value="<?=htmlspecialchars($fDateA)?>" style="width:140px;"></div>
    <div style="display:flex;gap:8px;">
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="<?=$BP?>/index.php?page=admin_logs" class="btn btn-secondary">Réinitialiser</a>
    </div>
  </form>
</div>

<div class="card" style="overflow:hidden;">
  <div class="card-header">
    <div>
      <div class="card-title">Journal d'Audit</div>
      <div class="card-subtitle"><?=$total?> entrée<?=$total>1?'s':''?> — Page <?=$page_n?>/<?=max(1,$totalPages)?></div>
    </div>
  </div>
  <?php if(empty($logs)):?>
    <div style="padding:48px;text-align:center;color:var(--subtle);font-size:13px;">Aucun log trouvé.</div>
  <?php else:?>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Date &amp; Heure</th><th>Utilisateur</th><th>Action</th><th>Adresse IP</th><th>Détails</th>
      </tr></thead>
      <tbody>
        <?php foreach($logs as $l): ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--muted);white-space:nowrap;"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
          <td style="font-size:12.5px;font-weight:500;color:var(--blue);"><?= htmlspecialchars($l['user_nom'] ?? 'Système') ?></td>
          <td><span class="badge <?= $actionColor($l['action']) ?>"><?= htmlspecialchars($l['action']) ?></span></td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--muted);"><?= htmlspecialchars($l['ip'] ?? '—') ?></td>
          <td style="font-size:12px;color:var(--ink-soft);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?php if (!empty($l['details'])): $det = json_decode($l['details'],true); ?>
              <?= htmlspecialchars(is_array($det) ? implode(' | ', array_map(fn($k,$v)=>"$k: $v", array_keys($det), $det)) : $l['details']) ?>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if($totalPages>1):?>
  <div style="padding:14px 20px;border-top:1px solid var(--rule);display:flex;justify-content:flex-end;">
    <div class="pagination">
      <?php if($page_n>1):?><a href="?page=admin_logs&p=<?=$page_n-1?>&<?=$filterQuery?>">&lsaquo;</a><?php endif;?>
      <?php for($i=max(1,$page_n-2);$i<=min($totalPages,$page_n+2);$i++):?>
        <?php if($i===$page_n):?><span class="current"><?=$i?></span><?php else:?><a href="?page=admin_logs&p=<?=$i?>&<?=$filterQuery?>"><?=$i?></a><?php endif;?>
      <?php endfor;?>
      <?php if($page_n<$totalPages):?><a href="?page=admin_logs&p=<?=$page_n+1?>&<?=$filterQuery?>">&rsaquo;</a><?php endif;?>
    </div>
  </div>
  <?php endif;?>
  <?php endif;?>
</div>