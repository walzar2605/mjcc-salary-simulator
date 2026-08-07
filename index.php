<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/calcul.php';

demarrerSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    if ($action === 'login') {
        if (!csrfCheck($_POST['csrf_token'] ?? '')) { $_SESSION['flash']=['type'=>'danger','msg'=>'Token invalide.']; redirect('login'); }
        $user = authentifier(filter_input(INPUT_POST,'email',FILTER_SANITIZE_EMAIL)??'', $_POST['password']??'');
        if ($user) {
            connecterUtilisateur($user);
            // ── Récupérer simulation invité en attente ────────
            if (!empty($_SESSION['sim_pending'])) {
                $sim   = $_SESSION['sim_pending'];
                $simId = sauvegarderSimulation((int)$_SESSION['user_id'], $sim);
                unset($_SESSION['sim_pending']);
                $_SESSION['last_sim'] = $sim;
                $_SESSION['flash'] = ['type'=>'success','msg'=>'Simulation sauvegardée automatiquement !'];
                redirect('resultat', ['id' => $simId]);
            }
            redirect('dashboard');
        }
        logAction(null,'LOGIN_FAILED',['email'=>$_POST['email']??'']);
        $_SESSION['flash']=['type'=>'danger','msg'=>'Email ou mot de passe incorrect.'];
        redirect('login');
    }

    if ($action === 'register') {
        if (!csrfCheck($_POST['csrf_token'] ?? '')) { $_SESSION['flash_accueil']=['type'=>'danger','msg'=>'Token invalide.']; header('Location:'.BASE_PATH.'/accueil.php?tab=register'); exit; }
        $db=getDB(); $nom=trim($_POST['nom']??''); $email=strtolower(trim($_POST['email']??'')); $pwd=$_POST['password']??''; $pwd2=$_POST['password_confirm']??'';
        $err=[];
        if(mb_strlen($nom)<2) $err[]='Nom trop court.';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $err[]='Email invalide.';
        if(strlen($pwd)<8) $err[]='Mot de passe min. 8 caractères.';
        if($pwd!==$pwd2) $err[]='Mots de passe différents.';
        if(empty($err)){$c=$db->prepare('SELECT id FROM users WHERE email=:e');$c->execute([':e'=>$email]);if($c->fetch())$err[]='Email déjà utilisé.';}
        if(!empty($err)){$_SESSION['flash_accueil']=['type'=>'danger','msg'=>implode(' ',$err)];header('Location:'.BASE_PATH.'/accueil.php?tab=register');exit;}
        $db->prepare('INSERT INTO users (nom,email,password,role,actif) VALUES(:n,:e,:p,"invite",1)')->execute([':n'=>$nom,':e'=>$email,':p'=>password_hash($pwd,PASSWORD_BCRYPT,['cost'=>10])]);
        $newId=(int)$db->lastInsertId();
        logAction($newId,'REGISTER',['email'=>$email]);
        $user=authentifier($email,$pwd);
        if($user){
            connecterUtilisateur($user);
            // ── Récupérer simulation invité en attente ────────
            if (!empty($_SESSION['sim_pending'])) {
                $sim   = $_SESSION['sim_pending'];
                $simId = sauvegarderSimulation((int)$_SESSION['user_id'], $sim);
                unset($_SESSION['sim_pending']);
                $_SESSION['last_sim'] = $sim;
                $_SESSION['flash'] = ['type'=>'success','msg'=>'Bienvenue ! Votre simulation a été sauvegardée automatiquement.'];
                redirect('resultat', ['id' => $simId]);
            }
            redirect('dashboard');
        }
    }

    if ($action === 'logout') { deconnecterUtilisateur(); header('Location:'.BASE_PATH.'/accueil.php'); exit; }

    if ($action === 'change_password') {
        exigerConnexion();
        if (!csrfCheck($_POST['csrf_token'] ?? '')) die('Token invalide.');
        $db=getDB(); $uid=(int)$_SESSION['user_id'];
        $hash=$db->prepare('SELECT password FROM users WHERE id=:id'); $hash->execute([':id'=>$uid]); $hash=$hash->fetchColumn();
        $cur=$_POST['current_password']??''; $new=$_POST['new_password']??''; $con=$_POST['confirm_password']??'';
        if(!password_verify($cur,$hash)) $_SESSION['flash']=['type'=>'danger','msg'=>'Mot de passe actuel incorrect. / كلمة المرور الحالية غير صحيحة.'];
        elseif(strlen($new)<8) $_SESSION['flash']=['type'=>'danger','msg'=>'Nouveau mot de passe trop court. / كلمة المرور قصيرة جداً.'];
        elseif($new!==$con) $_SESSION['flash']=['type'=>'danger','msg'=>'Les mots de passe ne correspondent pas. / كلمتا المرور غير متطابقتين.'];
        else{$db->prepare('UPDATE users SET password=:p WHERE id=:id')->execute([':p'=>password_hash($new,PASSWORD_BCRYPT,['cost'=>10]),':id'=>$uid]);logAction($uid,'CHANGE_PASSWORD');$_SESSION['flash']=['type'=>'success','msg'=>'Mot de passe mis à jour. / تم تحديث كلمة المرور.'];}
        redirect('profil');
    }

    if ($action === 'toggle_actif') {
        exigerConnexion('admin');
        if (!csrfCheck($_POST['csrf_token'] ?? '')) die('Token invalide.');
        $db=getDB(); $uid=(int)($_POST['user_id']??0); $actif=(int)($_POST['actif']??0);
        if($uid && $uid!==(int)$_SESSION['user_id']){$db->prepare('UPDATE users SET actif=:a WHERE id=:id')->execute([':a'=>$actif,':id'=>$uid]);logAction((int)$_SESSION['user_id'],'TOGGLE_ACTIF',['user_id'=>$uid]);$_SESSION['flash']=['type'=>'success','msg'=>'Statut mis à jour.'];}
        redirect('admin_users');
    }

    if ($action === 'create_user') {
        exigerConnexion('admin');
        if (!csrfCheck($_POST['csrf_token'] ?? '')) die('Token invalide.');
        $db=getDB(); $nom=trim($_POST['nom']??''); $email=strtolower(trim($_POST['email']??'')); $role=in_array($_POST['role']??'',['admin','agent'])?$_POST['role']:'agent'; $pwd=$_POST['password']??''; $actif=isset($_POST['actif'])?1:0;
        if($nom&&filter_var($email,FILTER_VALIDATE_EMAIL)&&strlen($pwd)>=8){
            try{$db->prepare('INSERT INTO users (nom,email,password,role,actif) VALUES(:n,:e,:p,:r,:a)')->execute([':n'=>$nom,':e'=>$email,':p'=>password_hash($pwd,PASSWORD_BCRYPT,['cost'=>10]),':r'=>$role,':a'=>$actif]);logAction((int)$_SESSION['user_id'],'CREATE_USER',['email'=>$email]);$_SESSION['flash']=['type'=>'success','msg'=>"Utilisateur « {$nom} » créé."];}
            catch(Exception $e){$_SESSION['flash']=['type'=>'danger','msg'=>'Email déjà utilisé.'];}
        } else $_SESSION['flash']=['type'=>'danger','msg'=>'Données invalides.'];
        redirect('admin_users');
    }

    // ── SIMULER ───────────────────────────────────────────────
    if ($action === 'simuler') {
        exigerConnexion();
        if (!csrfCheck($_POST['csrf_token'] ?? '')) die('Token invalide.');
        $gradeId     = (int)($_POST['grade_id'] ?? 0);
        $echelon     = max(1, min(12, (int)($_POST['echelon'] ?? 1)));
        $situation   = $_POST['situation_familiale'] ?? 'celibataire';
        $nbEnfants   = max(0, min(6, (int)($_POST['nb_enfants'] ?? 0)));
        $mutuelleOrg = $_POST['mutuelle_org'] ?? 'aucune';
        $corps       = preg_replace('/[^a-z_]/', '', $_POST['corps'] ?? '');
        $nomEmploye  = (estAgent()||estAdmin()) ? mb_substr(strip_tags(trim($_POST['nom_employe']??'')),0,100) : '';
        if (!array_key_exists($mutuelleOrg, MUTUELLES)) $mutuelleOrg = 'aucune';
        try {
            $sim              = simulerRemuneration($gradeId,$echelon,$situation,$nbEnfants,$mutuelleOrg,$corps);
            $sim['nom_employe'] = $nomEmploye;
            $simId            = sauvegarderSimulation((int)$_SESSION['user_id'], $sim);
            $_SESSION['last_sim'] = $sim;
            redirect('resultat', ['id' => $simId]);
        } catch(Exception $e) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'Erreur : '.htmlspecialchars($e->getMessage())];
            redirect('simulateur');
        }
    }
}

