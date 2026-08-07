<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/calcul.php';

demarrerSession();
if (estConnecte()) { redirect('dashboard'); }

$BP     = BASE_PATH;
$LOGO   = $BP . '/assets/img/logo_mjcc.png';
$grades = getGradesCaches();

$simInvite = null;
$simErreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'simuler_invite') {
    $gradeId     = (int)($_POST['grade_id'] ?? 0);
    $echelon     = max(1, min(12, (int)($_POST['echelon'] ?? 1)));
    $situation   = $_POST['situation_familiale'] ?? 'celibataire';
    $nbEnfants   = max(0, min(6, (int)($_POST['nb_enfants'] ?? 0)));
    $mutuelleOrg = $_POST['mutuelle_org'] ?? 'aucune';
    if (!array_key_exists($mutuelleOrg, MUTUELLES)) $mutuelleOrg = 'aucune';
    try {
        $simInvite = simulerRemuneration($gradeId, $echelon, $situation, $nbEnfants, $mutuelleOrg);
        $_SESSION['sim_pending'] = $simInvite;
    } catch(Exception $e) { $simErreur = $e->getMessage(); }
}

$flashAcc = $_SESSION['flash_accueil'] ?? null;
unset($_SESSION['flash_accueil']);
$autoTab = (isset($_GET['tab']) && $_GET['tab']==='register') || $flashAcc ? 'inscription' : 'connexion';

