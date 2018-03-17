<?php
require('header.php');
require('userUtils.php');
require('formUtils.php');

global $props;
$formid=0;

$reqaction='home';
if (isset($_REQUEST['action'])) {
    $reqaction=$_REQUEST['action'];
}

$u=array();

if ($reqaction==='add' or $reqaction==='submitadd') {
    if (allowed('manageusers')) {
        $action='add';
    } else {
        alert('alert','Vous n\'êtes pas autorisé à créer un utilisateur.');
        $reqaction='home';
    }
}

if ($reqaction==='del' or $reqaction==='submitdel') {
    if (allowed('manageusers')) {
        $action='del';
    } else {
        alert('alert','Vous n\'êtes pas autorisé à supprimer un utilisateur.');
        $reqaction='home';
    }
}

if ($reqaction==='canceldel') {
    alert('alert','La suppression a été annulée.');
    $action='list';
}

if ($reqaction==='edit' or $reqaction==='submitedit') {
    if (isset($_REQUEST['login'])) {
        $login=$_REQUEST['login'];
        $users=usersGetTable($login);
        if (count($users)>0) {
            $u=$users[0];
            if (allowed('manageusers')) {
                $action='edit';
            } else {
                alert('alert','Vous n\'êtes pas autorisé à modifier les utilisateurs.');
                $reqaction='home';
            }
        } else {
            alert('alert','L\'identifiant à modifier n\'a pas été trouvé.');
            $reqaction='home';
        }
    } else {
        alert('alert','L\'identifiant à modifier n\'a pas été précisé.');
        $reqaction='home';
    }
}

$opts=array();
$opts['invalid']=array();

