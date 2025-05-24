<?php
session_start();
if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /index.php");
    exit;
}
require_once '../includes/db.php';

// 🧠 Manejo del POST antes de cualquier salida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $descuento = $_POST['descuento'];
    $fecha = $_POST['fecha_expiracion'];
    $uso_max = $_POST['uso_maximo'];

    $stmt = $pdo->prepare("INSERT INTO cupones (codigo, descuento_porcentaje, fecha_expiracion, uso_maximo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$codigo, $descuento, $fecha, $uso_max]);

    header("Location: cupones.php");
    exit;
}

// ✅ Ahora sí, incluye el header visual después
require_once '../includes/header.php';

$cupones = $pdo->query("SELECT * FROM cupones")->fetchAll();
?>

<link rel="stylesheet" href="/assets/css/cupones.css">

<div class="admin-container">
    <div class="page-header">
        <h2>Gestión de Cupones</h2>
        <p class="page-subtitle">Crea y administra cupones de descuento para tu tienda</p>
    </div>

    <div class="form-card">
        <div class="card-header">
            <h3>Crear Nuevo Cupón</h3>
            <p>Completa los datos para generar un cupón de descuento</p>
        </div>
        
        <form method="post" class="coupon-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="codigo">Código del cupón</label>
                    <input name="codigo" id="codigo" placeholder="Ej: DESCUENTO20" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="descuento">Porcentaje de descuento</label>
                    <input name="descuento" id="descuento" type="number" min="1" max="100" placeholder="20" required class="form-input">
                    <span class="input-suffix">%</span>
                </div>
                
                <div class="form-group">
                    <label for="fecha_expiracion">Fecha de expiración</label>
                    <input name="fecha_expiracion" id="fecha_expiracion" type="date" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="uso_maximo">Usos máximos</label>
                    <input name="uso_maximo" id="uso_maximo" type="number" min="1" placeholder="100" required class="form-input">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <span class="btn-icon">+</span>
                Crear Cupón
            </button>
        </form>
    </div>

    <div class="table-card">
        <div class="card-header">
            <h3>Cupones Existentes</h3>
            <p>Lista de todos los cupones creados y su estado actual</p>
        </div>
        
        <div class="table-container">
            <?php if (empty($cupones)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎫</div>
                    <h4>No hay cupones creados</h4>
                    <p>Crea tu primer cupón usando el formulario de arriba</p>
                </div>
            <?php else: ?>
                <table class="cupones-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descuento</th>
                            <th>Fecha de Expiración</th>
                            <th>Uso</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cupones as $c): ?>
                            <?php
                            $hoy = date('Y-m-d');
                            $expirado = $c['fecha_expiracion'] < $hoy;
                            $agotado = $c['usados'] >= $c['uso_maximo'];
                            $activo = !$expirado && !$agotado;
                            ?>
                            <tr class="<?= $activo ? 'row-active' : 'row-inactive' ?>">
                                <td>
                                    <code class="coupon-code"><?= htmlspecialchars($c['codigo']) ?></code>
                                </td>
                                <td>
                                    <span class="discount-badge"><?= $c['descuento_porcentaje'] ?>%</span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($c['fecha_expiracion'])) ?></td>
                                <td>
                                    <div class="usage-info">
                                        <span class="usage-numbers"><?= $c['usados'] ?>/<?= $c['uso_maximo'] ?></span>
                                        <div class="usage-bar">
                                            <div class="usage-progress" style="width: <?= ($c['uso_maximo'] > 0) ? ($c['usados'] / $c['uso_maximo'] * 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($expirado): ?>
                                        <span class="status-badge status-expired">Expirado</span>
                                    <?php elseif ($agotado): ?>
                                        <span class="status-badge status-exhausted">Agotado</span>
                                    <?php else: ?>
                                        <span class="status-badge status-active">Activo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
