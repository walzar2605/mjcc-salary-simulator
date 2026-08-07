<?php
/**
 * MJCC — Simulateur v2.4
 */
$grades = getGradesCaches();
$BP     = BASE_PATH;

$corps_list = [
    ''  => '— Sélectionner une direction —',
    'direction_jeunesse'      => 'Direction de la Jeunesse',
    'div_etablissements'      => '↳ Division des établissements de jeunesse',
    'div_programmes_jeunesse' => '↳ Division des programmes de jeunesse',
    'div_colonies'            => '↳ Division des colonies de vacances',
    'direction_enfance'       => "Direction de l'Enfance et des affaires féminines",
    'div_affaires_feminines'  => '↳ Division des affaires féminines',
    'div_enfance'             => "↳ Division de l'Enfance",
    'div_protection_enfance'  => "↳ Division de la protection de l'Enfance",
    'direction_admin'         => 'Direction des Affaires Administratives et Générales',
    'div_rh'                  => '↳ Division des ressources humaines',
    'div_budget'              => '↳ Division du budget et de la comptabilité',
    'div_si'                  => "↳ Division des systèmes d'information",
    'div_installations'       => '↳ Division des installations et des équipements',
    'div_documentation'       => '↳ Division de la documentation et de la gestion des risques',
    'direction_cooperation'   => 'Direction de la Coopération, Communication et Études Juridiques',
    'div_cooperation'         => '↳ Division de la coopération et du partenariat',
    'div_communication'       => '↳ Division de la Communication',
    'div_juridique'           => '↳ Division des affaires juridiques',
    'secretariat_general'     => 'Secrétariat Général',
    'inspection_generale'     => 'Inspection Générale',
    'div_planification'       => 'Division de la Planification Stratégique et Contrôle de Gestion',
];

$mutuelles_list = [
    'aucune' => 'Aucune mutuelle',
    'mgpap'  => 'MGPAP',
    'mgen'   => 'MGEN',
    'douanes'=> 'DOUANES',
    'police' => 'POLICE',
    'faux'   => 'F.AUX',
    'omfam'  => 'OMFAM',
];

$situations = [
    'celibataire'       => 'Célibataire',
    'marie_sans_enfant' => 'Marié(e) sans enfant',
    'marie_1enfant'     => 'Marié(e) — 1 enfant',
    'marie_2enfants'    => 'Marié(e) — 2 enfants',
    'marie_3enfants'    => 'Marié(e) — 3 enfants',
    'marie_4enfants'    => 'Marié(e) — 4 enfants et +',
];

$gradesByEchelle = [];
foreach ($grades as $g) $gradesByEchelle[$g['echelle']][] = $g;
$cats = [
    'Catégorie A — Échelle 11' => ['11'],
    'Catégorie A — Échelle 10' => ['10'],
    'Catégorie B — Échelle 9'  => ['9'],
    'Catégorie B — Échelle 7'  => ['7'],
    'Catégorie C — Échelle 5'  => ['5'],
];
?>

