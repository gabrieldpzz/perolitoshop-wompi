<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['firebase_email'])) {
    die("Acceso denegado.");
}

require_once '../includes/db.php';

$pedido_id = $_GET['pedido_id'] ?? null;
if (!$pedido_id) {
    die("ID de pedido no proporcionado.");
}

// Obtener el pedido
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND firebase_uid = ?");
$stmt->execute([$pedido_id, $_SESSION['firebase_uid']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Obtener detalles del pedido con imagen
$detalles = $pdo->prepare("
    SELECT d.*, p.nombre, p.imagen
    FROM pedido_detalle d
    JOIN productos p ON d.producto_id = p.id
    WHERE d.pedido_id = ?
");
$detalles->execute([$pedido_id]);
$productos = $detalles->fetchAll();

// Extraer dirección desde comentarios
// Extraer dirección desde comentarios o sucursal
// Extraer dirección desde comentarios o sucursal
$direccion = '-';
if ($pedido['tipo_entrega'] === 'domicilio' && !empty($pedido['comentarios'])) {
    // Procesar dirección para formatear visualmente
    $campos = explode(';', $pedido['comentarios']);
    $direccion = '<ul style="margin: 0; padding-left: 18px;">';
    foreach ($campos as $campo) {
        $campo = trim($campo);
        if ($campo !== '') {
            if (stripos($campo, 'Depto ID:') !== false) {
                $depto_id = (int) filter_var($campo, FILTER_SANITIZE_NUMBER_INT);
                $query = $pdo->prepare("SELECT nombre FROM departamentos WHERE id = ?");
                $query->execute([$depto_id]);
                $depto_nombre = $query->fetchColumn();
                $campo = "Departamento: " . ($depto_nombre ?: $depto_id);
            } elseif (stripos($campo, 'Municipio ID:') !== false) {
                $mun_id = (int) filter_var($campo, FILTER_SANITIZE_NUMBER_INT);
                $query = $pdo->prepare("SELECT nombre FROM municipios WHERE id = ?");
                $query->execute([$mun_id]);
                $mun_nombre = $query->fetchColumn();
                $campo = "Municipio: " . ($mun_nombre ?: $mun_id);
            }
            $direccion .= '<li>' . htmlspecialchars($campo) . '</li>';
        }
    }
    $direccion .= '</ul>';
} elseif ($pedido['tipo_entrega'] === 'sucursal' && is_numeric($pedido['sucursal_id'])) {
    $query = $pdo->prepare("SELECT nombre, direccion FROM sucursales WHERE id = ?");
    $query->execute([$pedido['sucursal_id']]);
    $sucursal = $query->fetch();
    if ($sucursal) {
        $direccion = "<ul style='margin: 0; padding-left: 18px;'>
            <li><strong>Sucursal:</strong> " . htmlspecialchars($sucursal['nombre']) . "</li>
            <li><strong>Dirección:</strong> " . htmlspecialchars($sucursal['direccion']) . "</li>
        </ul>";
    } else {
        $direccion = "<strong>Sucursal no encontrada (ID:</strong> {$pedido['sucursal_id']})";
    }
}


// Generar HTML
$html = "
<h1 style='text-align: center;'>Factura de compra</h1>
<hr>
<p><strong>Pedido #{$pedido['id']}</strong></p>
<p><strong>Identificador:</strong> {$pedido['identificador']}</p>
<p><strong>Fecha:</strong> {$pedido['fecha']}</p>
<p><strong>Email:</strong> {$pedido['email']}</p>
<p><strong>Forma de pago:</strong> {$pedido['forma_pago']}</p>
<p><strong>Tipo de entrega:</strong> {$pedido['tipo_entrega']}</p>
<p><strong>Dirección de entrega:</strong><br> {$direccion}</p>
<hr>
<table width='100%' border='1' cellspacing='0' cellpadding='5'>
    <thead>
        <tr>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Variante</th>
            <th>Precio unitario</th>
            <th>Cantidad</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>";

foreach ($productos as $prod) {
    $subtotal = $prod['precio_unitario'] * $prod['cantidad'];
    $imagen_url = $prod['imagen'];
    $imagen_html = '(imagen no compatible)';

    // Validar extensión
    $ext = strtolower(pathinfo(parse_url($imagen_url, PHP_URL_PATH), PATHINFO_EXTENSION));
    $formatos_validos = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $formatos_validos) && filter_var($imagen_url, FILTER_VALIDATE_URL)) {
        $img_data = @file_get_contents($imagen_url);
        if ($img_data !== false) {
            $mime = match($ext) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                default => 'image/jpeg'
            };
            $base64 = base64_encode($img_data);
            $imagen_html = "<img src='data:{$mime};base64,{$base64}' width='60'>";
        }
    }

    $html .= "<tr>
        <td>{$imagen_html}</td>
        <td>" . htmlspecialchars($prod['nombre']) . "</td>
        <td>" . htmlspecialchars($prod['variante'] ?? '-') . "</td>
        <td>$ " . number_format($prod['precio_unitario'], 2) . "</td>
        <td>{$prod['cantidad']}</td>
        <td>$ " . number_format($subtotal, 2) . "</td>
    </tr>";
}


$html .= "</tbody></table>
<br><h3>Total pagado: $ {$pedido['monto']}</h3>
<p style='text-align: center;'>Gracias por tu compra</p>";

// Configurar Dompdf con imágenes remotas
$options = new Options();
$options->set('isRemoteEnabled', true); // 🔓 Permitir URLs externas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("factura_{$pedido_id}.pdf", ["Attachment" => false]);