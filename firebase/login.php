<?php
require_once '../vendor/autoload.php';
require_once '../includes/db.php';
session_start();

use GuzzleHttp\Client;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $client = new Client();
        $response = $client->post(
            'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=AIzaSyCfJD1P8oQJ53ul5G9H29i-4btxcF6ihc4',
            ['json' => [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => true
            ]]
        );

        $data = json_decode($response->getBody(), true);

        $_SESSION['firebase_uid'] = $data['localId'];
        $_SESSION['firebase_email'] = $data['email'];

        // Buscar o registrar usuario por UID
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE firebase_uid = ?");
        $stmt->execute([$data['localId']]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $stmt = $pdo->prepare("INSERT INTO usuarios (firebase_uid, email, rol) VALUES (?, ?, 'usuario')");
            $stmt->execute([$data['localId'], $data['email']]);
            $rol = 'usuario';
        } else {
            $rol = $usuario['rol'];
        }

        $_SESSION['rol'] = $rol;

        if ($rol === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: ../productos/index.php');
        }
        exit;

    } catch (Exception $e) {
        header("Location: ../index.php?error=Login fallido");
        exit;
    }
}
