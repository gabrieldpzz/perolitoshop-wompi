<?php
session_start();
if (!isset($_SESSION['firebase_uid'])) {
    header("Location: /index.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Obtener productos destacados
$productos = $pdo->query("SELECT * FROM productos ORDER BY RAND() LIMIT 12")->fetchAll();

// Si hay menos de 12 productos, duplicar para llenar el carrusel
$productos_carrusel = $productos;
while (count($productos_carrusel) < 12) {
    $productos_carrusel = array_merge($productos_carrusel, $productos);
}
$productos_carrusel = array_slice($productos_carrusel, 0, 12);

// Obtener categorías destacadas
$categorias_destacadas = [1, 2, 3, 6, 7];
$categoria_info = [];
$categoria_nombres = [
    1 => 'Ropa', 
    2 => 'Electrónica', 
    3 => 'Moda', 
    6 => 'Juguetes', 
    7 => 'Belleza'
];

foreach ($categorias_destacadas as $cat_id) {
    $stmt = $pdo->prepare("SELECT MIN(imagen) as imagen, COUNT(*) as total FROM productos WHERE categoria_id = ? GROUP BY categoria_id");
    $stmt->execute([$cat_id]);
    $result = $stmt->fetch();
    
    $categoria_info[$cat_id] = [
        'nombre' => $categoria_nombres[$cat_id],
        'imagen' => $result['imagen'] ?? '/placeholder.svg?height=120&width=120',
        'total' => $result['total'] ?? 0
    ];
}

// Obtener productos más vendidos (simulado)
$stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC LIMIT 6");
$productos_populares = $stmt->fetchAll();

// Obtener ofertas especiales (productos con precio menor a $30)
$stmt = $pdo->query("SELECT * FROM productos WHERE precio < 30 ORDER BY precio ASC LIMIT 4");
$ofertas_especiales = $stmt->fetchAll();
?>

<link rel="stylesheet" href="/assets/css/productos_index.css">

<div class="main-container">
    <!-- Banner promocional -->
    <div class="promo-banner">
        <div class="banner-content">
            <div class="banner-icon">🎉</div>
            <div class="banner-text">
                <span class="banner-title">¡Ofertas Especiales!</span>
                <span class="banner-subtitle">Descuentos de hasta 50% en productos seleccionados</span>
            </div>
            <div class="banner-action">
                <a href="#ofertas" class="btn-banner">Ver Ofertas</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <span class="hero-icon">🛍️</span>
                ¡Bienvenido a tu Tienda Online!
            </h1>
            <p class="hero-subtitle">
                Descubre los mejores productos con la calidad que mereces y precios increíbles
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">+150</span>
                    <span class="stat-label">Productos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">+10</span>
                    <span class="stat-label">Categorías</span>
                </div>  
            </div>
        </div>
    </section>

    <!-- Productos Destacados -->
    <section class="featured-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">⭐</span>
                Productos Destacados
            </h2>
            <p class="section-subtitle">Los productos más populares seleccionados especialmente para ti</p>
        </div>

        <div class="carousel-container">
            <div class="carousel-track" id="carouselTrack">
                <?php foreach ($productos_carrusel as $index => $producto): ?>
                    <div class="product-card">
                        <?php if ($producto['precio'] < 25): ?>
                            <div class="product-badge sale">¡Oferta!</div>
                        <?php elseif ($index < 3): ?>
                            <div class="product-badge popular">Popular</div>
                        <?php endif; ?>
                        
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                 loading="lazy">
                            <div class="product-overlay">
                                <a href="detalle.php?id=<?= $producto['id'] ?>" class="btn-quick-view">
                                    👁️ Vista Rápida
                                </a>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <p class="product-description">
                                <?= substr(htmlspecialchars($producto['descripcion']), 0, 80) ?>...
                            </p>
                            <div class="product-price">
                                <?php if ($producto['precio'] < 25): ?>
                                    <span class="price-original">$<?= number_format($producto['precio'] * 1.3, 2) ?></span>
                                <?php endif; ?>
                                <span class="price-current">$<?= number_format($producto['precio'], 2) ?></span>
                            </div>
                            <div class="product-actions">
                                <form method="post" action="../actions/add_to_cart.php" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?= $producto['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="variant" value="">
                                    <button type="submit" class="btn-add-cart">
                                        <span class="btn-icon">🛒</span>
                                        Agregar
                                    </button>
                                </form>
                                <a href="detalle.php?id=<?= $producto['id'] ?>" class="btn-details">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button class="carousel-btn prev" onclick="moveCarousel(-1)">‹</button>
            <button class="carousel-btn next" onclick="moveCarousel(1)">›</button>
        </div>
    </section>

    <!-- Categorías Destacadas -->
    <section class="categories-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📂</span>
                Explora por Categoría
            </h2>
            <p class="section-subtitle">Encuentra exactamente lo que buscas navegando por nuestras categorías</p>
        </div>

        <div class="categories-grid">
            <?php foreach ($categorias_destacadas as $cat_id): ?>
                <a href="categoria.php?id=<?= $cat_id ?>" class="category-card">
                    <div class="category-image">
                        <img src="<?= htmlspecialchars($categoria_info[$cat_id]['imagen']) ?>" 
                             alt="<?= htmlspecialchars($categoria_info[$cat_id]['nombre']) ?>">
                        <div class="category-overlay">
                            <span class="category-count"><?= $categoria_info[$cat_id]['total'] ?> productos</span>
                        </div>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name"><?= htmlspecialchars($categoria_info[$cat_id]['nombre']) ?></h3>
                        <span class="category-arrow">→</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Ofertas Especiales -->
    <?php if (!empty($ofertas_especiales)): ?>
    <section class="offers-section" id="ofertas">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">🔥</span>
                Ofertas Especiales
            </h2>
            <p class="section-subtitle">Precios increíbles por tiempo limitado</p>
        </div>

        <div class="offers-grid">
            <?php foreach ($ofertas_especiales as $oferta): ?>
                <div class="offer-card">
                    <div class="offer-badge">-<?= rand(20, 50) ?>%</div>
                    <div class="offer-image">
                        <img src="<?= htmlspecialchars($oferta['imagen']) ?>" 
                             alt="<?= htmlspecialchars($oferta['nombre']) ?>">
                    </div>
                    <div class="offer-info">
                        <h3 class="offer-name"><?= htmlspecialchars($oferta['nombre']) ?></h3>
                        <div class="offer-price">
                            <span class="price-original">$<?= number_format($oferta['precio'] * 1.4, 2) ?></span>
                            <span class="price-offer">$<?= number_format($oferta['precio'], 2) ?></span>
                        </div>
                        <div class="offer-actions">
                            <form method="post" action="../actions/add_to_cart.php" class="offer-form">
                                <input type="hidden" name="product_id" value="<?= $oferta['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="variant" value="">
                                <button type="submit" class="btn-offer">
                                    🛒 Aprovechar Oferta
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Productos Populares -->
    <section class="popular-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">🏆</span>
                Más Populares
            </h2>
            <p class="section-subtitle">Los productos favoritos de nuestros clientes</p>
        </div>

        <div class="popular-grid">
            <?php foreach ($productos_populares as $index => $popular): ?>
                <div class="popular-card">
                    <div class="popular-rank">#<?= $index + 1 ?></div>
                    <div class="popular-image">
                        <img src="<?= htmlspecialchars($popular['imagen']) ?>" 
                             alt="<?= htmlspecialchars($popular['nombre']) ?>">
                    </div>
                    <div class="popular-info">
                        <h3 class="popular-name"><?= htmlspecialchars($popular['nombre']) ?></h3>
                        <div class="popular-rating">
                            <span class="stars">⭐⭐⭐⭐⭐</span>
                            <span class="rating-count">(<?= rand(50, 200) ?>)</span>
                        </div>
                        <div class="popular-price">$<?= number_format($popular['precio'], 2) ?></div>
                        <a href="detalle.php?id=<?= $popular['id'] ?>" class="btn-popular">Ver Producto</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</div>

<script>
let currentSlide = 0;
const totalSlides = <?= count($productos_carrusel) ?>;
const slidesToShow = 4;
const maxSlide = totalSlides - slidesToShow;

function moveCarousel(direction) {
    const track = document.getElementById('carouselTrack');
    
    currentSlide += direction;
    
    if (currentSlide < 0) {
        currentSlide = maxSlide;
    } else if (currentSlide > maxSlide) {
        currentSlide = 0;
    }
    
    const translateX = -(currentSlide * (100 / slidesToShow));
    track.style.transform = `translateX(${translateX}%)`;
}

// Auto-play del carrusel
setInterval(() => {
    moveCarousel(1);
}, 5000);

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.section-header, .product-card, .category-card, .offer-card, .popular-card').forEach(el => {
        observer.observe(el);
    });
});

// Smooth scroll para enlaces internos
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Feedback visual para botones de agregar al carrito
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const button = this.querySelector('.btn-add-cart');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<span class="btn-icon">✓</span> Agregado';
        button.style.backgroundColor = '#A8E6CF';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.backgroundColor = '';
            button.disabled = false;
        }, 2000);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
