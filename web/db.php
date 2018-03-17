<?php

global $props;
global $createmode;

$currenttablesmax=4;
$currenttables=0;
$origtables=-1;

$dbdir="/var/www/db";

function updateTables($v) {
    global $dbh;
    $x=$dbh->prepare("INSERT INTO tableversion (version, date) VALUES (:version,datetime('now'))");
    $x->bindValue(':version',$v);
    $x->execute();
    sleep(1);
}

function hashPassword($a) {
    return hash("sha256","root"."graindesel".$a,false);
}

function createTables($v) {
    global $dbh;
    try {
        if ($v < 1) {
            $x=$dbh->prepare('DROP TABLE IF EXISTS tableversion');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE tableversion ( version INT, date DATETIME )');
            $x->execute();
            updateTables(1);
        }
        if ($v < 2) {
            foreach (['users','rolesdef','roles','rolesequiv'] as $v) {
                $x=$dbh->prepare('DROP TABLE IF EXISTS '.$v);
                $x->execute();
            }
            $x=$dbh->prepare('CREATE TABLE users ( login VARCHAR(60) NOT NULL PRIMARY KEY, authelem VARCHAR(64), fullname VARCHAR(60), auth INTEGER )');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE rolesdef ( role VARCHAR(20) NOT NULL PRIMARY KEY, rolename VARCHAR(40), priority INTEGER)');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE roles ( login VARCHAR(60), role VARCHAR(20), FOREIGN KEY(login) REFERENCES users(login) ON DELETE CASCADE, FOREIGN KEY(role) REFERENCES rolesdef(role) )');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE rolesequiv ( role VARCHAR(20), eqrole VARCHAR(20), FOREIGN KEY(role) REFERENCES rolesdef(role), FOREIGN KEY(eqrole) REFERENCES rolesdef(role))');
            $x->execute();
            $x=$dbh->prepare('INSERT INTO rolesdef (role, rolename, priority) VALUES (:role,:rolename,:priority)');
            $prio=['admin' => 100,'guest' => 0, 'authuser' => 10];
            $rolenames=['admin' => 'Administrateur', 'guest' => 'Anonyme', 'authuser' => 'Utilisateur'];
            foreach ($prio as $k => $v) {
                $x->bindValue(':role',$k);
                $x->bindValue(':rolename',(isset($rolenames[$k])?$rolenames[$k]:$k));
                $x->bindValue(':priority',$v);
                $x->execute();
            }
            $x=$dbh->prepare('INSERT INTO rolesdef (role, priority) VALUES (:role,:priority)');
            $rolesequivtable=[[ 'admin'=> 'guest'], ['admin' => 'authuser'], ['authuser' => 'guest']];
            $x=$dbh->prepare('INSERT INTO rolesequiv (role, eqrole) VALUES (:role,:eqrole)');
            foreach ($prio as $k => $v) {
                $x->bindValue(':role',$k);
                $x->bindValue(':eqrole',$k);
                $x->execute();
            }
            foreach ($rolesequivtable as $t) {
                foreach ($t as $k => $v) {
                    $x->bindValue(':role',$k);
                    $x->bindValue(':eqrole',$v);
                    $x->execute();
                }
            }
            $x=$dbh->prepare('INSERT INTO users (login, authelem, fullname, auth) VALUES (:login,:password, :fullname, :auth)');
            $x->bindValue(':login','root');
            $pass=hashPassword("admin");
            $x->bindValue(':password',$pass);
            $x->bindValue(':fullname','Administrateur');
            $x->bindValue(':auth',0);
            $x->execute();
            $x->bindValue(':login','__anon__');
            $x->bindValue(':password','');
            $x->bindValue(':fullname','non identifié');
            $x->bindValue(':auth',1);
            $x->execute();
        
            $x=$dbh->prepare('INSERT INTO roles (login, role) VALUES (:login,:role)');
            $x->bindValue(':login','root');
            $x->bindValue(':role','admin');
            $x->execute();
            $x->bindValue(':login','__anon__');
            $x->bindValue(':role','guest');
            $x->execute();
            updateTables(2);
        }
        if ($v < 3) {
            $x=$dbh->prepare('DROP TABLE IF EXISTS journal');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE journal ( login VARCHAR(60), date DATETIME, FOREIGN KEY(login) REFERENCES users(login) )');
            updateTables(3);
        }
        if ($v < 4) {
            $x=$dbh->prepare('DROP TABLE IF EXISTS permissions');
            $x->execute();
            $x=$dbh->prepare('CREATE TABLE permissions ( role VARCHAR(60), perm VARCHAR(60), FOREIGN KEY(role) REFERENCES rolesdef(role) )');
            $permtable=[[ 'admin'=> 'manageusers']];
            $x->execute();
            $x=$dbh->prepare('INSERT INTO permissions (role, perm) VALUES (:role,:perm)');
            foreach ($permtable as $t) {
                foreach ($t as $k => $v) {
                    $x->bindValue(':role',$k);
                    $x->bindValue(':perm',$v);
                    $x->execute();
                }
                updateTables(4);
            }
        }
    } catch(PDOException $x) {
        alert('alert','La base de données n\'a pas pu être ouverte (phase 2) : '.$x->getMessage()."\n".$x->getTraceAsString());
    }
}

if (!is_writable($dbdir.'/')) {
    alert('alert','The directory $dbdir is not writable. All database operations may fail.');
} else {
    try {
        $dbh = new PDO('sqlite:'.$dbdir.'/db.sql');
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->query('PRAGMA foreign_keys=ON');
    } catch(PDOException $x) {
        alert('alert','La base de données n\'a pas pu être ouverte (phase 1)');
        $dbh=-1;
    }
    if ($dbh !== -1) {
        try {
            $req='SELECT * FROM tableversion ORDER BY date DESC LIMIT 1';
            $versionreq=$dbh->prepare($req);
        } catch (PDOException $x) {
            alert('alert','The tables could not be opened (not existent).');
            createTables(0);
            $origtables=0;
            $versionreq=$dbh->prepare($req);
        }
        try {
            if ($versionreq!==FALSE) {
                $versionreq->execute();
                $results=$versionreq->fetchAll();
                if (count($results)>0) {
                    $currenttables=intval($results[0][0]);
                    if ($origtables<0) $origtables=$currenttables;
                }
                if ($currenttables<$currenttablesmax) {
                    createTables($currenttables);
                    $versionreq=$dbh->prepare($req);
                    if ($versionreq!==FALSE) {
                        $versionreq->execute();
                        $results=$versionreq->fetchAll();
                        if (count($results)>0) {
                            $currenttables=intval($results[0][0]);
                        }
                    }
                }
                if ($origtables!=$currenttablesmax) {
                    if ($currenttables<$currenttablesmax) {
                        alert('alert','Tables were not upgraded.');
                    } else {
                        alert('success','Tables were upgraded from version '.$origtables.' to '.$currenttablesmax.'.');
                    }
                }
//                alert('success','La version actuelle des tables est '.$currenttables);
            }
        } catch(PDOException $x) {
            alert('alert','La base de données n\'a pas pu être ouverte (phase 2): '.$x->getMessage()."\n".$x->getTraceAsString());
        }
    }
}

require('roles.php');