// ── Routing ───────────────────────────────────────────────────
$page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'accueil');
$publiques = ['login','accueil'];
$protegees = ['dashboard','simulateur','resultat','historique','comparateur','projection','profil','admin_users','admin_logs'];
$exports   = ['export_csv','export_pdf','export_excel','export_projection_pdf'];
if (!in_array($page, array_merge($publiques,$protegees,$exports))) $page = estConnecte()?'dashboard':'accueil';
if (in_array($page,$protegees)) exigerConnexion();
if (in_array($page,['login','accueil']) && estConnecte()) redirect('dashboard');
if (in_array($page,['admin_users','admin_logs']) && !estAdmin()) $page='403';
// ── CORRIGÉ : bloquer AGENTS (pas invités) sur comparateur/projection ──
if (in_array($page,['comparateur','projection']) && estAgent()) {
    $_SESSION['flash']=['type'=>'danger','msg'=>'Comparateur et Projection réservés aux invités et admins.'];
    redirect('dashboard');
}
if ($page==='export_csv')            { exigerConnexion(); require_once __DIR__.'/actions/export_csv.php'; exit; }
if ($page==='export_pdf')            { exigerConnexion(); require_once __DIR__.'/actions/export_pdf.php'; exit; }
if ($page==='export_excel')          { exigerConnexion(); require_once __DIR__.'/actions/export_excel.php'; exit; }
if ($page==='export_projection_pdf') { exigerConnexion(); require_once __DIR__.'/actions/export_projection_pdf.php'; exit; }
require_once __DIR__.'/views/layout.php';