<?php
ob_start();
session_start();
if (!isset($_SESSION['msg_success'])) {
    $_SESSION['msg_success']=array();
}
if (!isset($_SESSION['msg_alert'])) {
    $_SESSION['msg_alert']=array();
}

$appname="ALD";
$appnamedev="2018-01";
$props=array();
$props['alert']=array();
$props['success']=array();
$myargs=array();

require('db.php');

function alert($where,$string) {
    global $props;
    $props[$where][]=$string;
}

function redirectHTML($where,$array) {
    $query='';
    $querya=array();
    global $props;
    foreach ($props['success'] as $k) {
        $_SESSION['msg_success'][]=$k;
    }
    foreach ($props['alert'] as $k) {
        $_SESSION['msg_alert'][]=$k;
    }
    foreach ($array as $k => $v) {
        if ($k == 'success') {
            if (!is_array($v)) {
                $_SESSION['msg_success'][]=$v;
            } else {
                foreach ($v as $l) {
                    $_SESSION['msg_success'][]=$v;
                }
            }
        } elseif ($k == 'alert') {
            if (!is_array($v)) {
                $_SESSION['msg_alert'][]=$v;
            } else {
                foreach ($v as $l) {
                    $_SESSION['msg_alert'][]=$v;
                }
            }
        } else {
            $querya[$k]=$v;
        }
    }
    if (count($querya)>0) {
        $where .= '?'.http_build_query($querya);
    }
    header('Location: '.$where);
    exit();
}