global $props;
if ($reqaction==='submitadd' or $reqaction==='submitedit') {
    /* Submitted data */
    /* Modify $u according to values found in $_REQUEST */
    foreach (['login','fullname','password','passwordconfirmation','casuser'] as $v) {
        if (isset($_REQUEST[$v])) {
            $u[$v]=$_REQUEST[$v];
        }
    }
    foreach (['auth'] as $v) {
        if (isset($_REQUEST[$v]) && ($_REQUEST[$v]!=='')) {
            $u[$v]=intval($_REQUEST[$v]);
        }
    }
    $rolesreq=$dbh->prepare('SELECT role FROM rolesdef ORDER BY priority DESC, role ASC');
    $rolesreq->execute();
    $rolesarrayreq=$rolesreq->fetchAll(PDO::FETCH_ASSOC);

    /* transfer roles */
    $transferredroles=array();
    foreach ($rolesarrayreq as $k => $v) {
        $r=$v['role'];
        if (isset($_REQUEST['role_'.$r])) {
            $transferredroles[]=$r;
        }
    }
    if (count($transferredroles)>0) {
        $u['roles']=join(', ',$transferredroles);
    }
    if (isset($u['auth']) && $u['auth']==2 && isset($_REQUEST['casuser'])) {
        $u['authelem']=$_REQUEST['casuser'];
    }

    /* Check validity of every field */
    $valid=true;
    /* login */
    $opts['invalid']['login']=array();
    if (!isset($u['login']) || $u['login']==='') {
        $valid=false;
        $opts['invalid']['login'][]='L\'identifiant est obligatoire.';
    } else {
        $users=usersGetTable(null);
        $found=false;
        foreach ($users as $k => $v) {
            if ($v['login']==$u['login']) {
                $found=true;
            }
        }
        if ($reqaction === 'submitadd') {
            if ($found) {
                $valid=false;
                $opts['invalid']['login'][]='L\'identifiant «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» est déjà utilisé.';
            }
        } else {
            if (!$found) {
                $valid=false;
                $opts['invalid']['login'][]='L\'identifiant «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» n\'existe pas.';
            }
        }
        if ($u['login'][0]==='_') {
            $valid=false;
            $opts['invalid']['login'][]='L\'identifiant «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» est réservé et ne peut pas être '.(($reqaction==='submitadd')?'créé':'modifié').'.';
        }
    }
    /* fullname */
    $opts['invalid']['fullname']=array();
    if (!isset($u['fullname']) || $u['fullname']==='') {
        $valid=false;
        $opts['invalid']['fullname'][]='Le nom complet est obligatoire.';
    }
    /* Check validity of auth+password/passwordconfirmation or auth+casuser */
    $opts['invalid']['auth']=array();
    if (!isset($u['auth']) || intval($u['auth'])<0 || intval($u['auth'])>2 ) {
        $valid=false;
        $opts['invalid']['auth'][]='La méthode d\'authentification est obligatoire.';
    } else {
        $auth=intval($u['auth']);
        if ($auth===0) {
            $lvalid=true;
            $opts['invalid']['password']=array();
            $opts['invalid']['passwordconfirmation']=array();
            if (!isset($u['password']) || $u['password']==='') {
                $lvalid=false;
                $opts['invalid']['password'][]='Le mot de passe est obligatoire.';
            }
            if (!isset($u['passwordconfirmation']) || $u['passwordconfirmation']==='') {
                $mvalid=false;
                $opts['invalid']['passwordconfirmation'][]='Le mot de passe doit être confirmé.';
            }
            if ($lvalid) {
                $password=$u['password'];
                $passwordconfirmation=$u['passwordconfirmation'];
                if ($password !== $passwordconfirmation) {
                    $lvalid=false;
                    $opts['invalid']['password'][]='Les mots de passe sont différents.';
                    $opts['invalid']['passwordconfirmation'][]='Les mots de passe sont différents.';
                }
            }
            if ($valid) {
                $valid=$lvalid;
            }
            if ($valid) {
                $u['authelem']=hashPassword($password);
            }
        } elseif ($auth===2) {
            $opts['invalid']['casuser']=array();
            if (!isset($u['casuser']) || $u['casuser']==='') {
                $valid=false;
                $opts['invalid']['casuser'][]='L\'identifiant CAS est obligatoire.';
            }
            if ($valid) {
                $u['authelem']=$u['casuser'];
            }
        } else {
            $u['authelem']='';
        }
    }
    
    /* If all fields are valid, modify/add data, redirect to users.php */
    if ($valid) {
        if ($reqaction === 'submitadd') {
            alert('success','Utilisateur «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» créé');
            $x=$dbh->prepare('INSERT INTO users (login, authelem, fullname, auth) VALUES (:login,:password, :fullname, :auth)');
            $x->bindValue(':login',$u['login']);
            $x->bindValue(':password',$u['authelem']);
            $x->bindValue(':fullname',$u['fullname']);
            $x->bindValue(':auth',$u['auth']);
            $x->execute();
            $x=$dbh->prepare('INSERT INTO roles (login, role) VALUES (:login,:role)');
            $x->bindValue(':login',$u['login']);
            foreach ($transferredroles as $k => $v) {
                $x->bindValue(':role',$v);
                $x->execute();
            }
        } else {
            alert('success','Utilisateur «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» modifié');
            $x=$dbh->prepare('UPDATE users SET login=:login, authelem=:password, fullname=:fullname, auth=:auth WHERE login=:login');
            $x->bindValue(':login',$u['login']);
            $x->bindValue(':password',$u['authelem']);
            $x->bindValue(':fullname',$u['fullname']);
            $x->bindValue(':auth',$u['auth']);
            $x->execute();
            $x=$dbh->prepare('DELETE FROM roles WHERE login=:login');
            $x->bindValue(':login',$u['login']);
            $x->execute();
            $x=$dbh->prepare('INSERT INTO roles (login, role) VALUES (:login,:role)');
            $x->bindValue(':login',$u['login']);
            foreach ($transferredroles as $k => $v) {
                $x->bindValue(':role',$v);
                $x->execute();
            }
        }
        redirectHTML('users.php',[]);
    } else {
        alert('alert','Données invalides');
    }
    /* If any field is not valid, continue, the form will redisplay */
}

if ($reqaction==='del' || $reqaction==='submitdel') {
    if (isset($_REQUEST['login'])) {
        $u['login']=$_REQUEST['login'];
        if ($reqaction==='submitdel') {
            alert('success','Utilisateur «&nbsp;'.htmlspecialchars($u['login']).'&nbsp;» supprimé');
            $x=$dbh->prepare('DELETE FROM users WHERE login=:login');
            $x->bindValue(':login',$u['login']);
            $x->execute();
            redirectHTML('users.php',[]);
        } else {
            $us=usersGetTable($u['login']);
            if (count($us)>0) {
                $u=$us[0];
            } else {
                alert('alert','L\'utilisateur n\'existe pas');
                $action='list';
            }
        }
    } else {
        alert('alert','Pas d\'identifiant précisé');
        $action='list';
    }
}

if ($reqaction==='home') {
    $action='home';
    alert('alert','Vous êtes redirigé vers l\'accueil.');
}

if ($action==='home') {
    redirectHTML('index.php',[]);
}

if ($action==='list') {
    redirectHTML('users.php',[]);
}

HTMLFile('Gestion des utilisateurs',$props);

if ($action==='add') {
    echo usersAddEditForm($u,$opts);
} elseif ($action==='edit') {
    $opts['edit']=$u['login'];
    echo usersAddEditForm($u,$opts);
} elseif ($action==='del') {
    echo usersViewDelForm($u);
}

closeHTMLFile();