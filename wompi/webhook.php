<?php
require_once '../includes/db.php';
date_default_timezone_set('America/El_Salvador');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 🧠 Leer input y guardar log
$input = file_get_contents("php://input");
$log_file = __DIR__ . '/../logs/webhook_' . date("Ymd_His") . '.log';
@file_put_contents($log_file, $input);
$data = json_decode($input, true);

// ✅ Validación
if (!$data || !isset($data['ResultadoTransaccion']) || !isset($data['EnlacePago']['IdentificadorEnlaceComercio'])) {
    http_response_code(400);
    echo "Datos inválidos";
    exit;
}

// 🎯 Extraer datos
$estado_raw = $data['ResultadoTransaccion'];
$estado = strtoupper($estado_raw) === "EXITOSAAPROBADA" ? "EXITOSA" : $estado_raw;
$identificador = $data['EnlacePago']['IdentificadorEnlaceComercio'];
$email = $data['Cliente']['EMail'] ?? 'sin-correo';
$formaPago = $data['FormaPagoUtilizada'] ?? 'desconocido';
$monto = floatval($data['Monto']);
$transaccion_id = $data['IdTransaccion'] ?? null;
$codigo_autorizacion = $data['CodigoAutorizacion'] ?? null;
$uid = null;

if (preg_match('/_uid_(.+)$/', $identificador, $match)) {
    $uid = $match[1];
}

// ⚠️ Verifica si ya existe
$stmt = $pdo->prepare("SELECT id FROM pedidos WHERE identificador = ?");
$stmt->execute([$identificador]);
if ($stmt->fetch()) {
    // Actualiza solo estado
    $update = $pdo->prepare("UPDATE pedidos 
        SET estado = ?, forma_pago = ?, id_transaccion = ?, codigo_autorizacion = ? 
        WHERE identificador = ?");
    $update->execute([$estado, $formaPago, $transaccion_id, $codigo_autorizacion, $identificador]);

    http_response_code(200);
    echo "Pedido ya existía, actualizado.";
    exit;
}

// 🧾 Buscar carrito temporal
$stmt = $pdo->prepare("SELECT * FROM carrito_temporal WHERE identificador = ?");
$stmt->execute([$identificador]);
$items_temporales = $stmt->fetchAll();

if (!$items_temporales) {
    http_response_code(400);
    echo "No se encontró el carrito temporal.";
    exit;
}

// 🧠 Obtener info adicional
$uno = $items_temporales[0];
$tipo_entrega = $uno['tipo_entrega'] ?? 'domicilio';
$sucursal_id = $uno['sucursal_id'] ?? null;
$comentarios = $uno['comentarios'] ?? '';

// 🧾 Insertar pedido
$stmt = $pdo->prepare("INSERT INTO pedidos 
    (identificador, email, monto, estado, forma_pago, firebase_uid, tipo_entrega, sucursal_id, comentarios, id_transaccion, codigo_autorizacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $identificador,
    $email,
    $monto,
    $estado,
    $formaPago,
    $uid,
    $tipo_entrega,
    $sucursal_id,
    $comentarios,
    $transaccion_id,
    $codigo_autorizacion
]);

$pedido_id = $pdo->lastInsertId();

// 🧾 Insertar productos en pedido_detalle
$detalleStmt = $pdo->prepare("INSERT INTO pedido_detalle 
    (pedido_id, producto_id, cantidad, precio_unitario, variante)
    VALUES (?, ?, ?, ?, ?)");

$errores = [];

foreach ($items_temporales as $item) {
    if (!isset($item['producto_id'], $item['cantidad'], $item['precio_unitario'])) {
        $errores[] = "Faltan campos en el item: " . json_encode($item);
        continue;
    }

    // Verificar que producto exista
    $check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id = ?");
    $check->execute([$item['producto_id']]);
    if ($check->fetchColumn() == 0) {
        $errores[] = "Producto ID no encontrado: " . $item['producto_id'];
        continue;
    }

    try {
        $detalleStmt->execute([
            $pedido_id,
            $item['producto_id'],
            $item['cantidad'],
            $item['precio_unitario'],
            $item['variante'] ?? ''
        ]);
    } catch (Exception $e) {
        $errores[] = "Error al insertar detalle: " . $e->getMessage();
    }
}


if (empty($errores)) {
    // Borrar carrito temporal
    $delStmt = $pdo->prepare("DELETE FROM carrito_temporal WHERE identificador = ?");
    $delStmt->execute([$identificador]);

    // Borrar carrito normal del usuario
    if (!empty($uid)) {
        $delCarrito = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $delCarrito->execute([$uid]);
    }

    http_response_code(200);
    echo "Pedido registrado exitosamente";
} else {
    file_put_contents($log_file . '.errors.txt', implode("\n", $errores));
    http_response_code(500);
    echo "Errores al registrar pedido. Revisa el log: " . basename($log_file) . '.errors.txt';
}

    