$gradesByEchelle = [];
foreach ($grades as $g) $gradesByEchelle[$g['echelle']][] = $g;
$cats = [
    'Catégorie A — Échelle 11'=>['11'], 'Catégorie A — Échelle 10'=>['10'],
    'Catégorie B — Échelle 9'=>['9'],   'Catégorie B — Échelle 7'=>['7'],
    'Catégorie C — Échelle 5'=>['5'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Simulateur de Rémunération — MJCC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:       #2563EB;
      --blue-dark:  #1D4ED8;
      --blue-light: #3B82F6;
      --cyan:       #06B6D4;
      --white:      #FFFFFF;
      --ink:        #111827;
      --ink-soft:   #374151;
      --muted:      #4B5563;
      --subtle:     #9CA3AF;
      --rule:       #E5E7EB;
      --surface:    #F3F4F6;
      --surface2:   #F9FAFB;
      --success:    #16A34A; --success-bg:#DCFCE7; --success-bd:#86EFAC;
      --warning:    #D97706; --warning-bg:#FEF3C7; --warning-bd:#FCD34D;
      --danger:     #DC2626; --danger-bg:#FEE2E2;  --danger-bd:#FCA5A5;
      --grad:       linear-gradient(135deg,#2563EB 0%,#06B6D4 100%);
      --radius:     8px;
      --radius-lg:  12px;
      --radius-xl:  16px;
      --shadow-sm:  0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
      --shadow:     0 4px 6px -1px rgba(0,0,0,.08),0 2px 4px -1px rgba(0,0,0,.04);
      --shadow-lg:  0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);
      --shadow-xl:  0 20px 40px -8px rgba(0,0,0,.14);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{-webkit-font-smoothing:antialiased;scroll-behavior:smooth}
    body{font-family:'Plus Jakarta Sans',sans-serif;color:var(--ink);background:var(--white)}
    a{text-decoration:none;color:inherit}

    /* ======================== HEADER ======================== */
    .site-header {
      position: sticky; top: 0; z-index: 90;
      background: rgba(255,255,255,0.70);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(0,0,0,.07);
      box-shadow: 0 1px 12px rgba(0,0,0,.06);
    }
    .header-inner {
      max-width: 1200px; margin: 0 auto;
      padding: 0 28px; height: 90px;
      display: flex; align-items: center; gap: 14px;
    }
    .header-logo-mark {
      height: 72px; flex-shrink: 0;
      display:flex; align-items:center;
    }
    .header-logo-mark img {
      height: 72px; width: auto; object-fit: contain;
    }
    .header-brand{flex:1}
    .header-brand-name{font-size:15px;font-weight:800;color:var(--ink);letter-spacing:-.01em}
    .header-brand-sub{font-size:11px;color:var(--subtle);font-weight:400;margin-top:2px}
    .header-nav{display:flex;align-items:center;gap:8px}
    .btn-login{
      padding:7px 16px;border-radius:var(--radius);font-size:13px;font-weight:600;
      border:1.5px solid var(--rule);background:white;color:var(--ink-soft);
      cursor:pointer;transition:all .15s;font-family:'Plus Jakarta Sans',sans-serif;
      white-space:nowrap;
    }
    .btn-login:hover{border-color:var(--blue);color:var(--blue);background:rgba(37,99,235,.04)}
    .btn-signup{
      padding:7px 16px;border-radius:var(--radius);font-size:13px;font-weight:700;
      border:none;background:var(--grad);color:white;cursor:pointer;
      transition:all .18s;font-family:'Plus Jakarta Sans',sans-serif;
      box-shadow:0 4px 12px rgba(37,99,235,.25);white-space:nowrap;
    }
    .btn-signup:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(37,99,235,.35);filter:brightness(1.05)}
    @media(max-width:640px){
      .header-brand-sub{display:none}
      .header-inner{padding:0 16px}
    }

    /* ======================== HERO ======================== */
    .hero {
      background: linear-gradient(135deg,#EFF6FF 0%,#ECFEFF 60%,#F0F9FF 100%);
      padding: 88px 0 80px;
      position: relative; overflow: hidden;
    }
    .hero::before {
      content:''; position:absolute;
      width:600px; height:600px; border-radius:50%;
      background: radial-gradient(circle,rgba(37,99,235,.06) 0%,transparent 70%);
      top:-200px; right:-100px; pointer-events:none;
    }
    .hero::after {
      content:''; position:absolute;
      width:400px; height:400px; border-radius:50%;
      background: radial-gradient(circle,rgba(6,182,212,.07) 0%,transparent 70%);
      bottom:-100px; left:10%; pointer-events:none;
    }
    .hero-inner{max-width:1200px;margin:0 auto;padding:0 28px;position:relative;z-index:1}
    .hero-grid{display:grid;grid-template-columns:1fr 440px;gap:64px;align-items:center}

    .hero-eyebrow {
      display:inline-flex;align-items:center;gap:8px;
      background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.18);
      border-radius:100px;padding:5px 14px;
      font-size:11.5px;font-weight:700;color:var(--blue);
      letter-spacing:.06em;text-transform:uppercase;margin-bottom:20px;
    }
    .hero-eyebrow span{width:6px;height:6px;border-radius:50%;background:var(--blue);display:inline-block}
    .hero-h1{
      font-size:clamp(34px,4.5vw,58px);font-weight:800;
      color:var(--ink);line-height:1.1;letter-spacing:-.03em;
      margin-bottom:20px;
    }
    .hero-h1 .grad-text{
      background:var(--grad);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
    }
    .hero-desc{font-size:16px;color:var(--muted);line-height:1.75;margin-bottom:36px;max-width:500px}
    .hero-btns{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:44px}
    .btn-hero-primary{
      padding:13px 28px;border-radius:var(--radius-lg);font-size:15px;font-weight:700;
      background:var(--grad);color:white;border:none;cursor:pointer;transition:all .2s;
      font-family:'Plus Jakarta Sans',sans-serif;
      box-shadow:0 6px 20px rgba(37,99,235,.28);
      display:inline-flex;align-items:center;gap:8px;
    }
    .btn-hero-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(37,99,235,.38)}
    .btn-hero-primary svg{width:18px;height:18px;stroke:white}
    .btn-hero-secondary{
      padding:12px 24px;border-radius:var(--radius-lg);font-size:14px;font-weight:600;
      background:white;color:var(--ink-soft);border:1.5px solid var(--rule);cursor:pointer;
      transition:all .18s;font-family:'Plus Jakarta Sans',sans-serif;
      box-shadow:var(--shadow-sm);display:inline-flex;align-items:center;gap:8px;
    }
    .btn-hero-secondary:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-1px)}

    .hero-features{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .hero-feat{
      display:flex;align-items:center;gap:10px;
      background:white;border:1px solid var(--rule);border-radius:var(--radius);
      padding:12px 14px;box-shadow:var(--shadow-sm);transition:all .2s;
    }
    .hero-feat:hover{border-color:var(--blue);box-shadow:var(--shadow);transform:translateY(-1px)}
    .hero-feat-icon{
      width:32px;height:32px;border-radius:8px;background:var(--grad);
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .hero-feat-icon svg{width:16px;height:16px;stroke:white}
    .hero-feat-label{font-size:12.5px;font-weight:600;color:var(--ink-soft)}

    /* Stats grid (right side) */
    .hero-stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .hero-stat{
      background:white;border:1px solid var(--rule);border-radius:var(--radius-lg);
      padding:22px 20px;box-shadow:var(--shadow-sm);text-align:center;
      transition:all .2s;position:relative;overflow:hidden;
    }
    .hero-stat::before{
      content:'';position:absolute;top:0;left:0;right:0;height:3px;
      background:var(--grad);opacity:0;transition:opacity .2s;
    }
    .hero-stat:hover{box-shadow:var(--shadow);transform:translateY(-3px)}
    .hero-stat:hover::before{opacity:1}
    .hero-stat-value{
      font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:700;
      background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;
      background-clip:text;line-height:1;margin-bottom:6px;
    }
    .hero-stat-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--subtle)}

    /* ======================== SIMULATEUR SECTION ======================== */
    .sim-section{padding:72px 0 80px;background:var(--surface)}
    .sim-inner{max-width:1200px;margin:0 auto;padding:0 28px}
    .sec-head{text-align:center;margin-bottom:48px}
    .sec-tag{
      display:inline-block;background:rgba(37,99,235,.08);
      border:1px solid rgba(37,99,235,.18);border-radius:100px;
      padding:4px 16px;font-size:11px;font-weight:700;color:var(--blue);
      letter-spacing:.08em;text-transform:uppercase;margin-bottom:14px;
    }
    .sec-h2{font-size:clamp(22px,3vw,34px);font-weight:800;color:var(--ink);letter-spacing:-.02em;margin-bottom:8px}
    .sec-sub{font-size:14px;color:var(--subtle);line-height:1.6}

    .sim-layout{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start}

    /* Form card */
    .form-card{
      background:white;border:1px solid var(--rule);border-radius:var(--radius-xl);
      box-shadow:var(--shadow-lg);overflow:hidden;
    }
    .form-card-header{
      padding:20px 24px 18px;border-bottom:1px solid var(--rule);
      display:flex;align-items:center;gap:12px;
      background:linear-gradient(135deg,rgba(37,99,235,.04) 0%,rgba(6,182,212,.04) 100%);
    }
    .form-card-icon{
      width:40px;height:40px;border-radius:10px;background:var(--grad);
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
      box-shadow:0 4px 12px rgba(37,99,235,.28);
    }
    .form-card-icon svg{width:20px;height:20px;stroke:white}
    .form-card-title{font-size:15px;font-weight:700;color:var(--ink);letter-spacing:-.01em}
    .form-card-sub{font-size:11.5px;color:var(--subtle);margin-top:2px}
    .form-card-body{padding:24px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .f-group{margin-bottom:16px}
    .f-label{display:block;font-size:12px;font-weight:600;color:var(--ink-soft);margin-bottom:5px;letter-spacing:-.01em}
    .f-input{
      width:100%;padding:9px 12px;font-size:13.5px;font-family:'Plus Jakarta Sans',sans-serif;
      color:var(--ink);background:white;border:1.5px solid var(--rule);border-radius:var(--radius);
      outline:none;transition:border-color .15s,box-shadow .15s;appearance:none;
    }
    .f-input::placeholder{color:var(--subtle)}
    .f-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.09)}
    .f-input:hover:not(:focus){border-color:#D1D5DB}
    select.f-input{
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' fill='none' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer;
    }
    .range-row{display:flex;align-items:center;gap:12px;margin-top:4px}
    .range-slider{
      flex:1;height:5px;border-radius:3px;cursor:pointer;-webkit-appearance:none;
      background:linear-gradient(90deg,var(--blue),var(--cyan));
    }
    .range-slider::-webkit-slider-thumb{
      -webkit-appearance:none;width:18px;height:18px;border-radius:50%;
      background:white;border:3px solid var(--blue);box-shadow:0 1px 4px rgba(0,0,0,.18);
      cursor:pointer;transition:transform .15s;
    }
    .range-slider::-webkit-slider-thumb:hover{transform:scale(1.2)}
    .range-value{
      min-width:38px;height:36px;display:flex;align-items:center;justify-content:center;
      background:var(--grad);color:white;border-radius:var(--radius);
      font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;flex-shrink:0;
      box-shadow:0 2px 8px rgba(37,99,235,.28);
    }
    .range-hint{font-size:11px;color:var(--subtle);margin-top:4px}
    .btn-calc{
      width:100%;padding:13px;border-radius:var(--radius-lg);
      background:var(--grad);color:white;font-size:14px;font-weight:700;
      border:none;cursor:pointer;transition:all .2s;
      font-family:'Plus Jakarta Sans',sans-serif;
      box-shadow:0 4px 16px rgba(37,99,235,.28);
      display:flex;align-items:center;justify-content:center;gap:8px;
    }
    .btn-calc:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.38)}
    .btn-calc svg{width:18px;height:18px;stroke:white}

    /* Preview card */
    .preview-card{
      background:white;border:1px solid var(--rule);border-radius:var(--radius-xl);
      box-shadow:var(--shadow);overflow:hidden;position:sticky;top:80px;
    }
    .preview-header{
      padding:14px 18px;
      background:linear-gradient(135deg,rgba(37,99,235,.06) 0%,rgba(6,182,212,.06) 100%);
      border-bottom:1px solid var(--rule);
      display:flex;align-items:center;gap:8px;
    }
    .preview-dot{width:8px;height:8px;border-radius:50%;background:var(--grad);display:block;box-shadow:0 0 8px rgba(37,99,235,.4)}
    .preview-title{font-size:13px;font-weight:700;color:var(--ink)}
    .preview-empty{padding:40px 20px;text-align:center;color:var(--subtle)}
    .preview-empty svg{width:40px;height:40px;stroke:var(--rule);margin:0 auto 12px;display:block}
    .preview-empty p{font-size:12.5px;line-height:1.5}
    .preview-rows{padding:14px 16px}
    .preview-row{
      display:flex;justify-content:space-between;align-items:center;
      padding:7px 0;border-bottom:1px solid var(--surface);
    }
    .preview-row:last-child{border:none}
    .pr-label{font-size:11.5px;color:var(--muted)}
    .pr-value{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:600;color:var(--ink)}
    .pr-value.highlight{background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:13px}
    .pr-value.negative{color:var(--danger)}
    .preview-net{
      margin:0 12px 14px;padding:16px;border-radius:var(--radius-lg);
      background:var(--grad);text-align:center;
      box-shadow:0 4px 16px rgba(37,99,235,.22);
    }
    .preview-net-label{font-size:9.5px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.1em}
    .preview-net-value{font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:700;color:white;margin-top:4px}
    .preview-net-note{font-size:10px;color:rgba(255,255,255,.45);margin-top:3px}

    /* ======================== RÉSULTAT ======================== */
    .result-section{padding:56px 0;background:var(--surface2)}
    .result-inner{max-width:800px;margin:0 auto;padding:0 28px}
    .bulletin{
      background:white;border:1px solid var(--rule);border-radius:var(--radius-xl);
      box-shadow:var(--shadow-xl);overflow:hidden;
    }
    .bul-top{
      background:linear-gradient(135deg,#1e3a8a 0%,#0e7490 100%);
      padding:24px 28px;
    }
    .bul-top-inner{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}
    .bul-org{color:rgba(255,255,255,.6);font-size:11px;line-height:1.5}
    .bul-org strong{color:white;font-weight:700;font-size:12.5px;display:block;margin-bottom:2px}
    .bul-date{font-size:11px;color:rgba(255,255,255,.4);text-align:right;white-space:nowrap}
    .bul-title{font-size:18px;font-weight:800;color:white;margin:16px 0 4px;letter-spacing:-.01em}
    .bul-subtitle{font-size:13px;color:rgba(255,255,255,.6);line-height:1.5}
    .bul-unsaved{
      display:inline-flex;align-items:center;gap:5px;
      background:rgba(217,119,6,.2);border:1px solid rgba(217,119,6,.4);
      border-radius:100px;padding:3px 10px;font-size:10.5px;font-weight:600;
      color:#FCD34D;margin-top:8px;
    }
    .bul-body{padding:24px 28px}
    .bul-table{width:100%;border-collapse:collapse;margin-bottom:20px}
    .bul-table th{
      padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.09em;
      text-transform:uppercase;color:var(--subtle);background:var(--surface2);
      border-bottom:1px solid var(--rule);text-align:left;
    }
    .bul-table td{padding:11px 14px;font-size:13px;border-bottom:1px solid var(--surface);color:var(--ink-soft)}
    .bul-table tr:last-child td{border-bottom:none}
    .bul-table .row-total td{background:rgba(37,99,235,.04);font-weight:700;color:var(--ink);border-top:2px solid rgba(37,99,235,.12)}
    .bul-table .row-deduct td{background:rgba(220,38,38,.03)}
    .bul-table .row-total-neg td{background:rgba(220,38,38,.05);font-weight:700;color:var(--danger);border-top:2px solid rgba(220,38,38,.15)}
    .td-right{text-align:right;font-family:'JetBrains Mono',monospace;font-size:12.5px}
    .td-center{text-align:center;color:var(--muted);font-size:12px}
    .td-neg{color:var(--danger)}

    .net-box{
      background:var(--grad);border-radius:var(--radius-lg);
      padding:24px;text-align:center;margin-bottom:20px;
      box-shadow:0 6px 20px rgba(37,99,235,.25);
    }
    .net-label{font-size:10.5px;font-weight:700;color:rgba(255,255,255,.55);letter-spacing:.1em;text-transform:uppercase;margin-bottom:6px}
    .net-value{font-family:'JetBrains Mono',monospace;font-size:38px;font-weight:700;color:white;line-height:1}
    .net-rate{font-size:12px;color:rgba(255,255,255,.45);margin-top:6px}

    .alert-box{padding:14px 18px;border-radius:var(--radius);margin-bottom:20px;font-size:13px;font-weight:500;border:1.5px solid}
    .alert-success{background:var(--success-bg);color:var(--success);border-color:var(--success-bd)}
    .alert-warning{background:var(--warning-bg);color:var(--warning);border-color:var(--warning-bd)}
    .alert-danger{background:var(--danger-bg);color:var(--danger);border-color:var(--danger-bd)}

    .cta-save{
      border:1.5px dashed var(--rule);border-radius:var(--radius-lg);
      padding:24px;text-align:center;background:var(--surface);
    }
    .cta-save h3{font-size:17px;font-weight:700;color:var(--ink);margin-bottom:6px;letter-spacing:-.01em}
    .cta-save p{font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6}
    .cta-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
    .btn-cta-primary{
      padding:10px 22px;border-radius:var(--radius);font-size:13px;font-weight:700;
      background:var(--grad);color:white;border:none;cursor:pointer;
      box-shadow:0 4px 12px rgba(37,99,235,.25);transition:all .18s;
      font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-cta-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(37,99,235,.35)}
    .btn-cta-outline{
      padding:9px 22px;border-radius:var(--radius);font-size:13px;font-weight:600;
      background:white;color:var(--ink-soft);border:1.5px solid var(--rule);cursor:pointer;
      transition:all .15s;font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-cta-outline:hover{border-color:var(--blue);color:var(--blue)}

    /* ======================== FOOTER ======================== */
    .site-footer{
      background: rgba(17,24,39,0.82);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      padding:48px 28px;
      border-top: 1px solid rgba(255,255,255,.06);
    }
    .footer-inner{
      max-width:1200px;margin:0 auto;
      display:grid;grid-template-columns:auto 1fr auto;
      align-items:center;gap:32px;
    }
    .footer-brand{display:flex;align-items:center;gap:12px}
    .footer-logo-mark{ display:flex;align-items:center; }
    .footer-logo-mark img{height:44px;width:auto;object-fit:contain;}
    .footer-brand-name{font-size:14px;font-weight:800;color:white;letter-spacing:-.01em}
    .footer-brand-sub{font-size:10.5px;color:rgba(255,255,255,.35)}
    .footer-center{text-align:center}
    .footer-ministry{font-size:12.5px;color:rgba(255,255,255,.5);line-height:1.6}
    .footer-copy{font-size:11px;color:rgba(255,255,255,.25);margin-top:4px}
    .footer-action .btn-footer-login{
      padding:9px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;
      background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.15);
      color:rgba(255,255,255,.7);cursor:pointer;transition:all .15s;
      font-family:'Plus Jakarta Sans',sans-serif;
      display:flex;align-items:center;gap:6px;
    }
    .footer-action .btn-footer-login:hover{background:rgba(255,255,255,.14);color:white;border-color:rgba(255,255,255,.3)}
    .footer-action .btn-footer-login svg{width:14px;height:14px;stroke:currentColor}

    /* ======================== MODAL ======================== */
    .modal-overlay{
      display:none;position:fixed;inset:0;background:rgba(17,24,39,.55);
      z-index:200;align-items:center;justify-content:center;padding:16px;
      backdrop-filter:blur(4px);
    }
    .modal-overlay.open{display:flex}
    .modal-box{
      background:white;border-radius:var(--radius-xl);width:100%;max-width:420px;
      max-height:92vh;overflow-y:auto;
      box-shadow:var(--shadow-xl);
      border:1px solid var(--rule);
      animation:modalIn .18s ease;
    }
    @keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .modal-head{
      padding:20px 22px 0;display:flex;align-items:center;justify-content:space-between;
    }
    .modal-logo-row{display:flex;align-items:center;gap:10px}
    .modal-logo-mark{ display:flex;align-items:center; }
    .modal-logo-mark img{height:38px;width:auto;object-fit:contain;}
    .modal-brand{font-size:14px;font-weight:700;color:var(--ink)}
    .modal-close{
      width:30px;height:30px;border-radius:7px;border:1.5px solid var(--rule);
      background:white;color:var(--muted);font-size:18px;cursor:pointer;
      display:flex;align-items:center;justify-content:center;transition:all .15s;
    }
    .modal-close:hover{background:var(--danger-bg);border-color:var(--danger-bd);color:var(--danger)}
    .modal-tabs{
      display:flex;background:var(--surface);border-radius:var(--radius);padding:3px;
      margin:16px 22px 0;gap:3px;
    }
    .modal-tab{
      flex:1;padding:8px;border:none;background:none;font-size:13px;font-weight:600;
      color:var(--muted);cursor:pointer;border-radius:6px;transition:all .15s;
      font-family:'Plus Jakarta Sans',sans-serif;
    }
    .modal-tab.active{background:white;color:var(--ink);box-shadow:0 1px 4px rgba(0,0,0,.1)}
    .modal-panel{padding:18px 22px 22px}
    .modal-panel.hidden{display:none}
    .m-group{margin-bottom:14px}
    .m-label{display:block;font-size:12px;font-weight:600;color:var(--ink-soft);margin-bottom:5px}
    .m-input{
      width:100%;padding:9px 12px;font-size:13.5px;font-family:'Plus Jakarta Sans',sans-serif;
      color:var(--ink);background:white;border:1.5px solid var(--rule);
      border-radius:var(--radius);outline:none;transition:border-color .15s,box-shadow .15s;
    }
    .m-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.09)}
    .pwd-wrap{position:relative}
    .pwd-eye{
      position:absolute;right:10px;top:50%;transform:translateY(-50%);
      border:none;background:none;color:var(--subtle);cursor:pointer;
      display:flex;align-items:center;padding:4px;
    }
    .pwd-eye svg{width:16px;height:16px;stroke:currentColor}
    .str-bar-wrap{margin-top:5px;height:4px;border-radius:2px;background:var(--rule);overflow:hidden}
    .str-bar{height:100%;border-radius:2px;transition:all .3s;width:0}
    .str-text{font-size:10.5px;color:var(--subtle);margin-top:3px}
    .btn-modal{
      width:100%;padding:11px;border-radius:var(--radius);font-size:14px;font-weight:700;
      border:none;cursor:pointer;transition:all .15s;margin-top:2px;
      font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-modal-primary{background:var(--grad);color:white;box-shadow:0 4px 12px rgba(37,99,235,.25)}
    .btn-modal-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(37,99,235,.35)}
    .btn-modal-success{background:linear-gradient(135deg,#16A34A,#15803D);color:white;box-shadow:0 4px 12px rgba(22,163,74,.25)}
    .btn-modal-success:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(22,163,74,.35)}
    .modal-footer-note{
      font-size:12px;text-align:center;color:var(--subtle);
      margin-top:14px;padding-top:14px;border-top:1px solid var(--rule);
    }
    .modal-footer-note button{
      color:var(--blue);background:none;border:none;cursor:pointer;
      font-weight:600;font-size:12px;font-family:'Plus Jakarta Sans',sans-serif;
    }
    .modal-notice{
      background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.15);
      border-radius:var(--radius);padding:10px 13px;font-size:12px;
      color:var(--blue);margin-bottom:14px;line-height:1.5;
    }
    .flash-modal{padding:9px 13px;border-radius:var(--radius);font-size:12.5px;margin-bottom:12px;border:1.5px solid}
    .flash-modal.danger{background:var(--danger-bg);color:var(--danger);border-color:var(--danger-bd)}
    .flash-modal.success{background:var(--success-bg);color:var(--success);border-color:var(--success-bd)}

    /* ======================== RESPONSIVE ======================== */
    @media(max-width:1024px){
      .hero-grid{grid-template-columns:1fr;gap:40px}
      .hero-stats-grid{grid-template-columns:repeat(4,1fr)}
    }
    @media(max-width:768px){
      .hero{padding:60px 0 56px}
      .hero-features{grid-template-columns:1fr}
      .hero-stats-grid{grid-template-columns:1fr 1fr}
      .sim-layout{grid-template-columns:1fr}
      .preview-card{position:static}
      .footer-inner{grid-template-columns:1fr;text-align:center;gap:20px}
      .footer-brand{justify-content:center}
      .footer-action{display:flex;justify-content:center}
      .form-row{grid-template-columns:1fr}
    }
    @media(max-width:640px){
      .hero-inner,.sim-inner,.result-inner{padding:0 16px}
      .hero-h1{font-size:32px}
      .bul-top,.bul-body{padding:18px 18px}
      .site-footer{padding:36px 16px}
    }
    @media print{.no-print{display:none!important}.bulletin{box-shadow:none;border:1px solid #ddd}}
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header no-print">
  <div class="header-inner">
    <div class="header-logo-mark">
      <img src="<?= $LOGO ?>" alt="Logo MJCC">
    </div>
    <div class="header-brand">
      <div class="header-brand-name">Simulateur de Rémunération</div>
      <div class="header-brand-sub">Ministère de la Jeunesse, de la Culture et de la Communication</div>
    </div>
    <div class="header-nav">
      <button class="btn-login" onclick="ouvrirModal('connexion')">Se connecter</button>
      <button class="btn-signup" onclick="ouvrirModal('inscription')">Créer un compte</button>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero no-print">
  <div class="hero-inner">
    <div class="hero-grid">
      <div>
        <div class="hero-eyebrow">
          <span></span>
          Fonction Publique Marocaine
        </div>
        <h1 class="hero-h1">
          Simulez votre<br>
          <span class="grad-text">rémunération</span><br>
          en temps réel
        </h1>
        <p class="hero-desc">
          Estimez votre salaire net selon votre grade, échelon et situation familiale — basé sur les barèmes officiels de la Fonction Publique Marocaine.
        </p>
        <div class="hero-btns">
          <a href="#simulateur" class="btn-hero-primary">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round"/></svg>
            Simuler maintenant
          </a>
          <button onclick="ouvrirModal('inscription')" class="btn-hero-secondary">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Créer un compte
          </button>
        </div>
        <div class="hero-features">
          <div class="hero-feat">
            <div class="hero-feat-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg></div>
            <span class="hero-feat-label">Sauvegarde automatique</span>
          </div>
          <div class="hero-feat">
            <div class="hero-feat-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15" stroke-linecap="round"/></svg></div>
            <span class="hero-feat-label">Historique complet</span>
          </div>
          <div class="hero-feat">
            <div class="hero-feat-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
            <span class="hero-feat-label">Comparateur de grades</span>
          </div>
          <div class="hero-feat">
            <div class="hero-feat-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <span class="hero-feat-label">Projection de carrière</span>
          </div>
        </div>
      </div>

      <div>
        <div class="hero-stats-grid">
          <div class="hero-stat"><div class="hero-stat-value">10%</div><div class="hero-stat-label">CMR Retraite</div></div>
          <div class="hero-stat"><div class="hero-stat-value">2,5%</div><div class="hero-stat-label">AMO Santé</div></div>
          <div class="hero-stat"><div class="hero-stat-value">51,40</div><div class="hero-stat-label">MAD / Point</div></div>
          <div class="hero-stat"><div class="hero-stat-value">12</div><div class="hero-stat-label">Échelons</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SIMULATEUR -->
<section class="sim-section" id="simulateur">
  <div class="sim-inner">
    <div class="sec-head">
      <div class="sec-tag">Simulation rapide — sans inscription</div>
      <h2 class="sec-h2">Calculez votre rémunération nette</h2>
      <p class="sec-sub">Basé sur les indices officiels et les barèmes IR de la Fonction Publique Marocaine</p>
    </div>

    <?php if ($simErreur): ?>
    <div class="alert-box alert-danger" style="max-width:760px;margin:0 auto 24px;"><?= htmlspecialchars($simErreur) ?></div>
    <?php endif; ?>

    <div class="sim-layout">
      <!-- FORM -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round"/></svg>
          </div>
          <div>
            <div class="form-card-title">Paramètres de simulation</div>
            <div class="form-card-sub">Renseignez votre grade et votre situation</div>
          </div>
        </div>
        <div class="form-card-body">
          <form method="POST" action="<?= $BP ?>/accueil.php#resultat">
            <input type="hidden" name="_action" value="simuler_invite">

            <div class="f-group">
              <label class="f-label">Grade <span style="color:var(--danger)">*</span></label>
              <select id="grade_id" name="grade_id" class="f-input" required>
                <option value="">— Sélectionner un grade —</option>
                <?php foreach ($cats as $label => $echelles):
                  $hasG = false;
                  foreach ($echelles as $e) if (!empty($gradesByEchelle[$e])) { $hasG = true; break; }
                  if (!$hasG) continue; ?>
                <optgroup label="<?= $label ?>">
                  <?php foreach ($echelles as $e): foreach ($gradesByEchelle[$e] ?? [] as $g): ?>
                  <option value="<?= $g['id'] ?>"
                    data-min="<?= $g['indice_minimal'] ?>"
                    data-max="<?= $g['indice_maximal'] ?>"
                    data-indem="<?= $g['indemnite_base'] ?>"
                    <?= ($simInvite && (int)($simInvite['grade']['id'] ?? 0) === $g['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['libelle']) ?>
                  </option>
                  <?php endforeach; endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="f-group">
              <label class="f-label">Échelon <span style="color:var(--danger)">*</span></label>
              <div class="range-row">
                <input type="range" id="echelonRange" name="echelon" class="range-slider" min="1" max="12" value="<?= $simInvite['echelon'] ?? 1 ?>">
                <div class="range-value" id="echelonVal"><?= $simInvite['echelon'] ?? 1 ?></div>
              </div>
              <div class="range-hint" id="indiceInfo">Sélectionnez d'abord un grade</div>
            </div>

            <div class="form-row">
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Situation familiale</label>
                <select id="sit" name="situation_familiale" class="f-input">
                  <option value="celibataire"       <?= ($simInvite['situation_familiale'] ?? 'celibataire') === 'celibataire'      ? 'selected' : '' ?>>Célibataire</option>
                  <option value="marie_sans_enfant" <?= ($simInvite['situation_familiale'] ?? '') === 'marie_sans_enfant' ? 'selected' : '' ?>>Marié(e), sans enfant</option>
                  <option value="marie_1enfant"     <?= ($simInvite['situation_familiale'] ?? '') === 'marie_1enfant'     ? 'selected' : '' ?>>Marié(e) + 1 enfant</option>
                  <option value="marie_2enfants"    <?= ($simInvite['situation_familiale'] ?? '') === 'marie_2enfants'    ? 'selected' : '' ?>>Marié(e) + 2 enfants</option>
                  <option value="marie_3enfants"    <?= ($simInvite['situation_familiale'] ?? '') === 'marie_3enfants'    ? 'selected' : '' ?>>Marié(e) + 3 enfants</option>
                  <option value="marie_4enfants"    <?= ($simInvite['situation_familiale'] ?? '') === 'marie_4enfants'    ? 'selected' : '' ?>>Marié(e) + 4 enfants+</option>
                </select>
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Mutuelle</label>
                <select id="mutuelle_org" name="mutuelle_org" class="f-input">
                  <option value="aucune"  <?= ($simInvite['mutuelle_org'] ?? 'aucune') === 'aucune'  ? 'selected' : '' ?>>Aucune</option>
                  <option value="mgpap"   <?= ($simInvite['mutuelle_org'] ?? '') === 'mgpap'          ? 'selected' : '' ?>>MGPAP</option>
                  <option value="mgen"    <?= ($simInvite['mutuelle_org'] ?? '') === 'mgen'           ? 'selected' : '' ?>>MGEN</option>
                  <option value="douanes" <?= ($simInvite['mutuelle_org'] ?? '') === 'douanes'        ? 'selected' : '' ?>>DOUANES</option>
                  <option value="police"  <?= ($simInvite['mutuelle_org'] ?? '') === 'police'         ? 'selected' : '' ?>>POLICE</option>
                  <option value="faux"    <?= ($simInvite['mutuelle_org'] ?? '') === 'faux'           ? 'selected' : '' ?>>F.AUX</option>
                  <option value="omfam"   <?= ($simInvite['mutuelle_org'] ?? '') === 'omfam'          ? 'selected' : '' ?>>OMFAM</option>
                </select>
              </div>
            </div>

            <div style="margin-top:20px">
              <button type="submit" class="btn-calc">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Calculer ma rémunération
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- PREVIEW -->
      <div class="preview-card">
        <div class="preview-header">
          <span class="preview-dot"></span>
          <span class="preview-title">Aperçu instantané</span>
        </div>
        <div id="previewEmpty" class="preview-empty">
          <svg fill="none" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="1.5"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round" stroke-width="1.5"/></svg>
          <p>Sélectionnez un grade pour voir l'aperçu en temps réel</p>
        </div>
        <div id="previewContent" class="hidden">
          <div class="preview-rows">
            <div class="preview-row"><span class="pr-label">Indice brut</span><span class="pr-value highlight" id="pv_i">—</span></div>
            <div class="preview-row"><span class="pr-label">Traitement de base</span><span class="pr-value" id="pv_b">—</span></div>
            <div class="preview-row"><span class="pr-label">+ Indemnité (IRF)</span><span class="pr-value" id="pv_id">—</span></div>
            <div class="preview-row"><span class="pr-label">= Brut total</span><span class="pr-value highlight" id="pv_br">—</span></div>
            <div class="preview-row"><span class="pr-label">− CMR (10%)</span><span class="pr-value negative" id="pv_cmr">—</span></div>
            <div class="preview-row"><span class="pr-label" id="pv_ml">− AMO (2,5%)</span><span class="pr-value negative" id="pv_m">—</span></div>
          </div>
          <div class="preview-net">
            <div class="preview-net-label">Net avant IR</div>
            <div class="preview-net-value" id="pv_net">—</div>
            <div class="preview-net-note">L'IR est calculé à la soumission</div>
          </div>
        </div>
      </div>
    </div>

    <!-- RÉSULTAT -->
    <?php if ($simInvite):
      $alertClass = ['success'=>'alert-success','warning'=>'alert-warning','danger'=>'alert-danger'][$simInvite['niveau_alerte']] ?? 'alert-success';
      $mutL = $simInvite['mutuelle_org'] === 'aucune'
        ? 'AMO seule (2,5%)'
        : 'AMO + '.htmlspecialchars($simInvite['mutuelle_libelle']).' ('.number_format($simInvite['taux_mutuelle_total']*100,1).'%)';
    ?>
    <div id="resultat" style="padding-top:48px;">
      <div style="max-width:800px;margin:0 auto;">
        <div class="bulletin">
          <div class="bul-top">
            <div class="bul-top-inner">
              <div class="bul-org">
                <strong>Royaume du Maroc — المملكة المغربية</strong>
                Ministère de la Jeunesse, de la Culture et de la Communication
              </div>
              <div class="bul-date"><?= date('d/m/Y H:i') ?></div>
            </div>
            <div class="bul-title">Bulletin de Simulation de Rémunération</div>
            <div class="bul-subtitle">
              <?= htmlspecialchars($simInvite['grade']['libelle']) ?>
              — Échelle <?= $simInvite['grade']['echelle'] ?>
              — Échelon <?= $simInvite['echelon'] ?>
              — <?= libelleSituation($simInvite['situation_familiale']) ?>
            </div>
            <div class="bul-unsaved">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              Non sauvegardé — Créez un compte pour conserver
            </div>
          </div>

          <div class="bul-body">
            <table class="bul-table">
              <thead><tr><th>Désignation</th><th class="td-right">Montant (MAD)</th></tr></thead>
              <tbody>
                <tr>
                  <td>Traitement de Base <small style="color:var(--subtle)">(Indice <?= (int)$simInvite['indice_brut'] ?> × 51,40 MAD)</small></td>
                  <td class="td-right"><?= fmt($simInvite['traitement_base']) ?></td>
                </tr>
                <tr>
                  <td>Indemnité Représentative de Frais (IRF)</td>
                  <td class="td-right"><?= fmt($simInvite['indemnite_base']) ?></td>
                </tr>
                <tr class="row-total">
                  <td><strong>TOTAL BRUT</strong></td>
                  <td class="td-right"><strong><?= fmt($simInvite['brut_total']) ?></strong></td>
                </tr>
              </tbody>
            </table>

            <table class="bul-table">
              <thead><tr><th>Nature des retenues</th><th class="td-center">Taux</th><th class="td-right">Montant (MAD)</th></tr></thead>
              <tbody>
                <tr class="row-deduct">
                  <td>CMR — Caisse Marocaine des Retraites</td>
                  <td class="td-center">10%</td>
                  <td class="td-right td-neg">− <?= fmt($simInvite['retenue_cmr']) ?></td>
                </tr>
                <tr class="row-deduct">
                  <td><?= htmlspecialchars($mutL) ?></td>
                  <td class="td-center"><?= number_format($simInvite['taux_mutuelle_total']*100,1) ?>%</td>
                  <td class="td-right td-neg">− <?= fmt($simInvite['retenue_mutuelle']) ?></td>
                </tr>
                <tr class="row-deduct">
                  <td>Impôt sur le Revenu (Barème CGI)</td>
                  <td class="td-center">Variable</td>
                  <td class="td-right td-neg">− <?= fmt($simInvite['retenue_ir']) ?></td>
                </tr>
                <tr class="row-total-neg">
                  <td><strong>TOTAL RETENUES</strong></td>
                  <td class="td-center"><strong><?= number_format($simInvite['taux_retenue'],1) ?>%</strong></td>
                  <td class="td-right"><strong>− <?= fmt($simInvite['retenues_total']) ?></strong></td>
                </tr>
              </tbody>
            </table>

            <div class="net-box">
              <div class="net-label">Net à Payer</div>
              <div class="net-value"><?= fmt($simInvite['net_a_payer']) ?></div>
              <div class="net-rate">Taux de retenue global : <?= number_format($simInvite['taux_retenue'],2) ?>%</div>
            </div>

            <div class="alert-box <?= $alertClass ?>">
              <strong> Analyse &amp; Conseil</strong><br>
              <?= htmlspecialchars($simInvite['message_conseil']) ?>
            </div>

            <div class="cta-save">
              <h3> Sauvegarder cette simulation ?</h3>
              <p>Connectez-vous ou créez un compte gratuitement — la simulation sera sauvegardée automatiquement et vous pourrez accéder à l'historique complet.</p>
              <div class="cta-btns">
                <button onclick="ouvrirModal('inscription')" class="btn-cta-primary"> Créer un compte</button>
                <button onclick="ouvrirModal('connexion')" class="btn-cta-outline">Se connecter</button>
              </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--rule);margin-top:4px">
              <span style="font-size:11px;color:var(--subtle)">Document simulatif — Non contractuel</span>
              <span style="font-size:11px;color:var(--subtle)">© <?= date('Y') ?> MJCC</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ========== BANNER RÉGLEMENTAIRE ========== -->
<section style="background:#1e3a5f;padding:18px 28px;">
  <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <p style="font-size:13px;color:rgba(255,255,255,.85);flex:1;margin:0;line-height:1.6;">
      <strong style="color:#fff;">Avis réglementaire —</strong>
      Les calculs sont basés sur les barèmes officiels de la Fonction Publique Marocaine en vigueur : valeur du point indiciaire <strong style="color:#93c5fd;">51,40 MAD</strong>, taux CMR <strong style="color:#93c5fd;">10%</strong>, AMO <strong style="color:#93c5fd;">2,5%</strong> et barème IR DGI.
      Ces simulations ont une valeur indicative et ne constituent pas un bulletin de paie officiel.
    </p>
    <span style="font-size:11px;color:rgba(255,255,255,.35);white-space:nowrap;">© DGI / CMR Maroc</span>
  </div>
</section>

<!-- ========== COMMENT ÇA MARCHE ========== -->
<section style="padding:80px 28px;background:#fff;">
  <div style="max-width:1100px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:52px;">
      <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);border-radius:100px;padding:5px 14px;font-size:11.5px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--blue);display:inline-block;"></span> Guide rapide
      </div>
      <h2 style="font-size:30px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.02em;">Comment ça marche ?</h2>
      <p style="font-size:15px;color:var(--muted);max-width:480px;margin:0 auto;line-height:1.7;">Obtenez votre simulation de rémunération nette en 3 étapes simples.</p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:28px;">
      <?php
      $steps = [
        ['01','Choisissez votre grade','Sélectionnez votre grade et votre échelon parmi les 10 grades de la Fonction Publique Marocaine.','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','#2563EB'],
        ['02','Renseignez votre situation','Indiquez votre situation familiale et le nombre d\'enfants à charge pour le calcul des déductions IR.','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','#0891b2'],
        ['03','Obtenez votre net','Visualisez instantanément votre salaire net après déductions CMR, AMO, mutuelle et IR, avec conseils personnalisés.','M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M12 7h.01M9 7h.01M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z','#16a34a'],
      ];
      foreach($steps as $i=>[$num,$title,$desc,$icon,$color]):
      ?>
      <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 16px rgba(0,0,0,.06);padding:28px;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.10)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 16px rgba(0,0,0,.06)'">
        <div style="position:absolute;top:16px;right:20px;font-size:42px;font-weight:900;color:rgba(37,99,235,.05);font-family:'JetBrains Mono',monospace;line-height:1;"><?= $num ?></div>
        <div style="width:46px;height:46px;border-radius:12px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;margin-bottom:18px;box-shadow:0 4px 14px <?= $color ?>44;">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8"><path stroke-linecap="round" d="<?= $icon ?>"/></svg>
        </div>
        <div style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:8px;"><?= $title ?></div>
        <div style="font-size:13px;color:var(--muted);line-height:1.65;"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ========== TABLEAU DES GRADES ========== -->
