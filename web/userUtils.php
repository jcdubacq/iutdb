<?php

function authName($a) {
    $b=['local','système','CAS'];
    if ($a === null)
        return $b;
    if (abs(intval($a))<count($b)) {
        return $b[abs(intval($a))];
    } else return '???';
}

function authIdentification($a) {
    if (strlen($a)>16) {
        return substr($a,0,12).'[...]';
    } else {
        return $a;
    }
}

$allusers=array();

function usersGetTable($login) {
    global $dbh;
    global $allusers;
    $users=array();
    if ($login === null) {
        if (count($allusers)>0) {
            return $allusers;
        }
        $userreq=$dbh->prepare('SELECT * FROM users');
    } else {
        $userreq=$dbh->prepare('SELECT * FROM users WHERE login=:login');
        $userreq->bindValue(':login',$login);
    }
    $userreq->execute();
    $users=$userreq->fetchAll(PDO::FETCH_ASSOC);
    $rolesreq=$dbh->prepare('SELECT roles.role, rolesdef.priority, rolesdef.rolename FROM roles LEFT JOIN rolesdef ON rolesdef.role = roles.role WHERE roles.login=:login ORDER BY rolesdef.priority DESC, roles.role ASC');
    foreach ($users as $k => $v) {
        $t=array();
        $tt=array();
        $login=$v['login'];
        $rolesreq->bindValue(':login',$login);
        $rolesreq->execute();
        $roles=$rolesreq->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roles as $kk => $vv) {
            $t[]=$vv['role'];
            $tt[]=$vv['rolename'];
        }
        $users[$k]['roles']=join($t,', ');
        $users[$k]['rolenames']=join($tt,', ');
    }
    if ($login === null) {
        $allusers=$users;
    }
    return $users;
}

function usersHTML($users) {
    $s='<table class="table">';
    $s.='<thead><tr>';
    $columns=['login'=>'login','fullname'=>'Nom complet','auth'=>'Type d\'utilisateur','authelem' => 'Identification', 'rolenames'=>'Rôles'];
    $columndisplay=['auth'=>'authName', 'authelem' => 'authIdentification'];
    $columnsth=['login' => 1];
    foreach ($columns as $c => $cn) {
        $s.='<th scope="col">'.htmlspecialchars($cn).'</th>';
    }
    $s.='<th scope="col">Modifier</th>';
    $s.='<th scope="col">Supprimer</th>';
    $s.='</tr></thead><tbody>';
    $s.='<tr>';
    foreach ($users as $k => $v) {
        $s.='<tr>';
        $id='';
        foreach ($columns as $c => $cn) {
            $text=$v[$c];
            if (isset($columndisplay[$c])) {
                $text=call_user_func($columndisplay[$c],$text);
            }
            if (isset($columnsth[$c])) {
                $id=$v[$c];
                $s.='<th scope="row">'.htmlspecialchars($text).'</th>';
            } else {
                $s.='<td>'.htmlspecialchars($text).'</td>';
            }
        }
        $s.='<td><a href="user.php?'.http_build_query(array('action'=>'edit','login'=>$id),'','&amp;',PHP_QUERY_RFC3986).'"><i class="fas fa-edit"></i></a></td>';
        $s.='<td><a href="user.php?'.http_build_query(array('action'=>'del','login'=>$id),'','&amp;',PHP_QUERY_RFC3986).'"><i class="fas fa-trash"></i></a></td>';
        $s.='</tr>';
    }
    $s.='</tbody></table>';
    $s.='<a href="user.php?'.http_build_query(array('action'=>'add','login'=>''),'','&amp;',PHP_QUERY_RFC3986).'" class="btn btn-primary" role="button"><i class="fas fa-plus-circle"></i>  Ajouter un utilisateur</a>';
    return $s;
}

