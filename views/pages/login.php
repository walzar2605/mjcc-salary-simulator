<?php
$BP   = BASE_PATH ?? '';
$LOGO = ($BP ?? '') . '/assets/img/logo_mjcc.png';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion — MJCC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --blue:#2563EB;--blue-dark:#1D4ED8;--cyan:#06B6D4;
      --ink:#111827;--ink-soft:#374151;--muted:#4B5563;--subtle:#9CA3AF;
      --rule:#E5E7EB;--surface:#F3F4F6;--surface2:#F9FAFB;
      --success:#16A34A;--success-bg:#DCFCE7;--success-bd:#86EFAC;
      --danger:#DC2626;--danger-bg:#FEE2E2;--danger-bd:#FCA5A5;
      --warning:#D97706;--warning-bg:#FEF3C7;--warning-bd:#FCD34D;
      --grad:linear-gradient(135deg,#2563EB 0%,#06B6D4 100%);
      --radius:8px;--radius-lg:12px;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{-webkit-font-smoothing:antialiased}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--surface);min-height:100vh;display:flex;align-items:stretch}

    /* ---- LAYOUT ---- */
    .login-wrap{display:flex;width:100%;min-height:100vh}

    /* LEFT PANEL */
    .login-left{
      display:none;flex:1;
      background:linear-gradient(135deg,#1e3a8a 0%,#0e7490 100%);
      padding:48px 56px;
      flex-direction:column;
      justify-content:space-between;
      position:relative;
      overflow:hidden;
    }
    @media(min-width:1024px){.login-left{display:flex}}
    .login-left::before{
      content:'';position:absolute;inset:0;
      background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      pointer-events:none;
    }
    .login-left::after{
      content:'';position:absolute;
      width:500px;height:500px;
      border-radius:50%;
      background:radial-gradient(circle,rgba(6,182,212,.15) 0%,transparent 70%);
      bottom:-100px;right:-100px;
      pointer-events:none;
    }

    .left-logo-row{display:flex;align-items:center;gap:12px;position:relative;z-index:1}
    .left-logo-icon{width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center}
    .left-logo-icon svg{width:22px;height:22px;stroke:white}
    .left-logo-name{color:white;font-size:16px;font-weight:800;letter-spacing:-.01em}
    .left-logo-sub{color:rgba(255,255,255,.5);font-size:11px;font-weight:400}

    .left-main{position:relative;z-index:1}
    .left-headline{font-size:42px;font-weight:800;color:white;line-height:1.15;letter-spacing:-.02em;margin-bottom:16px}
    .left-headline span{color:#67e8f9}
    .left-desc{font-size:14px;color:rgba(255,255,255,.6);line-height:1.7;max-width:380px;margin-bottom:36px}
    .left-features{display:flex;flex-direction:column;gap:12px}
    .left-feature{display:flex;align-items:center;gap:12px}
    .left-feature-icon{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .left-feature-icon svg{width:14px;height:14px;stroke:#67e8f9}
    .left-feature-text{font-size:13px;color:rgba(255,255,255,.7)}

    .left-footer{color:rgba(255,255,255,.25);font-size:11px;line-height:1.5;position:relative;z-index:1}

    /* RIGHT PANEL */
    .login-right{
      width:100%;max-width:480px;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;
      padding:40px 40px;
      background:white;
    }
    @media(min-width:1024px){.login-right{width:480px}}
    @media(max-width:1023px){.login-right{max-width:100%;background:var(--surface)}}

    .login-card{width:100%;max-width:380px}

    /* Header */
    .login-header{text-align:center;margin-bottom:32px}
    .login-logo-mark{width:48px;height:48px;border-radius:14px;background:var(--grad);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;box-shadow:0 8px 24px rgba(37,99,235,.28)}
    .login-logo-mark svg{width:26px;height:26px;stroke:white}
    .login-app-name{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.02em}
    .login-tagline{font-size:13px;color:var(--subtle);margin-top:4px}

    /* Tabs */
    .tab-nav{display:flex;background:var(--surface);border-radius:var(--radius);padding:3px;margin-bottom:24px;gap:3px}
    .tab-btn{flex:1;padding:8px;border:none;background:none;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border-radius:6px;transition:all .15s;font-family:'Plus Jakarta Sans',sans-serif}
    .tab-btn.active{background:white;color:var(--ink);box-shadow:0 1px 4px rgba(0,0,0,.1)}
    .tab-pane{display:none}
    .tab-pane.active{display:block}

    /* Fields */
    .f-group{margin-bottom:14px}
    .f-label{display:block;font-size:12px;font-weight:600;color:var(--ink-soft);margin-bottom:5px}
    .f-input{width:100%;padding:9px 12px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--ink);background:white;border:1.5px solid var(--rule);border-radius:var(--radius);outline:none;transition:border-color .15s,box-shadow .15s}
    .f-input::placeholder{color:var(--subtle)}
    .f-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.09)}

    /* Submit */
    .submit-btn{
      width:100%;padding:10px;
      background:var(--grad);
      color:white;
      font-size:14px;font-weight:700;
      font-family:'Plus Jakarta Sans',sans-serif;
      border:none;border-radius:var(--radius);
      cursor:pointer;transition:all .18s;
      margin-top:6px;
      box-shadow:0 4px 14px rgba(37,99,235,.28);
    }
    .submit-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.38);filter:brightness(1.05)}

    /* Flash */
    .flash-login{padding:10px 14px;border-radius:var(--radius);font-size:12.5px;font-weight:500;margin-bottom:16px;border:1.5px solid}
    .flash-danger{background:var(--danger-bg);color:var(--danger);border-color:var(--danger-bd)}
    .flash-success{background:var(--success-bg);color:var(--success);border-color:var(--success-bd)}
    .flash-warning{background:var(--warning-bg);color:var(--warning);border-color:var(--warning-bd)}

    .login-note{text-align:center;font-size:11px;color:var(--subtle);margin-top:20px;line-height:1.6}
    .login-note a{color:var(--blue);font-weight:500}

    @media(max-width:640px){
      .login-right{padding:28px 20px}
    }
  </style>
