<?php
require('header.php');
require('userUtils.php');

global $props;
$formid=0;

$action='home';
if (isset($_REQUEST['action'])) {
    $reqaction=$_REQUEST['action'];
} else {
    $reqaction='list';
}
if ($reqaction==='list') {
    if (allowed('manageusers')) {
        $action='list';
    } else {
        alert('alert','Vous n\'êtes pas autorisé à voir les utilisateurs.');
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

HTMLFile('Gestion des utilisateurs',$props);
$users=usersGetTable(null);
echo usersHTML($users);
closeHTMLFile();