function usersAddEditForm($user,$opts) {
    global $formid;
    global $dbh;
    $fid='form'.$formid;
    $s='';
    if (isset($opts['edit']) && $opts['edit']!='') {
        $title='Modifier l\'utilisateur «&nbsp;'.htmlspecialchars($opts['edit']).'&nbsp;»';
        $hidden='submitedit';
    } else {
        $title='Ajouter un utilisateur';
        $hidden='submitadd';
    }
    $s.='<form id="'.$fid.'" action="user.php" method="POST">';
    $s.='<fieldset><legend>'.$title.'</legend>';
    $s.=simpleHiddenField($fid,'formid',$fid);
    $s.=simpleHiddenField($fid,'action',$hidden);
    if (isset($user['login'])) {
        $vallogin=$user['login'];
    } else {
        $vallogin='';
    }
    $readonly=true;
    if ($hidden=='submitadd') {
        $readonly=false;
    }
    $s.=simpleTextField($fid,'login',$vallogin,'Login','identifiant ne commençant pas par le symbole _',$readonly,$opts['invalid']);
    $s.=simpleTextField($fid,'fullname',((isset($user['fullname']))?($user['fullname']):''),'Nom complet','Nom de l\'utilisateur',false,$opts['invalid']);
    $s.=simpleSelect($fid,'auth',array(0,1,2),((isset($user['auth']))?($user['auth']):-1),'Type d\'utilisateur','Choisissez une méthode d\'authentification','authName',$opts['invalid']);
    $s.='</fieldset>';
    $title='Informations complémentaires';
    $s.='<div id="'.$fid.'authelems'.'"><fieldset><legend>'.$title.'</legend>';
    $s.='<p class="authhide auth">Choisissez un type d\'utilisateur.</p>';
    $s.='<div class="authhide auth0">';
    $s.=doublePasswordField($fid,'password','','Mot de passe',$opts['invalid']);
    $s.='</div>';
    $s.='<p class="authhide auth1">Les utilisateurs systèmes ne peuvent pas être authentifiés.</p>';
    $s.='<div class="authhide auth2">';
    $s.=simpleTextField($fid,'casuser',((isset($user['authelem']) && isset($user['auth']) && ($user['auth']==2))?($user['authelem']):''),'Identifiant CAS','Entrez ici l\'identifiant CAS correspondant',false,$opts['invalid']);
    $s.='</div>';
    $s.='</fieldset></div>';
    $s.="\n".'<script>$("#'.$fid.'auth").change(function() { var xv=$("#'.$fid.'auth").val();$("#'.$fid.'authelems .authhide").slideUp();$("#'.$fid.'authelems .auth"+xv).stop().slideDown();}).change()</script>'."\n";
    $title='Rôles';
    $s.='<fieldset><legend>'.$title.'</legend>';
    $rolesreq=$dbh->prepare('SELECT role, rolename FROM rolesdef ORDER BY priority DESC, role ASC');
    $rolesreq->execute();
    $rolesarrayreq=$rolesreq->fetchAll(PDO::FETCH_ASSOC);
    $mainrolesarray=array();
    if (isset($user['roles'])) {
        foreach (explode(', ',$user['roles']) as $k => $v) {
            $mainrolesarray[$v]=1;
        }
    }
    foreach ($rolesarrayreq as $k => $v) {
        $localname='role_'.$v['role'];
        $s.='<div class="custom-control custom-checkbox custom-control-inline">';
        $s.='<input type="checkbox" class="custom-control-input" name="'.$localname.'" id="'.$fid.$localname.'" '.((isset($mainrolesarray[$v['role']]))?'checked ':'').' />';
        $s.='<label class="custom-control-label" for="'.$fid.$localname.'">'.htmlspecialchars($v['rolename']).'</label>';
        $s.='</div>';
    }
    $s.='</fieldset>';
    $s.='<button type="submit" class="btn btn-primary mt-2">'.(($hidden=='submitadd')?'Créer':'Modfier').'</button>';
    $s.='</form>';
    $formid=$formid+1;
    return $s;
}

function usersViewDelForm($u) {
    $s='';
    $columns=['login'=>'login','fullname'=>'Nom complet','auth'=>'Type d\'utilisateur','authelem' => 'Identification', 'rolenames'=>'Rôles'];
    $columndisplay=['auth'=>'authName', 'authelem' => 'authIdentification'];
    foreach ($columns as $c => $v) {
        $s.='<div class="row">';
        $s.='<div class="col-sm-2 font-weight-bold">'.$v.'</div>';
        $text=$u[$c];
        if (isset($columndisplay[$c])) {
            $text=call_user_func($columndisplay[$c],$text);
        }
        $s.='<div class="col-sm-10">'.htmlspecialchars($text).'</div>';
        $s.='</div>';
    }
    $s.='<a href="user.php?'.http_build_query(array('action'=>'canceldel','login'=>$u['login']),'','&amp;',PHP_QUERY_RFC3986).'" class="btn btn-primary" role="button"><i class="fas fa-undo"></i>  Annuler la suppression</a>';
    $s.=' ';
    $s.='<a href="user.php?'.http_build_query(array('action'=>'submitdel','login'=>$u['login']),'','&amp;',PHP_QUERY_RFC3986).'" class="btn btn-danger" role="button"><i class="fas fa-trash"></i>  Supprimer « '.htmlspecialchars($u['login']).' »</a>';
    return $s;
}