<style>
  .sim-layout { display:grid; grid-template-columns:1fr 300px; gap:20px; max-width:1000px; }
  @media(max-width:900px){ .sim-layout { grid-template-columns:1fr; } }

  .sim-card { background:white; border:1px solid var(--rule); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; }
  .sim-card-header {
    padding:18px 24px 16px; border-bottom:1px solid var(--rule);
    display:flex; align-items:center; gap:12px;
    background:linear-gradient(135deg,rgba(37,99,235,.04),rgba(6,182,212,.04));
  }
  .sim-card-icon { width:38px; height:38px; border-radius:9px; background:var(--grad); display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 4px 12px rgba(37,99,235,.25); }
  .sim-card-icon svg { width:19px; height:19px; stroke:white; }
  .sim-card-title { font-size:14.5px; font-weight:700; color:var(--ink); letter-spacing:-.01em; }
  .sim-card-sub { font-size:11.5px; color:var(--subtle); margin-top:2px; }
  .sim-card-body { padding:24px; }

  .f-group { margin-bottom:18px; }
  .f-label { display:block; font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:5px; letter-spacing:-.01em; }
  .f-label small { font-weight:400; color:var(--subtle); }
  .f-required { color:var(--danger); }
  .f-input {
    border:1.5px solid var(--rule); border-radius:var(--radius);
    padding:9px 12px; font-size:13.5px; width:100%;
    font-family:'Plus Jakarta Sans',sans-serif; outline:none;
    transition:border .15s,box-shadow .15s; background:white; color:var(--ink); appearance:none;
  }
  .f-input::placeholder { color:var(--subtle); }
  .f-input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.09); }
  select.f-input {
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' fill='none' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 12px center;
    padding-right:32px; cursor:pointer;
  }

  .slider-row { display:flex; align-items:center; gap:12px; }
  .sim-slider {
    flex:1; height:5px; border-radius:3px; cursor:pointer; -webkit-appearance:none;
    background:linear-gradient(90deg,var(--blue),var(--cyan));
  }
  .sim-slider::-webkit-slider-thumb {
    -webkit-appearance:none; width:18px; height:18px; border-radius:50%;
    background:white; border:3px solid var(--blue); box-shadow:0 1px 4px rgba(0,0,0,.18);
    cursor:pointer; transition:transform .15s;
  }
  .sim-slider::-webkit-slider-thumb:hover { transform:scale(1.2); }
  .slider-val {
    min-width:38px; height:36px; display:flex; align-items:center; justify-content:center;
    background:var(--grad); color:white; border-radius:var(--radius);
    font-family:'JetBrains Mono',monospace; font-size:15px; font-weight:700;
    flex-shrink:0; box-shadow:0 2px 8px rgba(37,99,235,.25);
  }
  .slider-hint { font-size:11px; color:var(--subtle); margin-top:5px; }

  .sim-btn-row { display:flex; gap:12px; margin-top:20px; }
  .sim-btn-calc {
    flex:1; padding:12px; border-radius:var(--radius-lg);
    background:var(--grad); color:white; font-size:14px; font-weight:700;
    border:none; cursor:pointer; transition:all .2s;
    font-family:'Plus Jakarta Sans',sans-serif;
    box-shadow:0 4px 16px rgba(37,99,235,.25);
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .sim-btn-calc:hover { transform:translateY(-2px); box-shadow:0 6px 22px rgba(37,99,235,.35); }
  .sim-btn-calc svg { width:17px; height:17px; stroke:white; }
  .sim-btn-reset {
    padding:12px 20px; border-radius:var(--radius-lg);
    background:white; color:var(--ink-soft); font-size:13px; font-weight:600;
    border:1.5px solid var(--rule); cursor:pointer; transition:all .15s;
    font-family:'Plus Jakarta Sans',sans-serif;
  }
  .sim-btn-reset:hover { border-color:var(--blue); color:var(--blue); }

  /* Aperçu */
  .apercu-card { background:white; border:1px solid var(--rule); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; position:sticky; top:80px; }
  .apercu-header { padding:13px 16px; border-bottom:1px solid var(--rule); display:flex; align-items:center; gap:8px; background:linear-gradient(135deg,rgba(37,99,235,.05),rgba(6,182,212,.05)); }
  .apercu-dot { width:8px; height:8px; border-radius:50%; background:var(--grad); box-shadow:0 0 8px rgba(37,99,235,.4); }
  .apercu-title { font-size:13px; font-weight:700; color:var(--ink); }
  .apercu-empty { padding:40px 20px; text-align:center; color:var(--subtle); }
  .apercu-empty svg { width:38px; height:38px; stroke:var(--rule); margin:0 auto 10px; display:block; }
  .apercu-empty p { font-size:12.5px; line-height:1.5; }
  .apercu-rows { padding:14px 16px; }
  .apercu-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid var(--surface); }
  .apercu-row:last-child { border:none; }
  .ap-label { font-size:11.5px; color:var(--muted); }
  .ap-value { font-family:'JetBrains Mono',monospace; font-size:11.5px; font-weight:600; color:var(--ink); }
  .ap-value.hl { background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; font-size:13px; }
  .ap-value.neg { color:var(--danger); }
  .apercu-net {
    margin:0 12px 14px; padding:16px; border-radius:var(--radius-lg);
    background:var(--grad); text-align:center;
    box-shadow:0 4px 14px rgba(37,99,235,.22);
  }
  .apercu-net-label { font-size:9.5px; font-weight:700; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.1em; }
  .apercu-net-val { font-family:'JetBrains Mono',monospace; font-size:20px; font-weight:700; color:white; margin-top:4px; }
  .apercu-net-note { font-size:10px; color:rgba(255,255,255,.45); margin-top:3px; }
