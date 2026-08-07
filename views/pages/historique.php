<?php
$db=$db??getDB(); $BP=BASE_PATH;
$uid=(int)$_SESSION['user_id']; $isAdm=estAdmin();
$page_n=max(1,(int)($_GET['p']??1)); $limit=ITEMS_PER_PAGE; $offset=($page_n-1)*$limit;
$fGrade=(int)($_GET['grade_id']??0); $fAgent=(int)($_GET['agent_id']??0);
$fNomEmp=trim($_GET['nom_employe']??'');
$fDateDe=$_GET['date_de']??''; $fDateA=$_GET['date_a']??'';
$fNetMin=isset($_GET['net_min'])&&$_GET['net_min']!==''?(float)$_GET['net_min']:null;
$fNetMax=isset($_GET['net_max'])&&$_GET['net_max']!==''?(float)$_GET['net_max']:null;

$where=$isAdm?[]:['s.user_id = :uid']; $params=$isAdm?[]:[':uid'=>$uid];
if($fGrade>0){$where[]='s.grade_id=:gid';$params[':gid']=$fGrade;}
if($fAgent>0&&$isAdm){$where[]='s.user_id=:aid';$params[':aid']=$fAgent;}
if($fNomEmp!==''){$where[]='(s.nom_employe LIKE :nmp OR u.nom LIKE :nmp2)';$params[':nmp']='%'.$fNomEmp.'%';$params[':nmp2']='%'.$fNomEmp.'%';}
if($fDateDe){$where[]='DATE(s.date_simulation)>=:dde';$params[':dde']=$fDateDe;}
if($fDateA){$where[]='DATE(s.date_simulation)<=:da';$params[':da']=$fDateA;}
if($fNetMin!==null){$where[]='s.net_a_payer>=:nmin';$params[':nmin']=$fNetMin;}
if($fNetMax!==null){$where[]='s.net_a_payer<=:nmax';$params[':nmax']=$fNetMax;}
$wSQL=!empty($where)?'WHERE '.implode(' AND ',$where):'';

$stmtC=$db->prepare("SELECT COUNT(*) FROM simulations s JOIN users u ON s.user_id=u.id {$wSQL}");
$stmtC->execute($params); $total=(int)$stmtC->fetchColumn();
$totalPages=(int)ceil($total/$limit);

$stmtS=$db->prepare("SELECT s.id,s.echelon,s.situation_familiale,s.net_a_payer,s.taux_retenue,s.retenues_total,s.traitement_base,s.indemnite_base,s.niveau_alerte,s.date_simulation,s.nom_employe,g.libelle AS grade_libelle,g.echelle,u.nom AS agent_nom FROM simulations s JOIN grades g ON s.grade_id=g.id JOIN users u ON s.user_id=u.id {$wSQL} ORDER BY s.date_simulation DESC LIMIT :lim OFFSET :off");
$stmtS->execute(array_merge($params,[':lim'=>$limit,':off'=>$offset]));
$sims=$stmtS->fetchAll();

$grades=$db->query('SELECT id,libelle FROM grades ORDER BY libelle')->fetchAll();
$agents=$isAdm?$db->query('SELECT id,nom FROM users ORDER BY nom')->fetchAll():[];

