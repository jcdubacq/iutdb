<?php
require('header.php');

unset($_SESSION['USER']);
alert('success','Vous avez été déconnecté.');
alert('alert','Vous êtes redirigé vers l\'accueil.');
redirectHTML('index.php',[]);