<section style="padding:80px 28px;background:var(--surface);">
  <div style="max-width:1100px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:44px;">
      <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);border-radius:100px;padding:5px 14px;font-size:11.5px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--blue);display:inline-block;"></span> Grille indiciaire
      </div>
      <h2 style="font-size:30px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.02em;">Grades de la Fonction Publique</h2>
      <p style="font-size:15px;color:var(--muted);max-width:480px;margin:0 auto;line-height:1.7;">Les 10 grades avec leur indice minimal, maximal et indemnité de base.</p>
    </div>
    <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:var(--surface2);">
            <th style="padding:13px 18px;text-align:left;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Grade</th>
            <th style="padding:13px 18px;text-align:center;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Échelle</th>
            <th style="padding:13px 18px;text-align:right;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Indice min</th>
            <th style="padding:13px 18px;text-align:right;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Indice max</th>
            <th style="padding:13px 18px;text-align:right;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Indemnité base</th>
            <th style="padding:13px 18px;text-align:right;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Net estimé éch.1</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($grades as $i => $g):
            $indice1   = (int)$g['indice_minimal'];
            $base1     = round($indice1 * VALEUR_POINT, 2);
            $cmr       = round($base1 * TAUX_CMR, 2);
            $amo       = round($base1 * TAUX_AMO, 2);
            $net_est   = round($base1 + (float)$g['indemnite_base'] - $cmr - $amo, 0);
            $isLast    = $i === count($grades)-1;
          ?>
          <tr style="<?= $isLast ? '' : 'border-bottom:1px solid #f3f4f6;' ?>" onmouseover="this.style.background='rgba(37,99,235,.03)'" onmouseout="this.style.background=''">
            <td style="padding:13px 18px;font-size:13.5px;font-weight:600;color:var(--ink);"><?= htmlspecialchars($g['libelle']) ?></td>
            <td style="padding:13px 18px;text-align:center;">
              <span style="background:rgba(37,99,235,.09);color:var(--blue);font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;">Éch. <?= htmlspecialchars($g['echelle']) ?></span>
            </td>
            <td style="padding:13px 18px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--muted);"><?= $g['indice_minimal'] ?></td>
            <td style="padding:13px 18px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--muted);"><?= $g['indice_maximal'] ?></td>
            <td style="padding:13px 18px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--ink-soft);"><?= number_format((float)$g['indemnite_base'],0,',',' ') ?> MAD</td>
            <td style="padding:13px 18px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;color:var(--success);">≈ <?= number_format($net_est,0,',',' ') ?> MAD</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="text-align:center;font-size:11.5px;color:var(--subtle);margin-top:14px;">* Net estimé = Traitement + Indemnité − CMR − AMO (hors IR, calcul indicatif échelon 1)</p>
  </div>
