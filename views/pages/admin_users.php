<?php
exigerConnexion('admin');
$db = getDB(); $BP = BASE_PATH;
$agents = $db->query("SELECT u.id,u.nom,u.email,u.role,u.actif,u.created_at,COUNT(s.id) AS nb_sims FROM users u LEFT JOIN simulations s ON s.user_id=u.id WHERE u.role IN ('admin','agent') GROUP BY u.id ORDER BY u.role ASC,u.nom ASC")->fetchAll();
$invites = $db->query("SELECT u.id,u.nom,u.role,u.actif,u.created_at,COUNT(s.id) AS nb_sims FROM users u LEFT JOIN simulations s ON s.user_id=u.id WHERE u.role='invite' GROUP BY u.id ORDER BY u.nom ASC")->fetchAll();
?>

<div style="max-width:1100px;display:flex;flex-direction:column;gap:22px;">

  <!-- Admins & Agents -->
  <div class="card" style="overflow:hidden;">
    <div class="card-header">
      <div>
        <div class="card-title">Admins &amp; Agents</div>
        <div class="card-subtitle"><?= count($agents) ?> compte(s) — Détails complets</div>
      </div>
      <button onclick="document.getElementById('modalCreate').classList.add('open')" class="btn btn-primary btn-sm">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
        Nouvel agent
      </button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Nom</th><th>Email</th><th style="text-align:center;">Rôle</th>
          <th style="text-align:center;">Simulations</th><th>Depuis</th>
          <th style="text-align:center;">Statut</th><th style="text-align:center;" class="no-print">Action</th>
        </tr></thead>
        <tbody>
          <?php foreach($agents as $a): ?>
          <tr>
            <td style="font-weight:500;color:var(--blue);">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(22,51,82,.1);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:var(--blue-dark);flex-shrink:0;"><?= mb_strtoupper(mb_substr($a['nom'],0,1)) ?></div>
                <?= htmlspecialchars($a['nom']) ?>
              </div>
            </td>
            <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($a['email']) ?></td>
            <td style="text-align:center;"><span class="badge <?= $a['role']==='admin'?'badge-gold':'badge-navy' ?>"><?= ucfirst($a['role']) ?></span></td>
            <td style="text-align:center;font-weight:600;color:var(--blue);"><?= $a['nb_sims'] ?></td>
            <td style="font-size:12px;color:var(--muted);"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
            <td style="text-align:center;"><span class="badge <?= $a['actif']?'badge-success':'badge-danger' ?>"><?= $a['actif']?'Actif':'Inactif' ?></span></td>
            <td style="text-align:center;" class="no-print">
              <?php if ($a['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="POST" action="<?=$BP?>/index.php" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="toggle_actif">
                <input type="hidden" name="user_id" value="<?=$a['id']?>">
                <input type="hidden" name="actif" value="<?=$a['actif']?0:1?>">
                <button type="submit" class="btn <?= $a['actif']?'btn-danger-btn':'btn-secondary' ?> btn-sm">
                  <?= $a['actif']?'Désactiver':'Activer' ?>
                </button>
              </form>
              <?php else: ?>
              <span style="font-size:11px;color:var(--subtle);">Vous</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Invités -->
  <?php if (!empty($invites)): ?>
  <div class="card" style="overflow:hidden;">
    <div class="card-header">
      <div>
        <div class="card-title">Comptes Invités</div>
        <div class="card-subtitle"><?= count($invites) ?> invité(s) inscrit(s)</div>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Nom</th><th style="text-align:center;">Simulations</th><th>Inscription</th>
          <th style="text-align:center;">Statut</th><th style="text-align:center;" class="no-print">Action</th>
        </tr></thead>
        <tbody>
          <?php foreach($invites as $a): ?>
          <tr>
            <td style="font-weight:500;color:var(--blue);"><?= htmlspecialchars($a['nom']) ?></td>
            <td style="text-align:center;font-weight:600;color:var(--blue);"><?= $a['nb_sims'] ?></td>
            <td style="font-size:12px;color:var(--muted);"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
            <td style="text-align:center;"><span class="badge <?= $a['actif']?'badge-success':'badge-danger' ?>"><?= $a['actif']?'Actif':'Inactif' ?></span></td>
            <td style="text-align:center;" class="no-print">
              <form method="POST" action="<?=$BP?>/index.php" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="toggle_actif">
                <input type="hidden" name="user_id" value="<?=$a['id']?>">
                <input type="hidden" name="actif" value="<?=$a['actif']?0:1?>">
                <button type="submit" class="btn <?= $a['actif']?'btn-danger-btn':'btn-secondary' ?> btn-sm"><?= $a['actif']?'Désactiver':'Activer' ?></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Create Modal -->
<div class="modal" id="modalCreate">
  <div class="modal-box">
    <div class="modal-title">Créer un Nouvel Agent</div>
    <form method="POST" action="<?=$BP?>/index.php">
      <?= csrfField() ?>
      <input type="hidden" name="_action" value="create_user">
      <div class="form-group">
        <label class="label">Nom complet</label>
        <input type="text" name="nom" class="input" required placeholder="Mohammed El Alami">
      </div>
      <div class="form-group">
        <label class="label">Email professionnel</label>
        <input type="email" name="email" class="input" required placeholder="m.elalami@mjcc.gov.ma">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="label">Rôle</label>
          <select name="role" class="input"><option value="agent">Agent</option><option value="admin">Admin</option></select>
        </div>
        <div class="form-group">
          <label class="label">Statut initial</label>
          <select name="actif" class="input"><option value="1">Actif</option><option value="0">Inactif</option></select>
        </div>
      </div>
      <div class="form-group">
        <label class="label">Mot de Passe</label>
        <input type="password" name="password" class="input" required minlength="8" placeholder="Min. 8 caractères">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:12px;border-top:1px solid var(--rule);margin-top:8px;">
        <button type="button" onclick="document.getElementById('modalCreate').classList.remove('open')" class="btn btn-secondary">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer l'Agent</button>
      </div>
    </form>
  </div>
</div>
