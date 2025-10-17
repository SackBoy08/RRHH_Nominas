<?php
require_once __DIR__ . '/../config/database.php';

use Config\Database;

$db = Database::getInstance()->getConnection();
$username = 'antony';
$rawPassword = 'jaziel123';  // Cámbialo luego por algo más seguro
$passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare('INSERT INTO usuarios (username, password_hash, rol) VALUES (?, ?, ?)');
$stmt->execute([$username, $passwordHash, 'admin']);

echo "Usuario 'antony' creado con contraseña 'jaziel123'.\n";