</section>

<!-- ========== BARÈME IR ========== -->
<section style="padding:80px 28px;background:#fff;">
  <div style="max-width:1100px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:44px;">
      <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);border-radius:100px;padding:5px 14px;font-size:11.5px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--blue);display:inline-block;"></span> Fiscalité
      </div>
      <h2 style="font-size:30px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.02em;">Barème de l'Impôt sur le Revenu</h2>
      <p style="font-size:15px;color:var(--muted);max-width:520px;margin:0 auto;line-height:1.7;">Tranches d'imposition annuelles applicables aux fonctionnaires marocains (DGI).</p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start;">
      <!-- Barres visuelles -->
      <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 16px rgba(0,0,0,.06);padding:28px;">
        <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:20px;">Visualisation des tranches</div>
        <?php
        $bareme = [
          ['0 – 30 000',   0,   '#16a34a', '0%'],
          ['30 001 – 50 000', 10, '#2563eb', '10%'],
          ['50 001 – 60 000', 20, '#0891b2', '20%'],
          ['60 001 – 80 000', 30, '#d97706', '30%'],
          ['80 001 – 180 000',34, '#ea580c', '34%'],
          ['> 180 000',    38, '#dc2626', '38%'],
        ];
        foreach($bareme as [$tranche,$taux,$color,$label]):
          $w = max(8, $taux === 0 ? 8 : round($taux / 38 * 100));
        ?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
            <span style="font-size:12px;color:var(--muted);"><?= $tranche ?> MAD</span>
            <span style="font-size:12px;font-weight:700;color:<?= $color ?>;"><?= $label ?></span>
          </div>
          <div style="height:10px;background:#f3f4f6;border-radius:100px;overflow:hidden;">
            <div style="height:100%;width:<?= $w ?>%;background:<?= $color ?>;border-radius:100px;transition:width .6s ease;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Tableau détaillé -->
      <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:var(--surface2);">
              <th style="padding:12px 16px;text-align:left;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Tranche annuelle</th>
              <th style="padding:12px 16px;text-align:center;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Taux</th>
              <th style="padding:12px 16px;text-align:right;font-size:10.5px;font-weight:700;color:var(--subtle);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #e5e7eb;">Déduction</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rows = [
              ['0 – 30 000 MAD',       '0 %',  '0'],
              ['30 001 – 50 000 MAD',  '10 %', '3 000'],
              ['50 001 – 60 000 MAD',  '20 %', '8 000'],
              ['60 001 – 80 000 MAD',  '30 %', '14 000'],
              ['80 001 – 180 000 MAD', '34 %', '17 200'],
              ['> 180 000 MAD',        '38 %', '24 400'],
            ];
            $colors2 = ['#16a34a','#2563eb','#0891b2','#d97706','#ea580c','#dc2626'];
            foreach($rows as $j=>[$tr,$tx,$ded]):
            ?>
            <tr style="<?= $j<count($rows)-1?'border-bottom:1px solid #f3f4f6;':'' ?>" onmouseover="this.style.background='rgba(37,99,235,.03)'" onmouseout="this.style.background=''">
              <td style="padding:12px 16px;font-size:12.5px;color:var(--muted);"><?= $tr ?></td>
              <td style="padding:12px 16px;text-align:center;">
                <span style="background:<?= $colors2[$j] ?>18;color:<?= $colors2[$j] ?>;font-size:12px;font-weight:700;padding:3px 10px;border-radius:100px;"><?= $tx ?></span>
              </td>
              <td style="padding:12px 16px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink-soft);"><?= $ded ?> MAD</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding:12px 16px;border-top:1px solid #f3f4f6;font-size:11px;color:var(--subtle);">Abattement FP : 17% (plafonné à 26 000 MAD/an) · Déduction conjoint : 360 MAD · Enfant : 360 MAD</div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section style="padding:80px 28px;background:var(--surface);">
  <div style="max-width:760px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:44px;">
      <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);border-radius:100px;padding:5px 14px;font-size:11.5px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--blue);display:inline-block;"></span> Questions fréquentes
      </div>
      <h2 style="font-size:30px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.02em;">FAQ</h2>
      <p style="font-size:15px;color:var(--muted);max-width:420px;margin:0 auto;line-height:1.7;">Tout ce que vous devez savoir sur le simulateur.</p>
    </div>
    <?php
    $faqs = [
      ["Qu'est-ce que le CMR ?", "La Caisse Marocaine des Retraites (CMR) est l'organisme qui gère les pensions de retraite des fonctionnaires. La cotisation est de 10% du traitement de base, prélevée directement sur le salaire."],
      ["Comment est calculé l'IR ?", "L'Impôt sur le Revenu est calculé sur le revenu annuel brut après abattement de 17% (plafonné à 26 000 MAD). Un barème progressif de 0% à 38% s'applique, avec des déductions pour charges de famille (360 MAD/an par personne)."],
      ["Qu'est-ce que l'AMO ?", "L'Assurance Maladie Obligatoire (AMO) est une couverture santé obligatoire au taux de 2,5% du traitement de base. Elle est gérée par la CNOPS pour les fonctionnaires."],
      ["La simulation est-elle officielle ?", "Non. Le simulateur est un outil indicatif basé sur les barèmes officiels en vigueur. Seul votre service RH ou la Direction du Budget peut établir un bulletin de paie officiel."],
      ["Puis-je simuler sans créer un compte ?", "Oui ! Vous pouvez lancer une simulation en mode invité. Cependant, pour sauvegarder votre historique, comparer des profils ou projeter votre carrière, un compte est nécessaire."],
      ["Comment fonctionne la valeur du point ?", "La valeur du point indiciaire est fixée à 51,40 MAD par arrêté. Votre traitement de base = votre indice brut × 51,40 MAD. L'indice dépend de votre grade et de votre échelon."],
    ];
    foreach($faqs as $idx=>[$q,$a]):
    ?>
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;box-shadow:0 1px 8px rgba(0,0,0,.04);margin-bottom:10px;overflow:hidden;">
      <button onclick="toggleFaq(<?= $idx ?>)" style="width:100%;padding:18px 20px;text-align:left;background:none;border:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;font-family:'Plus Jakarta Sans',sans-serif;">
        <span style="font-size:14px;font-weight:700;color:var(--ink);"><?= $q ?></span>
        <svg id="faq-icon-<?= $idx ?>" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--blue)" stroke-width="2.5" style="flex-shrink:0;transition:transform .25s;"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div id="faq-body-<?= $idx ?>" style="display:none;padding:0 20px 18px;border-top:1px solid #f3f4f6;">
        <p style="font-size:13.5px;color:var(--muted);line-height:1.75;margin:14px 0 0;"><?= $a ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<script>
