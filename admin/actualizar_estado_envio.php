<?php
session_start();
if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

require_once '../includes/db.php';

$pedido_id = $_POST['pedido_id'] ?? null;
$estado_envio = $_POST['estado_envio'] ?? null;

if ($pedido_id && $estado_envio) {
    $stmt = $pdo->prepare("UPDATE pedidos SET estado_envio = ? WHERE id = ?");
    $stmt->execute([$estado_envio, $pedido_id]);
}

header("Location: detalle_pedido.php?id=" . $pedido_id);
exit;
