<?php
require('header.php');
global $props;
HTMLFile('test',$props);
echo '<pre>';
var_dump($login);
print_r($roleactive);
var_dump($rolename);
echo '</pre>';
closeHTMLFile();