$filterQuery=http_build_query(array_filter(['grade_id'=>$fGrade?:null,'agent_id'=>$fAgent?:null,'nom_employe'=>$fNomEmp,'date_de'=>$fDateDe,'date_a'=>$fDateA,'net_min'=>$_GET['net_min']??'','net_max'=>$_GET['net_max']??''],fn($v)=>$v!==null&&$v!==''));
?>
<style>
  .hist-filter-card {
    background:white; border:1px solid var(--rule); border-radius:var(--radius-xl);
    padding:18px 20px; margin-bottom:20px; box-shadow:var(--shadow-sm);
  }
  .hist-filter-title {
    font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.07em;
    color:var(--subtle); margin-bottom:14px; display:flex; align-items:center; gap:6px;
  }
  .hist-filter-grid {
    display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;
  }
  .hist-filter-group { display:flex; flex-direction:column; gap:4px; }
  .hist-filter-group .label { margin-bottom:0; white-space:nowrap; }
  .hist-filter-actions { display:flex; gap:8px; align-items:flex-end; margin-left:auto; }

  .hist-card { background:white; border:1px solid var(--rule); border-radius:var(--radius-xl); box-shadow:var(--shadow-sm); overflow:hidden; }
  .hist-card-header {
    padding:14px 20px; border-bottom:1px solid var(--rule);
    display:flex; justify-content:space-between; align-items:center;
    background:var(--surface2);
  }
  .hist-card-title { font-size:13.5px; font-weight:700; color:var(--ink); }
  .hist-card-count { font-size:11px; color:var(--subtle); }

  /* Search highlight */
  .search-match { background:rgba(37,99,235,.1); border-radius:3px; padding:0 2px; color:var(--blue); font-weight:600; }

  /* Responsive table */
  .hist-table-wrap { overflow-x:auto; }
  .hist-table { width:100%; border-collapse:collapse; }
  .hist-table th {
    padding:10px 14px; font-size:10px; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color:var(--subtle); background:var(--surface2);
    border-bottom:1px solid var(--rule); text-align:left; white-space:nowrap;
  }
  .hist-table td {
    padding:11px 14px; font-size:13px; border-bottom:1px solid var(--surface);
    color:var(--ink-soft); vertical-align:middle;
  }
  .hist-table tr:last-child td { border-bottom:none; }
  .hist-table tr:hover td { background:rgba(37,99,235,.02); }
  .cell-date { font-size:12px; }
  .cell-date-time { font-size:10.5px; color:var(--subtle); display:block; }
  .cell-grade-name { font-size:12.5px; font-weight:600; color:var(--ink); }
  .cell-grade-sub { font-size:11px; color:var(--subtle); }
  .cell-employe { font-size:13px; font-weight:600; color:var(--ink); display:flex; align-items:center; gap:6px; }
  .cell-employe-empty { font-size:12px; font-style:italic; color:var(--subtle); display:flex; align-items:center; gap:6px; }
  .cell-net { font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:700; color:var(--success); text-align:right; }
  .cell-brut { font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--muted); text-align:right; }
  .cell-ret { font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--danger); text-align:right; }
  .cell-badge { text-align:center; }
  .cell-action { text-align:center; }
  .badge-s { background:var(--success-bg); color:var(--success); padding:3px 9px; border-radius:100px; font-size:10.5px; font-weight:700; white-space:nowrap; }
  .badge-w { background:var(--warning-bg); color:var(--warning); padding:3px 9px; border-radius:100px; font-size:10.5px; font-weight:700; white-space:nowrap; }
  .badge-d { background:var(--danger-bg);  color:var(--danger);  padding:3px 9px; border-radius:100px; font-size:10.5px; font-weight:700; white-space:nowrap; }
  .btn-voir {
    display:inline-flex; align-items:center; gap:4px;
    font-size:12px; font-weight:600; color:var(--blue);
    background:rgba(37,99,235,.06); border:1px solid rgba(37,99,235,.15);
    border-radius:6px; padding:5px 12px; transition:all .15s; white-space:nowrap;
  }
  .btn-voir:hover { background:rgba(37,99,235,.12); }
  .btn-print-sim {
    display:inline-flex; align-items:center; gap:4px;
    font-size:12px; font-weight:600; color:var(--muted);
    background:var(--surface); border:1px solid var(--rule);
    border-radius:6px; padding:5px 10px; transition:all .15s;
    cursor:pointer; text-decoration:none;
  }
  .btn-print-sim:hover { border-color:var(--blue); color:var(--blue); }
  .cell-actions-wrap { display:flex; gap:6px; justify-content:center; }
  @media(max-width:640px){.hist-filter-grid{flex-direction:column}.hist-filter-actions{margin-left:0}}