function HTMLFile($title,$props=array(),$filepath="./") {
    global $permlist;
    global $login;
    global $appname;
    global $appnamedev;
    global $myargs;
    global $loginfullname;
    global $roleactive;
    global $rolename;
    global $role;
    global $permissions;
    global $props;

    foreach ($_SESSION['msg_alert'] as $v) {
        alert('alert',$v);
    }
    $_SESSION['msg_alert']=array();
    foreach ($_SESSION['msg_success'] as $v) {
        alert('success',$v);
    }
    $_SESSION['msg_success']=array();

    echo '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><link rel="stylesheet" href="'.$filepath.'/css/bootstrap.min.css" type="text/css"/><link rel="stylesheet" type="text/css" href="'.$filepath.'/css/local.css" /><script src="'.$filepath.'/js/jquery.js"></script><script defer src="'.$filepath.'/js/fontawesome-all.js"></script><script src="'.$filepath.'/js/bootstrap.bundle.min.js"></script><script src="'.$filepath.'/js/local.js"></script>';
//    echo '<script type="text/javascript" src="'.$filepath.'/js/jquery.tablesorter.min.js"></script><script type="text/javascript" src="'.$filepath.'/js/jquery.tablesorter.widgets.min.js"></script><link rel="stylesheet" href="'.$filepath.'/css/theme.bootstrap.css"/>';
    if (isset($props['scripts'])) {
        echo $props['scripts'];
    }
    echo '<title>'.htmlspecialchars($title).'</title></head><body id="body">'."\n";

    $home='';
    if (isset($props['althome'])) {
        $home=$props['althome'];
    }
    echo '<nav class="navbar navbar-inverse navbar-expand-sm navbar-dark bg-dark hidden-print" role="navigation">';
    echo '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTop" aria-controls="navbarTop" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>';
    echo '<a href="'.$home.'" class="navbar-brand" title="Accueil de '.$appname.' ('.$appnamedev.')">'.$appname.'</a>';
    echo '<div class="collapse navbar-collapse" id="navbarTop">';
    echo '<ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="#">Accueil <span class="sr-only">(current)</span></a>
      </li>';
    $links=[];
    if (allowed('manageusers')) {
        $links['users.php']='Gestion des utilisateurs';
    }
    if (count($links)>0) {
        echo '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Actions</a>';
        echo '<div class="dropdown-menu">';
        foreach ($links as $k => $v) {
            echo '<a class="dropdown-item" href="'.htmlspecialchars($k,ENT_QUOTES).'">'.htmlspecialchars($v).'</a>';
        }
        echo '</div>';
        echo '</li>';
    }
    echo '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="fas '.(($login==='__anon__')?'fa-question-circle':'fa-user').'"></i> '.htmlspecialchars($loginfullname).'</a>';
    echo '<div class="dropdown-menu">';
    foreach ($rolename as $k => $v) {
        echo '<a class="dropdown-item" href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES).'?preferred_role='.htmlspecialchars($k,ENT_QUOTES).'">'.((isset($roleactive[$k]))?(($k==$role)?('<span style="font-weight:bold;">'):('<span style="font-style:italic;">')):('<span>')).$v.'</span></a>';
    }
    echo '<div class="dropdown-divider"></div>';
    echo '<a class="dropdown-item" href="'.(($login==='__anon__')?'login.php':'logout.php').'">'.(($login==='__anon__')?'Se connecter':'Se déconnecter').'</a>';
    echo '</div>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
    echo '</nav>';

    echo '<div class="container">',"\n";
    $subtitle=(isset($props['subtitle'])?'&nbsp;<small>'.htmlspecialchars($props['subtitle']).'</small>':'');
    if (isset($props['alttitle'])) {
        echo '<div class="header"><h1>'.htmlspecialchars($props['alttitle']).$subtitle.'</h1>'.$props['baseheader']."</div>\n";
    } elseif (isset($props['jumbotron'])) {
        echo '<div class="jumbotron"><h1>'.$props['jumbotron'].$subtitle.'</h1>'.$props['baseheader']."</div>\n";
    } else {
        echo '<h1>'.htmlspecialchars($title).$subtitle."</h1>\n";
    }
    if (isset($props['alert'])) {
        foreach ($props['alert'] as $x) {
            alertHTML($x);
        }
    }
    if (isset($props['success'])) {
        foreach ($props['success'] as $x) {
            alertHTML($x,'success');
        }
    }
/* XXX */
    echo '</div>',"\n";
    echo '<div class="container-fluid">',"\n";
    /* echo '<div class="row">'; */
    /* echo '<div class="hidden-print col-sm-2 col-xs-12">'; */
    /* echo '<div id="navbarul-parent">'; */
    /* echo '<p class="buttonToTop"><span class="buttonToTop glyphicon glyphicon-arrow-up buttonToTop">Retour</span></p>'; */
    /* echo '<ul id="navbarul" class="nav-toc" role="navigation" >'; */
    /* echo '</ul>'; */
    /* echo '</div>'; */
    /* echo '</div>'; */
    /* echo '<div class="col-sm-10 col-xs-12">'; */
    echo '<div class="col-sm-12 col-xs-12">';
    return;
}

function closeHTMLFile() {
    echo '</div>'; // column
    echo '</div>'; // row
    echo "</div></body></html>\n";
    exit;
}

function alertHTML($text,$color='warning') {
    $icons=array('warning' => 'thumbs-down', 'success' => 'thumbs-up');
    echo '<div class="alert alert-'.$color.' alert-dismissable">';
    echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
    if (isset($icons[$color])) {
        echo '<span class="glyphicon glyphicon-'.$icons[$color].'"></span> ';
    }
    echo $text.'</div>';
}

function timeToText($time) {
    $delta=time()-$time;
    if ($delta==0) return 'maintenant';
    $deltaminutes=$delta%3600;
    $deltahours=intval(($delta-$deltaminutes)/3600);
    $deltasecondes=$deltaminutes%60;
    $deltaminutes=intval(($deltaminutes-$deltasecondes)/60);
    if ($deltahours>0) {
        return ($deltahours.'h'.($deltaminutes>0?($deltaminutes.'m'):''));
    }
    if ($deltaminutes>0) {
        return ($deltaminutes.'m'.($deltasecondes>0?($deltasecondes.'s'):''));
    }
    return ($deltasecondes.'s');
}
