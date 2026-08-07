<?php
$user    = getUser();
$curPage = $page ?? 'dashboard';
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$titres = [
    'dashboard'   => 'Tableau de Bord',
    'simulateur'  => 'Simulateur',
    'resultat'    => 'Bulletin de Simulation',
    'historique'  => 'Historique',
    'comparateur' => 'Comparateur',
    'projection'  => 'Projection de Carrière',
    'profil'      => 'Mon Profil',
    'admin_users' => 'Utilisateurs',
    'admin_logs'  => "Journal d'Audit",
    '403'         => 'Accès Refusé',
];
$titre = $titres[$curPage] ?? 'MJCC';

$nav = [
    ['page'=>'dashboard',  'icon'=>'grid',    'label'=>'Tableau de Bord'],
    ['page'=>'simulateur', 'icon'=>'calc',    'label'=>'Simulateur'],
];
if (!estAgent()) {
    $nav[] = ['page'=>'comparateur', 'icon'=>'compare', 'label'=>'Comparateur'];
    $nav[] = ['page'=>'projection',  'icon'=>'chart',   'label'=>'Projection carrière'];
}
$nav[] = ['page'=>'historique', 'icon'=>'history', 'label'=>'Historique'];
$nav[] = ['page'=>'profil',     'icon'=>'profil',  'label'=>'Mon Profil'];
if (estAdmin()) {
    $nav[] = ['page'=>'admin_users', 'icon'=>'users', 'label'=>'Utilisateurs'];
    $nav[] = ['page'=>'admin_logs',  'icon'=>'log',   'label'=>'Journal Audit'];
}