</style>

<div class="sim-layout">

  <!-- Formulaire -->
  <div class="sim-card">
    <div class="sim-card-header">
      <div class="sim-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round"/></svg>
      </div>
      <div>
        <div class="sim-card-title">Paramètres de Simulation</div>
        <div class="sim-card-sub">Renseignez les informations de l'agent à simuler</div>
      </div>
    </div>
    <div class="sim-card-body">
      <form method="POST" action="<?= $BP ?>/index.php" id="fSim">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="simuler">

        <?php if (estAgent() || estAdmin()): ?>
        <div class="f-group">
          <label class="f-label" for="nom_employe">
            Nom de l'Employé
            <small>(optionnel — apparaît sur le bulletin)</small>
          </label>
          <input type="text" id="nom_employe" name="nom_employe"
            class="f-input" placeholder="Ex : Mohammed El Alami"
            maxlength="100">
        </div>
        <?php endif; ?>

        <div class="f-group">
          <label class="f-label" for="corps">Corps / Direction</label>
          <select id="corps" name="corps" class="f-input">
            <?php foreach ($corps_list as $val => $lib): ?>
            <option value="<?= $val ?>"><?= htmlspecialchars($lib) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label" for="grade_id">Grade <span class="f-required">*</span></label>
          <select id="grade_id" name="grade_id" class="f-input" required>
            <option value="">— Sélectionner un grade —</option>
            <?php foreach ($cats as $label => $echelles):
              $hasG = false;
              foreach ($echelles as $e) if (!empty($gradesByEchelle[$e])) { $hasG=true; break; }
              if (!$hasG) continue;
            ?>
            <optgroup label="<?= $label ?>">
              <?php foreach ($echelles as $e):
                foreach ($gradesByEchelle[$e] ?? [] as $g): ?>
              <option value="<?= $g['id'] ?>"
                data-min="<?= $g['indice_minimal'] ?>"
                data-max="<?= $g['indice_maximal'] ?>"
                data-indem="<?= $g['indemnite_base'] ?>">
                <?= htmlspecialchars($g['libelle']) ?>
              </option>
              <?php endforeach; endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Échelon <span class="f-required">*</span></label>
          <div class="slider-row">
            <input type="range" id="echelonRange" name="echelon"
              min="1" max="12" value="1" class="sim-slider">
            <div class="slider-val" id="echelonVal">1</div>
          </div>
          <div class="slider-hint" id="indiceInfo">Sélectionnez d'abord un grade</div>
        </div>

        <div class="f-group">
          <label class="f-label" for="situation_familiale">Situation Familiale <span class="f-required">*</span></label>
          <select id="situation_familiale" name="situation_familiale" class="f-input">
            <?php foreach ($situations as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="blockEnfants" class="f-group" style="display:none;">
          <label class="f-label" for="nb_enfants">Enfants à charge</label>
          <input type="number" id="nb_enfants" name="nb_enfants"
            class="f-input" min="0" max="6" value="0">
        </div>

        <div class="f-group">
          <label class="f-label" for="mutuelle_org">
            Mutuelle
            <small>(AMO 2,5% + Mutuelle 2,5%)</small>
          </label>
          <select id="mutuelle_org" name="mutuelle_org" class="f-input">
            <?php foreach ($mutuelles_list as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="sim-btn-row">
          <button type="submit" class="sim-btn-calc">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Calculer la Rémunération
          </button>
          <button type="reset" class="sim-btn-reset" onclick="resetApercu()">Réinitialiser</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Aperçu -->
  <div class="apercu-card">
    <div class="apercu-header">
      <span class="apercu-dot"></span>
      <span class="apercu-title">Aperçu Instantané</span>
    </div>
    <div id="apercuVide" class="apercu-empty">
      <svg fill="none" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="1.5"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round" stroke-width="1.5"/></svg>
      <p>Sélectionnez un grade pour voir l'aperçu en temps réel</p>
    </div>
    <div id="apercuContent" style="display:none;">
      <div class="apercu-rows">
        <div class="apercu-row"><span class="ap-label">Indice brut</span><span class="ap-value hl" id="ap_indice">—</span></div>
        <div class="apercu-row"><span class="ap-label">Traitement de base</span><span class="ap-value" id="ap_base">—</span></div>
        <div class="apercu-row"><span class="ap-label">+ Indemnité (IRF)</span><span class="ap-value" id="ap_indem">—</span></div>
        <div class="apercu-row"><span class="ap-label">= Brut total</span><span class="ap-value hl" id="ap_brut">—</span></div>
        <div class="apercu-row"><span class="ap-label">− CMR (10%)</span><span class="ap-value neg" id="ap_cmr">—</span></div>
        <div class="apercu-row"><span class="ap-label" id="ap_mut_label">− AMO (2,5%)</span><span class="ap-value neg" id="ap_mut">—</span></div>
      </div>
      <div class="apercu-net">
        <div class="apercu-net-label">Net avant IR</div>
        <div class="apercu-net-val" id="ap_net">—</div>
        <div class="apercu-net-note">L'IR est calculé à la soumission</div>
      </div>
    </div>
  </div>

</div>

<script>
const POINT = 51.40;
const gradeEl    = document.getElementById('grade_id');
const echelonEl  = document.getElementById('echelonRange');
const echelonV   = document.getElementById('echelonVal');
const sitEl      = document.getElementById('situation_familiale');
const mutEl      = document.getElementById('mutuelle_org');
const blockEnf   = document.getElementById('blockEnfants');

function fmtMAD(n) {
  return new Intl.NumberFormat('fr-MA',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n)+' MAD';
}

function updateApercu() {
  const opt = gradeEl.options[gradeEl.selectedIndex];
  if (!opt || !opt.value) return;
  const mn    = parseInt(opt.dataset.min);
  const mx    = parseInt(opt.dataset.max);
  const indem = parseFloat(opt.dataset.indem);
  const ech   = parseInt(echelonEl.value);
  const indice = Math.round(mn + (ech-1) * (mx-mn) / 11);
  const base  = indice * POINT;
  const cmr   = base * 0.10;
  const hasMut = mutEl.value !== 'aucune';
  const mut   = base * (hasMut ? 0.05 : 0.025);
  const brut  = base + indem;
  const net   = brut - cmr - mut;

  document.getElementById('apercuVide').style.display = 'none';
  document.getElementById('apercuContent').style.display = 'block';
  document.getElementById('ap_indice').textContent = indice;
  document.getElementById('ap_base').textContent   = fmtMAD(base);
  document.getElementById('ap_indem').textContent  = fmtMAD(indem);
  document.getElementById('ap_brut').textContent   = fmtMAD(brut);
  document.getElementById('ap_cmr').textContent    = '− ' + fmtMAD(cmr);
  document.getElementById('ap_mut_label').textContent = hasMut ? '− AMO + Mutuelle (5%)' : '− AMO (2,5%)';
  document.getElementById('ap_mut').textContent    = '− ' + fmtMAD(mut);
  document.getElementById('ap_net').textContent    = fmtMAD(net);
  document.getElementById('indiceInfo').textContent = `Indice ${mn} → ${mx} — Échelon ${ech}/12`;
}

function resetApercu() {
  document.getElementById('apercuVide').style.display  = 'block';
  document.getElementById('apercuContent').style.display = 'none';
  echelonV.textContent = '1';
  document.getElementById('indiceInfo').textContent = "Sélectionnez d'abord un grade";
}

echelonEl.addEventListener('input', function() { echelonV.textContent = this.value; updateApercu(); });
gradeEl.addEventListener('change', updateApercu);
mutEl.addEventListener('change', updateApercu);
sitEl.addEventListener('change', function() {
  const show = this.value !== 'celibataire';
  blockEnf.style.display = show ? 'block' : 'none';
  const nb = this.value.match(/marie_(\d+)/);
  document.getElementById('nb_enfants').value = nb ? nb[1] : 0;
});
</script>