function toggleFaq(i){
  const body=document.getElementById('faq-body-'+i);
  const icon=document.getElementById('faq-icon-'+i);
  const open=body.style.display==='block';
  body.style.display=open?'none':'block';
  icon.style.transform=open?'':'rotate(180deg)';
}
</script>

<!-- FOOTER -->
<footer class="site-footer no-print">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo-mark">
        <img src="<?= $LOGO ?>" alt="Logo MJCC" style="height:44px;width:auto;object-fit:contain;filter:brightness(0) invert(1);">
      </div>
      <div>
        <div class="footer-brand-name">Simulateur de Rémunération</div>
        <div class="footer-brand-sub">v<?= APP_VERSION ?></div>
      </div>
    </div>
    <div class="footer-center">
      <div class="footer-ministry">Ministère de la Jeunesse, de la Culture et de la Communication</div>
      <div class="footer-copy">© <?= date('Y') ?> — Usage informatif uniquement</div>
    </div>
    <div class="footer-action">
      <button onclick="ouvrirModal('connexion')" class="btn-footer-login">
        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        Espace Personnel
      </button>
    </div>
  </div>
</footer>

<!-- MODAL AUTH -->
<div class="modal-overlay" id="authModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-logo-row">
        <div class="modal-logo-mark">
          <img src="<?= $LOGO ?>" alt="Logo MJCC" style="height:38px;width:auto;object-fit:contain;">
        </div>
        <div class="modal-brand">Simulateur de Rémunération</div>
      </div>
      <button class="modal-close" onclick="fermerModal()">×</button>
    </div>

    <div class="modal-tabs">
      <button class="modal-tab active" id="mtConn" onclick="switchModalTab('connexion')">Connexion</button>
      <button class="modal-tab" id="mtInsc" onclick="switchModalTab('inscription')">Créer un compte</button>
    </div>

    <?php if ($flashAcc): ?>
    <div style="margin:10px 22px 0;">
      <div class="flash-modal <?= $flashAcc['type']==='success'?'success':'danger' ?>"><?= htmlspecialchars($flashAcc['msg']) ?></div>
    </div>
    <?php endif; ?>

    <!-- CONNEXION -->
    <div class="modal-panel" id="pConn">
      <form method="POST" action="<?= $BP ?>/index.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="login">
        <div class="m-group">
          <label class="m-label">Adresse Email</label>
          <input type="email" name="email" class="m-input" placeholder="prenom.nom@mjcc.gov.ma" required autocomplete="email">
        </div>
        <div class="m-group">
          <label class="m-label">Mot de Passe</label>
          <div class="pwd-wrap">
            <input type="password" id="lPwd" name="password" class="m-input" style="padding-right:38px" placeholder="••••••••" required>
            <button type="button" class="pwd-eye" onclick="togglePwd('lPwd')">
              <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn-modal btn-modal-primary">Se connecter</button>
      </form>
      <div class="modal-footer-note">
        Pas encore de compte ? <button onclick="switchModalTab('inscription')">S'inscrire gratuitement</button>
      </div>
    </div>

    <!-- INSCRIPTION -->
    <div class="modal-panel hidden" id="pInsc">
      <div class="modal-notice">
        ✨ <strong>Compte gratuit :</strong> Accès au simulateur, historique, comparateur et projection de carrière.
      </div>
      <form method="POST" action="<?= $BP ?>/index.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="register">
        <div class="m-group">
          <label class="m-label">Nom Complet *</label>
          <input type="text" name="nom" class="m-input" placeholder="Mohammed El Alami" required minlength="2">
        </div>
        <div class="m-group">
          <label class="m-label">Adresse Email *</label>
          <input type="email" name="email" class="m-input" placeholder="prenom.nom@mjcc.gov.ma" required>
        </div>
        <div class="m-group">
          <label class="m-label">Mot de Passe * <small style="font-weight:400;color:var(--subtle)">(min. 8 caractères)</small></label>
          <div class="pwd-wrap">
            <input type="password" id="rPwd" name="password" class="m-input" style="padding-right:38px" placeholder="••••••••" required minlength="8" oninput="checkStrength(this.value)">
            <button type="button" class="pwd-eye" onclick="togglePwd('rPwd')">
              <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="str-bar-wrap"><div class="str-bar" id="strBar"></div></div>
          <div class="str-text" id="strText"></div>
        </div>
        <div class="m-group">
          <label class="m-label">Confirmer le Mot de Passe *</label>
          <input type="password" name="password_confirm" class="m-input" placeholder="••••••••" required minlength="8">
        </div>
        <button type="submit" class="btn-modal btn-modal-success">Créer mon compte gratuitement</button>
      </form>
      <div class="modal-footer-note">
        Déjà inscrit ? <button onclick="switchModalTab('connexion')">Se connecter</button>
      </div>
    </div>
  </div>
