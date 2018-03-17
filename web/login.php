<?php
require('header.php');
require('userUtils.php');
require('formUtils.php');

global $props;
$formid=0;
$opts=array();
$opts['invalid']=array();
$u=array();
$action='login';

if (isset($_REQUEST['action'])) {
    $reqaction=$_REQUEST['action'];
} else {
    $reqaction='loginform';
}
if ($reqaction==='login') {
    $ok=true;
    foreach (['login','password'] as $v) {
        if (isset($_REQUEST[$v])) {
            $u[$v]=$_REQUEST[$v];
            $opts['invalid'][$v]=[];
            if ($u[$v]==='') {
                $ok=false;
                $opts['invalid'][$v][]='Ce champ est obligatoire';
            }
        } else {
            $ok=false;
            $opts['invalid'][$v]=['Ce champ est obligatoire'];
        }
    }
    if ($ok) {
        $users=usersGetTable($u['login']);
        $encpass=hashPassword($u['password']);
        if (count($users)>0) {
            if ($users[0]['authelem']===$encpass) {
                $ok=true;
            } else {
                $opts['invalid']['password'][]='L\'identifiant ou le mot de passe ne correspondent pas';
                $ok=false;
            }
        }
    }
    $reqaction='loginform';
    if ($ok) {
        $_SESSION['USER']=$u['login'];
        $reqaction='home';
    }
}
if ($reqaction==='home') {
    $action='home';
    alert('alert','Vous êtes redirigé vers l\'accueil.');
}
if ($action==='home') {
    redirectHTML('index.php',[]);
}

function loginGetForm($u,$opts) {
    $s='';
    global $formid;
    $fid='form'.$formid;
    $title='Connexion locale';
    $s.='<form id="'.$fid.'" action="login.php" method="POST">';
    $s.='<fieldset><legend>'.$title.'</legend>';
    $s.=simpleHiddenField($fid,'formid',$fid);
    $s.=simpleHiddenField($fid,'action','login');
    $vallogin='';
    if (isset($u['login'])) {
        $vallogin=$u['login'];
    }
    $s.=simpleTextField($fid,'login',$vallogin,'Login','Votre identifiant',false,$opts['invalid']);
    $s.=simplePasswordField($fid,'password','','Mot de passe',$opts['invalid']);
    $s.='</fieldset>';
    $s.='<button type="submit" class="btn btn-primary mt-2">Connexion</button>';
    $s.='</form>';
    $formid=$formid+1;
    return $s;
}


HTMLFile('Connexion',$props);
echo loginGetForm($u,$opts);
closeHTMLFile();