</style>

<!-- FILTER CARD -->
<div class="hist-filter-card no-print">
  <div class="hist-filter-title">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
    Filtres
  </div>
  <form method="GET" action="<?=$BP?>/index.php">
    <input type="hidden" name="page" value="historique">
    <div class="hist-filter-grid">
      <div class="hist-filter-group">
        <label class="label">Grade</label>
        <select name="grade_id" class="input" style="width:180px">
          <option value="0">Tous les grades</option>
          <?php foreach($grades as $g):?><option value="<?=$g['id']?>" <?=$fGrade==$g['id']?'selected':''?>><?=htmlspecialchars($g['libelle'])?></option><?php endforeach;?>
        </select>
      </div>

      <!-- Recherche par nom d'employé -->
      <div class="hist-filter-group">
        <label class="label">Nom employé</label>
        <div style="position:relative">
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#9CA3AF" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
          <input type="text" name="nom_employe" class="input" value="<?=htmlspecialchars($fNomEmp)?>" placeholder="Rechercher un employé..." style="padding-left:32px;width:200px">
        </div>
      </div>

      <?php if($isAdm):?>
      <div class="hist-filter-group">
        <label class="label">Agent</label>
        <select name="agent_id" class="input" style="width:150px">
          <option value="0">Tous</option>
          <?php foreach($agents as $a):?><option value="<?=$a['id']?>" <?=$fAgent==$a['id']?'selected':''?>><?=htmlspecialchars($a['nom'])?></option><?php endforeach;?>
        </select>
      </div>
      <?php endif;?>

      <div class="hist-filter-group">
        <label class="label">Du</label>
        <input type="date" name="date_de" class="input" value="<?=htmlspecialchars($fDateDe)?>" style="width:140px">
      </div>
      <div class="hist-filter-group">
        <label class="label">Au</label>
        <input type="date" name="date_a" class="input" value="<?=htmlspecialchars($fDateA)?>" style="width:140px">
      </div>

      <div class="hist-filter-actions">
        <button type="submit" class="btn btn-primary">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
          Filtrer
        </button>
        <a href="<?=$BP?>/index.php?page=historique" class="btn btn-secondary">Réinitialiser</a>
        <!-- Bouton Export Excel (liste filtrée) -->
        <a href="<?=$BP?>/index.php?page=export_excel&<?=$filterQuery?>" class="btn btn-secondary" style="color:#15803D;border-color:#BBF7D0;" title="Télécharger la liste en Excel">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
          Télécharger Excel
        </a>
      </div>
    </div>
  </form>
</div>

