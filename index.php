<?php
session_start();
if (isset($_SESSION['firebase_uid'])) {
    header("Location: productos/index.php");
    exit;
}

require_once 'vendor/autoload.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Comercializadora</title>
    <link rel="stylesheet" href="/assets/css/index.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Iniciar Sesión</h2>
                <p class="login-subtitle">Accede a tu cuenta</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <p><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="firebase/login.php" class="login-form">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ingresa tu correo" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                </div>
                
                <button type="submit" class="btn-primary">Entrar</button>
            </form>

            <div class="login-footer">
                <p>¿No tienes cuenta? <a href="firebase/registro.php" class="link-register">Registrarse</a></p>
            </div>
        </div>
    </div>
</body>
</html>