</div>

<script>
// Modal
function ouvrirModal(tab) {
  document.getElementById('authModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  switchModalTab(tab);
  setTimeout(() => {
    const el = tab === 'connexion'
      ? document.querySelector('#pConn input[type=email]')
      : document.querySelector('#pInsc input[name=nom]');
    if (el) el.focus();
  }, 150);
}
function fermerModal() {
  document.getElementById('authModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('authModal').addEventListener('click', e => {
  if (e.target === document.getElementById('authModal')) fermerModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerModal(); });

function switchModalTab(t) {
  const c = t === 'connexion';
  document.getElementById('pConn').classList.toggle('hidden', !c);
  document.getElementById('pInsc').classList.toggle('hidden', c);
  document.getElementById('mtConn').classList.toggle('active', c);
  document.getElementById('mtInsc').classList.toggle('active', !c);
}

function togglePwd(id) {
  const e = document.getElementById(id);
  e.type = e.type === 'password' ? 'text' : 'password';
}

function checkStrength(p) {
  let s = 0;
  if (p.length >= 8) s++;
  if (p.length >= 12) s++;
  if (/[A-Z]/.test(p)) s++;
  if (/[0-9]/.test(p)) s++;
  if (/[^A-Za-z0-9]/.test(p)) s++;
  const colors = ['#EF4444','#F97316','#EAB308','#22C55E','#15803D'];
  const labels = ['Très faible','Faible','Moyen','Fort','Très fort'];
  document.getElementById('strBar').style.cssText = `width:${s*20}%;background:${colors[Math.max(0,s-1)]}`;
  document.getElementById('strText').textContent = s > 0 ? labels[s-1] : '';
}

<?php if ($flashAcc || isset($_GET['tab'])): ?>
document.addEventListener('DOMContentLoaded', () => ouvrirModal('<?= $autoTab ?>'));
<?php endif; ?>

// Aperçu instantané
const POINT = 51.40;
const gEl = document.getElementById('grade_id');
const eEl = document.getElementById('echelonRange');
const eValEl = document.getElementById('echelonVal');
const mEl = document.getElementById('mutuelle_org');

function fmtMAD(n) {
  return new Intl.NumberFormat('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' MAD';
}

function updatePreview() {
  const opt = gEl.options[gEl.selectedIndex];
  if (!opt || !opt.value) return;
  const mn = +opt.dataset.min, mx = +opt.dataset.max, indem = +opt.dataset.indem;
  const ec = +eEl.value;
  const idx = Math.round(mn + (ec - 1) * (mx - mn) / 11);
  const base = idx * POINT;
  const cmr  = base * 0.10;
  const hasMut = mEl.value !== 'aucune';
  const mut  = base * (hasMut ? 0.05 : 0.025);
  const brut = base + indem;
  const net  = brut - cmr - mut;

  document.getElementById('previewEmpty').classList.add('hidden');
  document.getElementById('previewContent').classList.remove('hidden');
  document.getElementById('pv_i').textContent   = idx;
  document.getElementById('pv_b').textContent   = fmtMAD(base);
  document.getElementById('pv_id').textContent  = fmtMAD(indem);
  document.getElementById('pv_br').textContent  = fmtMAD(brut);
  document.getElementById('pv_cmr').textContent = '− ' + fmtMAD(cmr);
  document.getElementById('pv_ml').textContent  = hasMut ? '− AMO + Mutuelle (5%)' : '− AMO (2,5%)';
  document.getElementById('pv_m').textContent   = '− ' + fmtMAD(mut);
  document.getElementById('pv_net').textContent = fmtMAD(net);
  document.getElementById('indiceInfo').textContent = `Indice ${mn} → ${mx} — Échelon ${ec}/12`;
}

eEl.addEventListener('input', function() { eValEl.textContent = this.value; updatePreview(); });
gEl.addEventListener('change', updatePreview);
mEl.addEventListener('change', updatePreview);

document.addEventListener('DOMContentLoaded', () => {
  updatePreview();
  <?php if ($simInvite): ?>
  setTimeout(() => {
    const r = document.getElementById('resultat');
    if (r) r.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 300);
  <?php endif; ?>
});
</script>
</body>
</html>