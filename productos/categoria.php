<?php
session_start();
if (!isset($_SESSION['firebase_email'])) {
    header("Location: /login.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($categoria_id <= 0) {
    echo "<p style='padding:20px;'>Categoría no válida.</p>";
    require_once '../includes/footer.php';
    exit;
}

// Obtener nombre de la categoría
$stmt = $pdo->prepare("SELECT nombre FROM categorias WHERE id = ?");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();
$categoria_nombre = $categoria ? $categoria['nombre'] : 'Categoría';

// Orden y filtro
$orden = $_GET['orden'] ?? '';
$min = $_GET['min'] ?? '';
$max = $_GET['max'] ?? '';

$sql = "SELECT * FROM productos WHERE categoria_id = ?";
$params = [$categoria_id];

if (is_numeric($min)) {
    $sql .= " AND precio >= ?";
    $params[] = $min;
}
if (is_numeric($max)) {
    $sql .= " AND precio <= ?";
    $params[] = $max;
}

switch ($orden) {
    case 'nombre_asc':
        $sql .= " ORDER BY nombre ASC";
        break;
    case 'nombre_desc':
        $sql .= " ORDER BY nombre DESC";
        break;
    case 'precio_asc':
        $sql .= " ORDER BY precio ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY precio DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>

<link rel="stylesheet" href="/assets/css/categoria.css">

<div class="category-container">
    <!-- Header de la categoría -->
    <div class="page-header">
        <h2 class="page-title"><?= htmlspecialchars($categoria_nombre) ?></h2>
        <p class="page-subtitle">
            <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?> encontrado<?= count($productos) !== 1 ? 's' : '' ?>
        </p>
    </div>

    <!-- Filtros y ordenamiento -->
    <div class="filters-card">
        <form method="get" class="filters-form" id="filtersForm">
            <input type="hidden" name="id" value="<?= $categoria_id ?>">

            <div class="filters-row">
                <!-- Ordenamiento -->
                <div class="filter-group">
                    <label for="orden" class="filter-label">Ordenar por:</label>
                    <select name="orden" id="orden" class="filter-select">
                        <option value="">Relevancia</option>
                        <option value="nombre_asc" <?= $orden === 'nombre_asc' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                        <option value="nombre_desc" <?= $orden === 'nombre_desc' ? 'selected' : '' ?>>Nombre (Z-A)</option>
                        <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Precio (menor a mayor)</option>
                        <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio (mayor a menor)</option>
                    </select>
                </div>

                <!-- Filtro de precio -->
                <div class="filter-group price-filter">
                    <label class="filter-label">Rango de precio:</label>
                    <div class="price-controls">
                        <div class="price-inputs">
                            <div class="price-input-group">
                                <span class="currency">$</span>
                                <input type="number" name="min" class="price-input input-min" value="<?= $min ?: 0 ?>" min="0" max="500">
                            </div>
                            <span class="price-separator">-</span>
                            <div class="price-input-group">
                                <span class="currency">$</span>
                                <input type="number" name="max" class="price-input input-max" value="<?= $max ?: 500 ?>" min="0" max="500">
                            </div>
                        </div>
                        
                        <div class="price-slider">
                            <div class="slider-track">
                                <div class="slider-progress"></div>
                            </div>
                            <div class="range-inputs">
                                <input type="range" class="range-min" min="0" max="500" value="<?= $min ?: 0 ?>" step="1">
                                <input type="range" class="range-max" min="0" max="500" value="<?= $max ?: 500 ?>" step="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">🔍</span>
                        Aplicar filtros
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <span class="btn-icon">🔄</span>
                        Limpiar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="results-section">
        <?php if (empty($productos)): ?>
            <div class="no-products">
                <div class="no-products-icon">📦</div>
                <h3 class="no-products-title">No hay productos disponibles</h3>
                <p class="no-products-message">No se encontraron productos en esta categoría con los filtros aplicados.</p>
                <button type="button" class="btn btn-primary" onclick="clearFilters()">
                    <span class="btn-icon">🔄</span>
                    Limpiar filtros
                </button>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($productos as $p): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <img src="<?= htmlspecialchars($p['imagen']) ?>" 
                                 alt="<?= htmlspecialchars($p['nombre']) ?>" 
                                 class="product-image">
                            <div class="product-overlay">
                                <a href="detalle.php?id=<?= $p['id'] ?>" class="btn btn-info btn-small overlay-btn">
                                    <span class="btn-icon">👁️</span>
                                    Ver detalles
                                </a>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h4 class="product-name">
                                <a href="detalle.php?id=<?= $p['id'] ?>" class="product-link">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </a>
                            </h4>
                            <p class="product-description">
                                <?= htmlspecialchars(substr($p['descripcion'], 0, 100)) ?>...
                            </p>
                            <div class="product-price">
                                <span class="price-amount"><?= formatearPrecio($p['precio']) ?></span>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <form method="post" action="../actions/add_to_cart.php" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="variant" value="">
                                <button type="submit" class="btn btn-primary btn-small">
                                    <span class="btn-icon">🛒</span>
                                    Agregar al carrito
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Gestión del slider de precios
const rangeInputs = document.querySelectorAll(".range-inputs input");
const priceInputs = document.querySelectorAll(".price-inputs input");
const progress = document.querySelector(".slider-progress");
let priceGap = 5;

function updateProgress() {
    const minVal = parseInt(rangeInputs[0].value);
    const maxVal = parseInt(rangeInputs[1].value);
    const minPercent = (minVal / 500) * 100;
    const maxPercent = (maxVal / 500) * 100;
    
    progress.style.left = minPercent + "%";
    progress.style.right = (100 - maxPercent) + "%";
}

function updateInputs() {
    priceInputs[0].value = rangeInputs[0].value;
    priceInputs[1].value = rangeInputs[1].value;
    updateProgress();
}

// Sincronizar inputs de precio con sliders
priceInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        const minPrice = parseInt(priceInputs[0].value) || 0;
        const maxPrice = parseInt(priceInputs[1].value) || 500;

        if (maxPrice - minPrice >= priceGap && maxPrice <= 500 && minPrice >= 0) {
            rangeInputs[index].value = e.target.value;
            updateProgress();
        }
    });
});

// Sincronizar sliders con inputs de precio
rangeInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        const minVal = parseInt(rangeInputs[0].value);
        const maxVal = parseInt(rangeInputs[1].value);

        if (maxVal - minVal < priceGap) {
            if (index === 0) {
                rangeInputs[0].value = maxVal - priceGap;
            } else {
                rangeInputs[1].value = minVal + priceGap;
            }
        }
        updateInputs();
    });
});

// Limpiar filtros
function clearFilters() {
    const form = document.getElementById('filtersForm');
    form.querySelector('select[name="orden"]').value = '';
    form.querySelector('input[name="min"]').value = 0;
    form.querySelector('input[name="max"]').value = 500;
    rangeInputs[0].value = 0;
    rangeInputs[1].value = 500;
    updateProgress();
    form.submit();
}

// Auto-aplicar filtros al cambiar ordenamiento
document.getElementById('orden').addEventListener('change', function() {
    document.getElementById('filtersForm').submit();
});

// Inicializar slider
document.addEventListener("DOMContentLoaded", () => {
    updateProgress();
});

// Feedback visual para agregar al carrito
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const button = this.querySelector('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="btn-icon">⏳</span>Agregando...';
        button.disabled = true;
        
        // Restaurar después de un momento
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1500);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>