<!-- RESULTS CARD -->
<div class="hist-card">
  <div class="hist-card-header">
    <div>
      <span class="hist-card-title">Simulations</span>
      <span class="hist-card-count" style="margin-left:8px"><?=$total?> résultat<?=$total>1?'s':''?></span>
      <?php if($fNomEmp!==''):?>
      <span style="margin-left:8px;font-size:11px;color:var(--blue);background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.15);padding:2px 8px;border-radius:100px;font-weight:600;">
        Recherche : "<?=htmlspecialchars($fNomEmp)?>"
      </span>
      <?php endif;?>
    </div>
    <span style="font-size:11.5px;color:var(--subtle);">Page <?=$page_n?>/<?=max(1,$totalPages)?></span>
  </div>

  <?php if(empty($sims)):?>
    <div style="padding:48px 20px;text-align:center;color:var(--subtle);">
      <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;stroke:var(--rule)"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <p style="font-size:13.5px">Aucune simulation trouvée.</p>
    </div>
  <?php else:?>
  <div class="hist-table-wrap">
    <table class="hist-table">
      <thead><tr>
        <th>Date</th>
        <?php if($isAdm):?><th>Agent</th><?php endif;?>
        <th>Employé</th>
        <th>Grade / Échelon</th>
        <th>Situation</th>
        <th style="text-align:right">Brut</th>
        <th style="text-align:right">Retenues</th>
        <th style="text-align:right">Net à Payer</th>
        <th style="text-align:center">Taux</th>
        <th style="text-align:center" class="no-print">Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($sims as $s):
          $brut=(float)$s['traitement_base']+(float)$s['indemnite_base'];
          $taux=(float)$s['taux_retenue'];
          $nomEmpDisplay = !empty($s['nom_employe']) ? $s['nom_employe'] : '—';
          $badgeCls = $s['niveau_alerte']==='success'?'s':($s['niveau_alerte']==='warning'?'w':'d');
          // Highlight search match
          $nomHL = $nomEmpDisplay;
          if($fNomEmp!==''&&$nomEmpDisplay!=='—'){
            $nomHL = preg_replace('/('.preg_quote(htmlspecialchars($fNomEmp),'/').')/i','<mark class="search-match">$1</mark>',htmlspecialchars($nomEmpDisplay));
          } else {
            $nomHL = htmlspecialchars($nomEmpDisplay);
          }
        ?>
        <tr>
          <td class="cell-date">
            <?=date('d/m/Y',strtotime($s['date_simulation']))?>
            <span class="cell-date-time"><?=date('H:i',strtotime($s['date_simulation']))?></span>
          </td>
          <?php if($isAdm):?>
          <td><span style="font-size:12.5px;font-weight:600"><?=htmlspecialchars($s['agent_nom'])?></span></td>
          <?php endif;?>
          <td>
            <?php if(!empty($s['nom_employe'])): ?>
            <div class="cell-employe">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;stroke:var(--blue)"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <?=$nomHL?>
            </div>
            <?php else: ?>
            <div class="cell-employe-empty">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              Non renseigné
            </div>
            <?php endif; ?>
          </td>
          <td>
            <div class="cell-grade-name"><?=htmlspecialchars($s['grade_libelle'])?></div>
            <div class="cell-grade-sub">Éch. <?=$s['echelle']?> — Échelon <?=$s['echelon']?></div>
          </td>
          <td style="font-size:12.5px;color:var(--muted)"><?=libelleSituation($s['situation_familiale'])?></td>
          <td class="cell-brut"><?=number_format($brut,0,',',' ')?></td>
          <td class="cell-ret">−<?=number_format((float)$s['retenues_total'],0,',',' ')?></td>
          <td class="cell-net"><?=number_format((float)$s['net_a_payer'],2,',',' ')?></td>
          <td class="cell-badge"><span class="badge-<?=$badgeCls?>"><?=number_format($taux,1)?>%</span></td>
          <td class="cell-action no-print">
            <div class="cell-actions-wrap">
              <a href="<?=$BP?>/index.php?page=resultat&id=<?=$s['id']?>" class="btn-voir">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Voir
              </a>
              <a href="<?=$BP?>/index.php?page=export_pdf&id=<?=$s['id']?>" class="btn-print-sim" title="Télécharger le bulletin PDF">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <?php if($totalPages>1):
    $base=$BP.'/index.php?page=historique&'.$filterQuery.'&p=';?>
  <div style="padding:12px 20px;border-top:1px solid var(--rule);display:flex;justify-content:center">
    <div class="pagination flex gap-1">
      <?php
      if($page_n>1) echo '<a href="'.$base.($page_n-1).'">&laquo;</a>';
      for($i=max(1,$page_n-2);$i<=min($totalPages,$page_n+2);$i++)
        echo $i==$page_n?'<span class="current">'.$i.'</span>':'<a href="'.$base.$i.'">'.$i.'</a>';
      if($page_n<$totalPages) echo '<a href="'.$base.($page_n+1).'">&raquo;</a>';
      ?>
    </div>
  </div>
  <?php endif;?>
  <?php endif;?>
</div>