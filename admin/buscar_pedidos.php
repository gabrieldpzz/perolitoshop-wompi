<?php
require_once '../includes/db.php';

$busqueda = $_GET['q'] ?? '';
$estado_envio = $_GET['estado'] ?? '';
$condiciones = [];
$params = [];

if ($busqueda !== '') {
    $condiciones[] = "(email LIKE ? OR identificador LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($estado_envio !== '') {
    $condiciones[] = "estado_envio = ?";
    $params[] = $estado_envio;
}

$whereClause = $condiciones ? "WHERE " . implode(" AND ", $condiciones) : "";

$stmt = $pdo->prepare("SELECT * FROM pedidos $whereClause ORDER BY fecha DESC");
$stmt->execute($params);
$pedidos = $stmt->fetchAll();
?>

<table class="pedidos-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Correo</th>
            <th>Identificador</th>
            <th>Monto</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Estado Envío</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($pedidos)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #7f8caa;">
                    No se encontraron pedidos que coincidan con los criterios de búsqueda.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><strong>#<?= $p['id'] ?></strong></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><code><?= htmlspecialchars($p['identificador']) ?></code></td>
                    <td><strong>$<?= number_format($p['monto'], 2) ?></strong></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                    <td><?= ucfirst($p['estado']) ?></td>
                    <td>
                        <span class="estado-<?= strtolower($p['estado_envio']) ?>">
                            <?= ucfirst(htmlspecialchars($p['estado_envio'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="detalle_pedido.php?id=<?= $p['id'] ?>" class="btn-detalles">
                            Ver detalles
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
