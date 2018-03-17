<?php
global $dbh;
global $props;
$login='';
$loginfullname='';
$preferred_role='';

function allowed($k) {
    global $permissions;
    return isset($permissions[$k]);
}

$marklogin=false;
if (isset($_SESSION['USER'])) {
    $login=$_SESSION['USER'];
} else {
    $login='__anon__';
}
if (isset($_SESSION['ROLE'])) {
    $preferred_role=$_SESSION['ROLE'];
}

$origrole=$preferred_role;

if (isset($_REQUEST['preferred_role'])) {
    $preferred_role=$_REQUEST['preferred_role'];
}

while ($loginfullname==='') {
    $userreq=$dbh->prepare('SELECT login, fullname FROM users WHERE login=:login');
    $userreq->bindValue(':login',$login);
    $userreq->execute();
    $userarray=$userreq->fetchAll(PDO::FETCH_ASSOC);
    if (count($userarray)>0) {
        $loginfullname=$userarray[0]['fullname'];
    } else {
        if ($login == '__anon__') {
            $loginfullname='???'; /* No database? */
        } else {
            alert('alert','Incident d\'authentification.');
            $login='__anon__';
            $marklogin=true;
        }
    }
}

$rolesreq=$dbh->prepare('SELECT rolesequiv.eqrole, rolesdef.priority, rolesdef.rolename FROM rolesequiv LEFT JOIN roles ON rolesequiv.role = roles.role LEFT JOIN rolesdef ON rolesdef.role = rolesequiv.eqrole WHERE roles.login=:login ORDER BY rolesdef.priority DESC, rolesequiv.eqrole ASC');
$rolesreq->bindValue(':login',$login);
$rolesreq->execute();
$rolesarrayreq=$rolesreq->fetchAll(PDO::FETCH_ASSOC);
$rolepriority=[];
$rolename=[];
$roleactive=[];
$role='';
foreach ($rolesarrayreq as $k) {
    if ($role==='') {
        $role=$k['eqrole'];
    }
    $rolepriority[$k['eqrole']]=$k['priority'];
    $rolename[$k['eqrole']]=$k['rolename'];
}
if (isset($rolename[$preferred_role])) {
    $role=$preferred_role;
}

$rolesreq=$dbh->prepare('SELECT eqrole FROM rolesequiv WHERE role=:role ORDER BY eqrole ASC');
$rolesreq->bindValue(':role',$role);
$rolesreq->execute();
$rolesarrayreq=$rolesreq->fetchAll(PDO::FETCH_ASSOC);
foreach ($rolesarrayreq as $k => $v) {
    $roleactive[$v['eqrole']]=1;
}

$permreq=$dbh->prepare('SELECT perm FROM permissions LEFT JOIN rolesequiv ON permissions.role = rolesequiv.eqrole WHERE rolesequiv.role=:role');
$permreq->bindValue(':role',$role);
$permreq->execute();
$permissionsarrayreq=$permreq->fetchAll(PDO::FETCH_ASSOC);
$permissions=[];
foreach ($permissionsarrayreq as $k => $v) {
    $permissions[$v['perm']]=1;
}

if ($marklogin) {
    $_SESSION['USER']=$login;
    alert('success','Connexion en tant qu\'utilisateur «&nbsp;'.htmlspecialchars($loginfullname).'&nbsp;»');
}
if ($origrole !== $role ) {
    alert('success','Connexion en tant que rôle «&nbsp;'.htmlspecialchars($role).'&nbsp;»');
}

$_SESSION['ROLE']=$role;
