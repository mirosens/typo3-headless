<?php

/**
 * Script zum Erstellen eines Frontend-Users für FAHN-CORE
 * 
 * Usage: ddev exec vendor/bin/typo3 create-frontend-user
 * Oder: ddev exec php vendor/bin/typo3 create-frontend-user
 */

// Verwende TYPO3 CLI Command
// Da TYPO3 kein direktes CLI-Command für Frontend-User hat, erstellen wir ein SQL-Script

$username = 'testpolizist';
$password = 'Polizei2024!';
$email = 'test@polizei.de';
$name = 'Test Polizist';

// TYPO3 verwendet bcrypt für Passwörter (Standard in TYPO3 13)
// Wir erstellen einen bcrypt-Hash mit password_hash()
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// Timestamp
$now = time();

// SQL-Statement
$sql = <<<SQL
INSERT INTO fe_users 
(pid, tstamp, crdate, deleted, disable, username, password, email, name, usergroup) 
VALUES 
(0, {$now}, {$now}, 0, 0, '{$username}', '{$hashedPassword}', '{$email}', '{$name}', '')
ON DUPLICATE KEY UPDATE 
    password = '{$hashedPassword}',
    email = '{$email}',
    name = '{$name}',
    tstamp = {$now};
SQL;

// Speichere SQL in temporäre Datei
$sqlFile = '/tmp/create-frontend-user.sql';
file_put_contents($sqlFile, $sql);

echo "SQL-Script erstellt: {$sqlFile}\n";
echo "Führe aus mit: ddev mysql < {$sqlFile}\n";
echo "\n";
echo "SQL-Statement:\n";
echo $sql . "\n";