</head>
<body>
<div class="login-wrap">

  <!-- LEFT -->
  <div class="login-left">
    <div class="left-logo-row">
      <div class="left-logo-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      </div>
      <div>
        <div class="left-logo-name">MJCC</div>
        <div class="left-logo-sub">Simulation de Rémunération</div>
      </div>
    </div>

    <div class="left-main">
      <div class="left-headline">
        Simulez votre<br>
        <span>rémunération</span><br>
        en temps réel
      </div>
      <p class="left-desc">
        Outil officiel du Ministère de la Jeunesse, de la Culture et de la Communication pour la simulation précise des salaires de la fonction publique marocaine.
      </p>
      <div class="left-features">
        <div class="left-feature">
          <div class="left-feature-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
          <span class="left-feature-text">Calcul IR selon le barème marocain</span>
        </div>
        <div class="left-feature">
          <div class="left-feature-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
          <span class="left-feature-text">Simulation CMR, AMO et mutuelles</span>
        </div>
        <div class="left-feature">
          <div class="left-feature-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <span class="left-feature-text">Projection de carrière sur 12 échelons</span>
        </div>
        <div class="left-feature">
          <div class="left-feature-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></div>
          <span class="left-feature-text">Export bulletin PDF officiel</span>
        </div>
      </div>
    </div>

    <div class="left-footer">
      Accès sécurisé — Toute connexion est tracée et auditée<br>
      conformément à la politique de sécurité du Ministère
    </div>
  </div>

  <!-- RIGHT -->
  <div class="login-right">
    <div class="login-card">

      <div class="login-header">
        <div class="login-logo-mark">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div class="login-app-name">MJCC Simulator</div>
        <div class="login-tagline">Système de Simulation de Rémunération</div>
      </div>

      <?php if (!empty($flash)): ?>
      <div class="flash-login flash-<?= $flash['type']==='success'?'success':'danger' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
      <?php endif; ?>
      <?php if (($_GET['msg'] ?? '') === 'session_expiree'): ?>
      <div class="flash-login flash-warning">Session expirée. Veuillez vous reconnecter.</div>
      <?php endif; ?>

      <?php $autoTab = (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'register' : 'login'; ?>

      <div class="tab-nav">
        <button class="tab-btn <?= $autoTab==='login'?'active':'' ?>" onclick="switchTab('login')">Connexion</button>
        <button class="tab-btn <?= $autoTab==='register'?'active':'' ?>" onclick="switchTab('register')">Inscription</button>
      </div>

      <!-- LOGIN -->
      <div class="tab-pane <?= $autoTab==='login'?'active':'' ?>" id="tab-login">
        <form method="POST" action="<?= $BP ?>/index.php" novalidate>
          <?= csrfField() ?>
          <input type="hidden" name="_action" value="login">
          <div class="f-group">
            <label class="f-label">Adresse Email</label>
            <input type="email" name="email" class="f-input" placeholder="prenom.nom@mjcc.gov.ma" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="f-group">
            <label class="f-label">Mot de Passe</label>
            <input type="password" name="password" class="f-input" placeholder="••••••••" required>
          </div>
          <button type="submit" class="submit-btn">Se Connecter</button>
        </form>
      </div>

      <!-- REGISTER -->
      <div class="tab-pane <?= $autoTab==='register'?'active':'' ?>" id="tab-register">
        <?php
        $flashAcc = $_SESSION['flash_accueil'] ?? null;
        unset($_SESSION['flash_accueil']);
        if ($flashAcc): ?>
        <div class="flash-login flash-<?= $flashAcc['type']==='success'?'success':'danger' ?>">
          <?= htmlspecialchars($flashAcc['msg']) ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?= $BP ?>/accueil.php" novalidate>
          <?= csrfField() ?>
          <input type="hidden" name="_action" value="register">
          <div class="f-group">
            <label class="f-label">Nom Complet</label>
            <input type="text" name="nom" class="f-input" placeholder="Mohammed El Alami" required>
          </div>
          <div class="f-group">
            <label class="f-label">Adresse Email</label>
            <input type="email" name="email" class="f-input" placeholder="prenom.nom@mjcc.gov.ma" required>
          </div>
          <div class="f-group">
            <label class="f-label">Mot de Passe</label>
            <input type="password" name="password" class="f-input" placeholder="Min. 8 caractères" required>
          </div>
          <div class="f-group">
            <label class="f-label">Confirmer le Mot de Passe</label>
            <input type="password" name="password_confirm" class="f-input" placeholder="••••••••" required>
          </div>
          <button type="submit" class="submit-btn">Créer un Compte</button>
        </form>
      </div>

      <p class="login-note">
        Accès réservé aux agents habilités<br>
        Toute connexion est tracée et auditée
      </p>
    </div>
  </div>
</div>

<script>
function switchTab(t){
  document.querySelectorAll('.tab-btn').forEach((b,i)=>b.classList.toggle('active',(i===0&&t==='login')||(i===1&&t==='register')));
  document.getElementById('tab-login').classList.toggle('active',t==='login');
  document.getElementById('tab-register').classList.toggle('active',t==='register');
}
</script>
</body>
</html>
