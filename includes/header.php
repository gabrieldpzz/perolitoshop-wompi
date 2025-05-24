<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comercializadora</title>
    <link rel="stylesheet" href="/assets/css/header.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="header-brand">
                <h1 class="brand-title">
                    <span class="brand-icon">🛒</span>
                    Comercializadora
                </h1>
            </div>
            
            <div class="header-actions">
                <?php if (isset($_SESSION['firebase_uid'])): ?>
                    <div class="user-menu">
                        <span class="user-greeting">
                            Hola, <?= htmlspecialchars(explode('@', $_SESSION['email'] ?? 'Usuario')[0]) ?>
                        </span>
                        <form method="post" action="/firebase/logout.php" class="logout-form">
                            <button type="submit" class="btn-logout">
                                <span class="logout-icon">🚪</span>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="/index.php" class="btn-login">Iniciar sesión</a>
                        <a href="/firebase/registro.php" class="btn-register">Registrarse</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="nav-container">
            <button onclick="toggleSidebar()" class="btn-departments">
                <span class="departments-icon">☰</span>
                <span class="departments-text">Departamentos</span>
            </button>
            
            <div class="nav-links">
                <a href="/productos/index.php" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    Inicio
                </a>
                <a href="/carrito/index.php" class="nav-link">
                    <span class="nav-icon">🛒</span>
                    Carrito
                </a>
                <a href="/carrito/historial.php" class="nav-link">
                    <span class="nav-icon">📋</span>
                    Mis Compras
                </a>
                <a href="/tracking.php" class="nav-link">
                    <span class="nav-icon">📦</span>
                    Rastrear Envío
                </a>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <a href="/admin/index.php" class="nav-link admin-link">
                        <span class="nav-icon">🔐</span>
                        Panel Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <aside id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3 class="sidebar-title">
                <span class="sidebar-icon">📂</span>
                Categorías
            </h3>
            <button onclick="toggleSidebar()" class="btn-close-sidebar">
                <span class="close-icon">✖</span>
            </button>
        </div>
        
        <div class="sidebar-content">
            <ul class="categories-list">
                <li class="category-item">
                    <a href="/productos/categoria.php?id=1" class="category-link">
                        <span class="category-icon">👕</span>
                        Ropa
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=2" class="category-link">
                        <span class="category-icon">📱</span>
                        Electrónica
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=3" class="category-link">
                        <span class="category-icon">👗</span>
                        Moda
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=4" class="category-link">
                        <span class="category-icon">🍎</span>
                        Alimentos
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=5" class="category-link">
                        <span class="category-icon">🏠</span>
                        Hogar
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=6" class="category-link">
                        <span class="category-icon">🧸</span>
                        Juguetes
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=7" class="category-link">
                        <span class="category-icon">💄</span>
                        Belleza
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=8" class="category-link">
                        <span class="category-icon">💊</span>
                        Salud
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=9" class="category-link">
                        <span class="category-icon">🐕</span>
                        Mascotas
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=10" class="category-link">
                        <span class="category-icon">📚</span>
                        Libros
                    </a>
                </li>
                <li class="category-item">
                    <a href="/productos/categoria.php?id=11" class="category-link">
                        <span class="category-icon">🔧</span>
                        Ferretería
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const body = document.body;
        
        if (sidebar.classList.contains('visible')) {
            sidebar.classList.remove('visible');
            overlay.classList.remove('visible');
            body.classList.remove('sidebar-open');
        } else {
            sidebar.classList.add('visible');
            overlay.classList.add('visible');
            body.classList.add('sidebar-open');
        }
    }

    // Cerrar sidebar al hacer clic en un enlace (móvil)
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
        });
    });

    // Cerrar sidebar al redimensionar ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const body = document.body;
            
            sidebar.classList.remove('visible');
            overlay.classList.remove('visible');
            body.classList.remove('sidebar-open');
        }
    });
    </script>

    <main class="main-content">
