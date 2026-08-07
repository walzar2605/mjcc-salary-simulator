<?php
$db  = getDB(); $uid = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT id,nom,email,role,actif,created_at FROM users WHERE id = :id');
$stmt->execute([':id'=>$uid]); $userInfo = $stmt->fetch();
$stmtSim = $db->prepare('SELECT COUNT(*) FROM simulations WHERE user_id = :id');
$stmtSim->execute([':id'=>$uid]); $nbSims = (int)$stmtSim->fetchColumn();
$BP = BASE_PATH;
$roleBadge = match($userInfo['role']) {
    'admin'  => '<span class="badge badge-admin">Administrateur</span>',
    'agent'  => '<span class="badge badge-agent">Agent</span>',
    'invite' => '<span class="badge badge-invite">Invité</span>',
    default  => '<span class="badge badge-navy">'.htmlspecialchars($userInfo['role']).'</span>',
};
?>
<div style="max-width:680px;">

  <!-- Identity card -->
  <div class="card" style="overflow:hidden;margin-bottom:20px;">
    <div style="background:var(--blue);padding:24px 28px;">
      <div style="display:flex;align-items:center;gap:18px;">
        <div style="width:58px;height:58px;border-radius:12px;background:rgba(184,151,58,.2);border:1px solid rgba(184,151,58,.3);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:600;color:var(--cyan-light);flex-shrink:0;">
          <?= mb_strtoupper(mb_substr($userInfo['nom'],0,1)) ?>
        </div>
        <div>
          <div style="font-size:22px;font-weight:600;color:#fff;"><?= htmlspecialchars($userInfo['nom']) ?></div>
          <div style="font-size:13px;color:rgba(255,255,255,.45);margin-top:3px;"><?= htmlspecialchars($userInfo['email']) ?></div>
          <div style="margin-top:8px;"><?= $roleBadge ?></div>
        </div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--rule);">
      <div style="padding:16px 20px;text-align:center;border-right:1px solid var(--rule);">
        <div style="font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;">Simulations</div>
        <div style="font-size:28px;font-weight:600;color:var(--blue);"><?= $nbSims ?></div>
      </div>
      <div style="padding:16px 20px;text-align:center;border-right:1px solid var(--rule);">
        <div style="font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;">Membre depuis</div>
        <div style="font-size:15px;font-weight:600;color:var(--blue);margin-top:6px;"><?= date('M Y', strtotime($userInfo['created_at'])) ?></div>
      </div>
      <div style="padding:16px 20px;text-align:center;">
        <div style="font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;">Statut</div>
        <div style="margin-top:6px;">
          <span class="badge <?= $userInfo['actif'] ? 'badge-success' : 'badge-danger' ?>"><?= $userInfo['actif'] ? 'Actif' : 'Inactif' ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Change password -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Modifier le Mot de Passe</div>
        <div class="card-subtitle">Laissez vide pour conserver votre mot de passe actuel</div>
      </div>
    </div>
    <div style="padding:24px;">
      <form method="POST" action="<?= $BP ?>/index.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="change_password">
        <div class="form-group">
          <label class="label">Mot de Passe Actuel</label>
          <input type="password" name="current_password" class="input" placeholder="••••••••" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="label">Nouveau Mot de Passe</label>
            <input type="password" name="new_password" class="input" placeholder="Min. 8 caractères" required minlength="8">
          </div>
          <div class="form-group">
            <label class="label">Confirmer</label>
            <input type="password" name="confirm_password" class="input" placeholder="••••••••" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à Jour</button>
      </form>
    </div>
  </div>

</div>
