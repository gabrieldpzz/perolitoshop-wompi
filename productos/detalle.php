<?php
session_start();
if (!isset($_SESSION['firebase_email'])) {
    header("Location: /index.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Producto no especificado";
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, c.nombre AS categoria_nombre, c.id AS categoria_id 
                       FROM productos p 
                       LEFT JOIN categorias c ON p.categoria_id = c.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    echo "Producto no encontrado";
    exit;
}

$variantes = [];
if (!empty($producto['variantes_json']) && is_string($producto['variantes_json'])) {
    $variantes = json_decode($producto['variantes_json'], true) ?? [];
}

?>

<link rel="stylesheet" href="/assets/css/detalle-producto.css">

<div class="product-detail-container">
    <div class="breadcrumb">
        <?php if (!empty($producto['categoria_id'])): ?>
            <a href="../productos/categoria.php?id=<?= $producto['categoria_id'] ?>" class="breadcrumb-link">
                <?= htmlspecialchars($producto['categoria_nombre'] ?? 'Categoría') ?>
            </a>
        <?php else: ?>
            <a href="../productos/" class="breadcrumb-link">Productos</a>
        <?php endif; ?>
        <span class="breadcrumb-separator">›</span>
        <span class="breadcrumb-current"><?= htmlspecialchars($producto['nombre']) ?></span>
    </div>

    <div class="product-content">
        <!-- Imagen del producto -->
        <div class="product-image-section">
            <div class="image-container">
                <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                     alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                     class="product-image"
                     id="mainImage">
            </div>
        </div>

        <!-- Información del producto -->
        <div class="product-info-section">
            <div class="product-header">
                <h1 class="product-title"><?= htmlspecialchars($producto['nombre']) ?></h1>
                <div class="product-price">
                    <span class="price-amount"><?= formatearPrecio($producto['precio']) ?></span>
                </div>
            </div>

            <div class="product-description">
                <h3 class="section-title">Descripción</h3>
                <div class="description-text">
                    <?= nl2br(htmlspecialchars($producto['descripcion'])) ?>
                </div>
            </div>

            <!-- Formulario de compra -->
            <div class="purchase-section">
                <form method="post" action="../actions/add_to_cart.php" class="add-to-cart-form" id="cartForm">
                    <input type="hidden" name="product_id" value="<?= $producto['id'] ?>">
                    
                    <!-- Cantidad -->
                    <div class="form-group">
                        <label for="quantity" class="form-label">Cantidad:</label>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="99" class="quantity-input">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>

                    <!-- Variantes -->
                    <?php if (!empty($variantes)): ?>
                        <div class="variants-section">
                            <h4 class="variants-title">Opciones disponibles:</h4>
                            <?php foreach ($variantes as $grupo => $valores): ?>
                                <div class="form-group">
                                    <label for="variant_<?= htmlspecialchars($grupo) ?>" class="form-label">
                                        <?= ucfirst(htmlspecialchars($grupo)) ?>:
                                    </label>
                                    <select name="variant[<?= htmlspecialchars($grupo) ?>]" 
                                            id="variant_<?= htmlspecialchars($grupo) ?>" 
                                            class="form-select" 
                                            required>
                                        <option value="">-- Selecciona <?= htmlspecialchars($grupo) ?> --</option>
                                        <?php foreach ((array)$valores as $opcion): ?>
                                            <option value="<?= htmlspecialchars($opcion) ?>">
                                                <?= htmlspecialchars($opcion) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary btn-large add-to-cart-btn">
                            <span class="btn-icon">🛒</span>
                            Agregar al carrito
                        </button>
                    </div>
                </form>
            </div>

            <!-- Información adicional -->
            <div class="additional-info">
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-icon">🚚</div>
                        <div class="info-content">
                            <h5 class="info-title">Envío gratis</h5>
                            <p class="info-text">En pedidos mayores a $50</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon">🔄</div>
                        <div class="info-content">
                            <h5 class="info-title">Devoluciones</h5>
                            <p class="info-text">30 días para cambios</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon">🛡️</div>
                        <div class="info-content">
                            <h5 class="info-title">Garantía</h5>
                            <p class="info-text">Producto garantizado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <div class="navigation-section">
        <?php if (!empty($producto['categoria_id'])): ?>
            <a href="../productos/categoria.php?id=<?= $producto['categoria_id'] ?>" class="btn btn-secondary">
                <span class="btn-icon">←</span>
                Volver a <?= htmlspecialchars($producto['categoria_nombre'] ?? 'categoría') ?>
            </a>
        <?php else: ?>
            <a href="../productos/" class="btn btn-secondary">
                <span class="btn-icon">←</span>
                Volver a productos
            </a>
        <?php endif; ?>
        <a href="../carrito/index.php" class="btn btn-info">
            <span class="btn-icon">🛒</span>
            Ver carrito
        </a>
    </div>
</div>

<script>
function changeQuantity(delta) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value) || 1;
    const newValue = Math.max(1, Math.min(99, currentValue + delta));
    input.value = newValue;
}

function addToWishlist(productId) {
    // Aquí puedes implementar la lógica para agregar a favoritos
    alert('Producto agregado a favoritos (funcionalidad pendiente)');
}

// Validación del formulario
document.getElementById('cartForm').addEventListener('submit', function(e) {
    const selects = this.querySelectorAll('select[required]');
    let allSelected = true;
    
    selects.forEach(select => {
        if (!select.value) {
            allSelected = false;
            select.style.borderColor = '#FF8C94';
        } else {
            select.style.borderColor = '#B8CFCE';
        }
    });
    
    if (!allSelected) {
        e.preventDefault();
        alert('Por favor, selecciona todas las opciones requeridas.');
        return false;
    }
    
    // Mostrar feedback visual
    const button = this.querySelector('.add-to-cart-btn');
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="btn-icon">⏳</span>Agregando...';
    button.disabled = true;
    
    // Restaurar después de un momento (el formulario se enviará)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
});

// Mejorar la experiencia de los selectores
document.querySelectorAll('.form-select').forEach(select => {
    select.addEventListener('change', function() {
        if (this.value) {
            this.style.borderColor = '#A8E6CF';
        } else {
            this.style.borderColor = '#B8CFCE';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>