$icons = [
    'grid'   =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>',
    'calc'   =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/><path d="M8 6h8M8 10h8M8 14h4M8 18h2" stroke-width="2" stroke-linecap="round"/></svg>',
    'compare'=>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'chart'  =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>',
    'history'=>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m-9 1A9 9 0 1012 3v1"/><path stroke-linecap="round" stroke-width="2" d="M3 4v4h4"/></svg>',
    'profil' =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'users'  =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
    'log'    =>'<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
];
$BP   = BASE_PATH;
$LOGO = $BP . '/assets/img/logo_mjcc.png';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($titre) ?> — MJCC</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:      #2563EB;
      --blue-dark: #1D4ED8;
      --blue-lt:   #3B82F6;
      --cyan:      #06B6D4;
      --cyan-light:#67E8F9;
      --grad:      linear-gradient(135deg,#2563EB 0%,#06B6D4 100%);
      --ink:       #111827;
      --ink-soft:  #374151;
      --muted:     #4B5563;
      --subtle:    #9CA3AF;
      --rule:      #E5E7EB;
      --surface:   #F3F4F6;
      --surface2:  #F9FAFB;
      --white:     #FFFFFF;
      --success:   #16A34A; --success-bg:#DCFCE7; --success-bd:#86EFAC;
      --warning:   #D97706; --warning-bg:#FEF3C7; --warning-bd:#FCD34D;
      --danger:    #DC2626; --danger-bg:#FEE2E2;  --danger-bd:#FCA5A5;
      --radius:    8px;
      --radius-lg: 12px;
      --radius-xl: 16px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
      --shadow:    0 4px 6px -1px rgba(0,0,0,.08),0 2px 4px -1px rgba(0,0,0,.04);
      --shadow-lg: 0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);
      --shadow-xl: 0 20px 40px -8px rgba(0,0,0,.14);
      --sb-w:      252px;
      --tb-h:      60px;
    }
    *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
    html { -webkit-font-smoothing:antialiased; }
    body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--surface); color:var(--ink); }
    a { text-decoration:none; color:inherit; }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sb-w); flex-shrink:0;
      display:flex; flex-direction:column;
      background:white; border-right:1px solid var(--rule);
      box-shadow:2px 0 8px rgba(0,0,0,.04);
    }
    .sb-brand {
      padding:0; border-bottom:1px solid rgba(0,0,0,.08);
      background: rgba(255,255,255,0.60);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }
    .sb-logo-bg {
      display:flex; align-items:center; justify-content:center;
      padding:14px 14px 10px;
    }
    .sb-logo-bg img {
      width: 100%; max-width: 160px; height: auto;
      object-fit: contain; display:block;
    }
    .sb-brand-info {
      padding:8px 14px 14px;
      border-top:1px solid rgba(0,0,0,.06);
      text-align:center;
    }
    .sb-brand-name { font-size:11.5px; font-weight:700; color:var(--ink); letter-spacing:.01em; line-height:1.3; }
    .sb-brand-sub  { font-size:9.5px; color:var(--subtle); margin-top:2px; }

    .sb-nav { flex:1; padding:6px 8px; overflow-y:auto; }
    .sb-section-lbl {
      font-size:9px; font-weight:700; color:var(--subtle);
      letter-spacing:.12em; text-transform:uppercase;
      padding:8px 8px 3px;
    }
    .sb-link {
      display:flex; align-items:center; gap:9px;
      padding:7px 10px; border-radius:var(--radius);
      font-size:12.5px; font-weight:500; color:var(--muted);
      transition:all .15s; border-left:3px solid transparent;
      text-decoration:none; cursor:pointer; margin-bottom:1px;
    }
    .sb-link:hover { background:rgba(37,99,235,.05); color:var(--blue); }
    .sb-link.active {
      background:rgba(37,99,235,.08); color:var(--blue);
      border-left-color:var(--blue); font-weight:700;
    }
    .sb-link-icon { width:16px; height:16px; flex-shrink:0; }
    .sb-link.active .sb-link-icon { stroke:var(--blue); }
    .sb-lock { opacity:.4; cursor:not-allowed; }
    .sb-lock:hover { background:transparent !important; color:var(--subtle) !important; }

    .sb-user { padding:14px 16px; border-top:1px solid var(--rule); }
    .sb-user-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .sb-avatar {
      width:34px; height:34px; border-radius:9px;
      background:var(--grad); display:flex;
      align-items:center; justify-content:center;
      color:white; font-size:13px; font-weight:700; flex-shrink:0;
      box-shadow:0 2px 8px rgba(37,99,235,.2);
    }
    .sb-user-name { color:var(--ink); font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:148px; }
    .badge { display:inline-block; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; }
    .badge-admin  { background:rgba(139,92,246,.12); color:#7C3AED; }
    .badge-agent  { background:rgba(37,99,235,.1);   color:var(--blue); }
    .badge-invite { background:rgba(217,119,6,.1);   color:var(--warning); }
    .sb-logout {
      width:100%; text-align:left; background:none; border:none;
      color:var(--subtle); font-size:12px; font-family:'Plus Jakarta Sans',sans-serif;
      cursor:pointer; padding:4px 2px; transition:color .15s;
      display:flex; align-items:center; gap:6px;
    }
    .sb-logout:hover { color:var(--danger); }

    /* ── TOPBAR ── */
    .topbar {
      height:var(--tb-h); background:rgba(255,255,255,.9);
      backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
      border-bottom:1px solid var(--rule);
      display:flex; align-items:center; padding:0 24px; gap:16px;
      flex-shrink:0; position:sticky; top:0; z-index:10;
    }
    .topbar-title { font-size:16px; font-weight:800; color:var(--ink); letter-spacing:-.02em; flex:1; }
    .topbar-date  { font-size:12px; color:var(--subtle); white-space:nowrap; }

    /* ── MAIN ── */
    .main-scroll { flex:1; overflow-y:auto; }
    main { padding:28px 30px; max-width:1200px; }

    /* Cards */
    .card { background:white; border-radius:var(--radius-lg); border:1px solid var(--rule); box-shadow:var(--shadow-sm); }
    .p-4{padding:16px}.p-5{padding:20px}.p-6{padding:24px}.p-8{padding:32px}.p-10{padding:40px}
    .mb-3{margin-bottom:12px}.mb-4{margin-bottom:16px}.mb-5{margin-bottom:20px}.mb-6{margin-bottom:24px}
    .mt-4{margin-top:16px}.mt-5{margin-top:20px}
    .overflow-hidden{overflow:hidden}.overflow-x-auto{overflow-x:auto}
    .text-center{text-align:center}.text-right{text-align:right}
    .text-sm{font-size:13px}.text-xs{font-size:11.5px}
    .font-semibold{font-weight:600}.font-bold{font-weight:700}
    .text-gray-400{color:var(--subtle)}.text-gray-500{color:var(--muted)}.text-gray-600{color:var(--ink-soft)}.text-gray-700{color:var(--ink-soft)}
    .space-y-0\.5>*+*{margin-top:2px}
    .grid{display:grid}.grid-cols-1{grid-template-columns:repeat(1,1fr)}.grid-cols-2{grid-template-columns:repeat(2,1fr)}.grid-cols-3{grid-template-columns:repeat(3,1fr)}
    .gap-2{gap:8px}.gap-4{gap:16px}.gap-5{gap:20px}
    .flex{display:flex}.flex-wrap{flex-wrap:wrap}.items-center{align-items:center}.justify-center{justify-content:center}.justify-between{justify-content:space-between}
    .flex-1{flex:1}.flex-col{flex-direction:column}.gap-1{gap:4px}.gap-3{gap:12px}
    .pt-4{padding-top:16px}.pb-2{padding-bottom:8px}.px-5{padding-left:20px;padding-right:20px}.px-10{padding-left:40px;padding-right:40px}.py-4{padding-top:16px;padding-bottom:16px}
    .border-b{border-bottom:1px solid var(--rule)}.border-t{border-top:1px solid var(--rule)}.border-x{border-left:1px solid var(--rule);border-right:1px solid var(--rule)}
    .rounded-lg{border-radius:var(--radius-lg)}.rounded-full{border-radius:100px}
    .bg-gray-50{background:var(--surface2)}
    .w-6{width:24px}.h-6{height:24px}.w-10{width:40px}.h-2{height:8px}
    .text-blue-900{color:#1e3a8a}.text-blue-200{color:rgba(147,197,253,.9)}.text-blue-300{color:rgba(147,197,253,.7)}.text-green-300{color:#86efac}.text-red-300{color:#fca5a5}.text-red-600{color:var(--danger)}.text-green-600{color:var(--success)}
    .text-2xl{font-size:22px}.text-xl{font-size:18px}.text-3xl{font-size:28px}.text-4xl{font-size:34px}.text-lg{font-size:16px}
    .tracking-wide{letter-spacing:.04em}.tracking-wider{letter-spacing:.08em}.tracking-widest{letter-spacing:.14em}
    .uppercase{text-transform:uppercase}.leading-none{line-height:1}
    .mx-auto{margin-left:auto;margin-right:auto}.max-w-2xl{max-width:672px}.max-w-5xl{max-width:1024px}.max-w-6xl{max-width:1152px}.w-full{width:100%}
    @media(min-width:768px){.md\:grid-cols-3{grid-template-columns:repeat(3,1fr)}}
    @media(min-width:1024px){.lg\:grid-cols-2{grid-template-columns:repeat(2,1fr)}.lg\:grid-cols-4{grid-template-columns:repeat(4,1fr)}}

    /* Form */
    .label { display:block; font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:5px; }
    .input {
      border:1.5px solid var(--rule); border-radius:var(--radius);
      padding:9px 12px; font-size:13px; width:100%;
      font-family:'Plus Jakarta Sans',sans-serif; outline:none;
      transition:border .15s; background:white; color:var(--ink); appearance:none;
    }
    .input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.09); }
    select.input {
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' fill='none' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 12px center;
      padding-right:32px; cursor:pointer;
    }

    /* Buttons */
    .btn {
      padding:9px 20px; border-radius:var(--radius); font-size:13px;
      font-weight:600; cursor:pointer; border:none; transition:all .15s;
      display:inline-flex; align-items:center; gap:6px;
      text-decoration:none; font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-primary { background:var(--grad); color:white; box-shadow:0 4px 12px rgba(37,99,235,.25); }
    .btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(37,99,235,.35); }
    .btn-secondary { background:white; color:var(--ink-soft); border:1.5px solid var(--rule); }
    .btn-secondary:hover { border-color:var(--blue); color:var(--blue); }
    .btn-outline { background:white; color:var(--blue); border:1.5px solid var(--blue); }
    .btn-outline:hover { background:var(--blue); color:white; }
    .btn-sm { padding:5px 14px; font-size:12px; }

    /* Form group */
    .form-group { margin-bottom:16px; }
    .form-group:last-child { margin-bottom:0; }

    /* Badge warning (LOGOUT etc.) */
    .badge-warning { background:var(--warning-bg); color:var(--warning); font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:100px; display:inline-block; }

    /* Tables */
    table { width:100%; border-collapse:collapse; }
    th { padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; background:var(--surface2); border-bottom:1px solid var(--rule); }
    td { padding:11px 14px; font-size:13px; border-bottom:1px solid var(--surface); color:var(--ink-soft); }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:var(--surface2); }
    .mono { font-family:'JetBrains Mono',monospace; }

    /* Flash */
    .flash { padding:12px 16px; border-radius:var(--radius); font-size:13px; margin-bottom:18px; font-weight:500; border:1.5px solid; }
    .flash-success { background:var(--success-bg); color:var(--success); border-color:var(--success-bd); }
    .flash-danger  { background:var(--danger-bg);  color:var(--danger);  border-color:var(--danger-bd); }

    /* Card header / title / subtitle */
    .card-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:16px 20px; border-bottom:1px solid var(--rule);
      background:var(--surface2);
    }
    .card-title {
      font-size:15px; font-weight:800; color:var(--ink); letter-spacing:-.01em;
    }
    .card-subtitle {
      font-size:12px; color:var(--subtle); margin-top:3px; font-weight:500;
    }
    .modal-title {
      font-size:16px; font-weight:800; color:var(--ink); margin-bottom:20px;
      padding-bottom:14px; border-bottom:1px solid var(--rule); letter-spacing:-.01em;
    }

    /* Table wrap */
    .table-wrap { overflow-x:auto; }
    .table-wrap table { width:100%; border-collapse:collapse; }
    .table-wrap table thead tr { background:var(--surface2); }
    .table-wrap table th {
      padding:10px 14px; font-size:10.5px; font-weight:700;
      text-transform:uppercase; letter-spacing:.06em; color:var(--subtle);
      border-bottom:1px solid var(--rule); white-space:nowrap;
    }
    .table-wrap table td {
      padding:11px 14px; font-size:13px; color:var(--ink);
      border-bottom:1px solid var(--rule);
    }
    .table-wrap table tbody tr:last-child td { border-bottom:none; }
    .table-wrap table tbody tr:hover { background:rgba(37,99,235,.02); }

    /* Badges supplémentaires */
    .badge-gold   { background:rgba(217,119,6,.12);  color:#92400e; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:100px; display:inline-block; }
    .badge-navy   { background:rgba(37,99,235,.1);   color:var(--blue); font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:100px; display:inline-block; }
    .badge-success{ background:var(--success-bg); color:var(--success); font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:100px; display:inline-block; }
    .badge-danger { background:var(--danger-bg);  color:var(--danger);  font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:100px; display:inline-block; }

    /* Bouton danger */
    .btn-danger-btn {
      background:var(--danger-bg); color:var(--danger);
      border:1.5px solid var(--danger-bd); border-radius:var(--radius);
      padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer;
      transition:all .15s; font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-danger-btn:hover { background:var(--danger); color:white; }

    /* Grid 2 colonnes */
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media(max-width:480px) { .grid-2 { grid-template-columns:1fr; } }

    /* Modal */
    .modal { display:none; position:fixed; inset:0; background:rgba(17,24,39,.5); z-index:50; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
    .modal.open { display:flex; }
    .modal-box { background:white; border-radius:var(--radius-xl); padding:28px; max-width:480px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-xl); border:1px solid var(--rule); }

    /* Pagination */
    .pagination a,.pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 10px; border-radius:var(--radius); font-size:12px; border:1.5px solid var(--rule); color:var(--muted); text-decoration:none; }
    .pagination a:hover { background:var(--blue); color:white; border-color:var(--blue); }
    .pagination .current { background:var(--blue); color:white; border-color:var(--blue); }

    /* Comparateur */
    .comp-a { border-top:3px solid var(--blue); }
    .comp-b { border-top:3px solid var(--success); }
    .diff-pos  { color:var(--success); font-weight:600; }
    .diff-neg  { color:var(--danger);  font-weight:600; }
    .diff-zero { color:var(--subtle); }
    .space-y-3>*+* { margin-top:12px; }

    /* Mobile */
    .btn-menu { display:none; width:36px; height:36px; border-radius:var(--radius); border:1.5px solid var(--rule); background:white; cursor:pointer; align-items:center; justify-content:center; }
    #overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:39; }
    #overlay.open { display:block; }
    @media(max-width:768px) {
      .sidebar { position:fixed; top:0; left:0; height:100%; z-index:40; transform:translateX(-100%); transition:transform .25s; }
      .sidebar.open { transform:translateX(0) !important; }
      .btn-menu { display:flex; }
      main { padding:16px; }
      .hide-mobile { display:none; }
    }
    @media print { .no-print { display:none !important; } .card { box-shadow:none; border:1px solid #ddd; } }
  </style>
</head>
<body style="height:100vh;overflow:hidden;display:flex;flex-direction:column;">

<?php if($curPage==='login'): require __DIR__.'/pages/login.php'; else: ?>
<div id="overlay" onclick="closeSidebar()"></div>
<div style="display:flex;height:100vh;overflow:hidden;">

  <!-- SIDEBAR -->
  <nav class="sidebar no-print" id="sidebar">
    <div class="sb-brand">
      <a href="<?= $BP ?>/accueil.php" style="display:block;text-decoration:none;" title="Retour à l'accueil">
        <div class="sb-logo-bg">
          <img src="<?= $LOGO ?>" alt="Logo Ministère MJCC">
        </div>
        <div class="sb-brand-info">
          <div class="sb-brand-name">Simulateur de Rémunération</div>
          <div class="sb-brand-sub">Fonction Publique — Maroc</div>
        </div>
      </a>
    </div>

    <div class="sb-nav">
      <div class="sb-section-lbl">Navigation</div>
      <?php foreach($nav as $item):
        $active = $curPage===$item['page'] ? 'active' : '';
      ?>
      <a href="<?= $BP ?>/index.php?page=<?= $item['page'] ?>"
         class="sb-link <?= $active ?>" onclick="closeSidebar()">
        <span class="sb-link-icon"><?= $icons[$item['icon']] ?? '' ?></span>
        <?= htmlspecialchars($item['label']) ?>
      </a>
      <?php endforeach; ?>

      <?php if(estAgent()): ?>
      <div class="sb-section-lbl" style="margin-top:8px;">Accès limité</div>
      <div class="sb-link sb-lock">
        <span class="sb-link-icon"><?= $icons['compare'] ?></span>
        Comparateur
        <span style="margin-left:auto;font-size:11px;">🔒</span>
      </div>
      <div class="sb-link sb-lock">
        <span class="sb-link-icon"><?= $icons['chart'] ?></span>
        Projection
        <span style="margin-left:auto;font-size:11px;">🔒</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- User -->
    <div class="sb-user">
      <div class="sb-user-row">
        <div class="sb-avatar"><?= strtoupper(mb_substr($user['nom'] ?? 'U', 0, 1)) ?></div>
        <div style="min-width:0;">
          <div class="sb-user-name"><?= htmlspecialchars($user['nom'] ?? '') ?></div>
          <div style="margin-top:3px;">
            <?php if(estAdmin()): ?>
              <span class="badge badge-admin">Administrateur</span>
            <?php elseif(estAgent()): ?>
              <span class="badge badge-agent">Agent</span>
            <?php else: ?>
              <span class="badge badge-invite">Invité</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <form method="POST" action="<?= $BP ?>/index.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="logout">
        <button type="submit" class="sb-logout">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Déconnexion
        </button>
      </form>
    </div>
  </nav>

  <!-- MAIN AREA -->
  <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;">
    <header class="topbar no-print">
      <button class="btn-menu" onclick="openSidebar()">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="topbar-title"><?= htmlspecialchars($titre) ?></div>
      <div style="display:flex;align-items:center;gap:12px;">
        <span class="topbar-date hide-mobile"><?= date('d/m/Y') ?></span>
        <?php if(estAdmin()): ?>
          <span class="badge badge-admin">Admin</span>
        <?php elseif(estAgent()): ?>
          <span class="badge badge-agent">Agent</span>
        <?php else: ?>
          <span class="badge badge-invite">Invité</span>
        <?php endif; ?>
      </div>
    </header>

    <div class="main-scroll">
      <main>
        <?php if($flash): ?>
        <div class="flash flash-<?= $flash['type']==='success' ? 'success' : 'danger' ?>">
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
        <?php endif; ?>
        <?php
        $viewFile = __DIR__.'/pages/'.$curPage.'.php';
        if(file_exists($viewFile)) require $viewFile;
        else echo '<div class="card p-8" style="text-align:center;color:var(--subtle);">Page introuvable.</div>';
        ?>
      </main>
      <footer class="no-print" style="padding:12px 30px;border-top:1px solid var(--rule);background:white;">
        <span style="font-size:11px;color:var(--subtle);">
          © <?= date('Y') ?> Ministère de la Jeunesse, de la Culture et de la Communication — Royaume du Maroc — v<?= APP_VERSION ?>
        </span>
      </footer>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeSidebar(); });
</script>
</body>
</html>