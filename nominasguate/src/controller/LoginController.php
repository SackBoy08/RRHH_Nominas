<?php
namespace Controller;

use Config\Database;

class LoginController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function showLoginForm(): void {
        // Si viene un mensaje de error, lo mostramos
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);
        require __DIR__ . '/../view/login.php';
    }

    public function authenticate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']   ?? '';

        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Guardamos solo lo esencial en sesión
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'rol'      => $user['rol']
            ];
            header('Location: index.php?route=menu');
            exit;
        }

        // Si falla login, volvemos al form con mensaje
        $error = 'Usuario o contraseña incorrectos';
        require __DIR__ . '/../view/login.php';
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }
}
