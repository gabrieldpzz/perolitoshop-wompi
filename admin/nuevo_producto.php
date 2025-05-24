<?php
ob_start(); // 🔧 Previene errores de "headers already sent"
session_start();
if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /index.php");
    exit;
}
require_once '../includes/db.php';
require_once '../includes/header.php';

// Obtener categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $categoria_id = $_POST['categoria_id'] ?? null;
    $imagen = $_POST['imagen'] ?? '';
    $variantes_json = $_POST['variantes_json'] ?? '{}';

    $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria_id, imagen, variantes_json) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $descripcion, $precio, $categoria_id, $imagen, $variantes_json]);

    header("Location: productos.php");
    exit;
}
?>

<h2>Nuevo Producto</h2>
<form method="post" onsubmit="generarJSON()">
    <label>Nombre:</label><br>
    <input name="nombre" placeholder="Nombre" required><br><br>

    <label>Descripción:</label><br>
    <textarea name="descripcion" placeholder="Descripción" required></textarea><br><br>

    <label>Precio ($):</label><br>
    <input name="precio" type="number" step="0.01" required><br><br>

    <label>Categoría:</label><br>
    <select name="categoria_id" required>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>URL Imagen:</label><br>
    <input name="imagen" placeholder="https://..." required><br><br>

    <div id="variantes">
        <h4>Variantes (opcional)</h4>
        <div class="variante">
            <input type="text" placeholder="Nombre de variante (ej. talla)" class="nombre-variante">
            <textarea placeholder="Valores separados por coma (ej. S,M,L)" class="valores-variante"></textarea>
        </div>
    </div>
    <button type="button" onclick="agregarVariante()">+ Agregar otra variante</button><br><br>

    <input type="hidden" name="variantes_json" id="variantes_json">
    <button type="submit">Guardar producto</button>
</form>

<script>
function agregarVariante() {
    const div = document.createElement('div');
    div.classList.add('variante');
    div.innerHTML = `
        <input type="text" placeholder="Nombre de variante" class="nombre-variante">
        <textarea placeholder="Valores separados por coma" class="valores-variante"></textarea>
    `;
    document.getElementById('variantes').appendChild(div);
}

function generarJSON() {
    const nombres = document.querySelectorAll('.nombre-variante');
    const valores = document.querySelectorAll('.valores-variante');
    const resultado = {};

    for (let i = 0; i < nombres.length; i++) {
        const nombre = nombres[i].value.trim();
        const valoresArray = valores[i].value.split(',').map(v => v.trim()).filter(v => v !== '');
        if (nombre && valoresArray.length) {
            resultado[nombre] = valoresArray;
        }
    }

    document.getElementById('variantes_json').value = JSON.stringify(resultado);
}
</script>

<?php ob_end_flush(); ?>
