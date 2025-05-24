<?php
require_once 'wompi_token.php';

$token = obtenerTokenWompi();

$data = [
    "idAplicativo" => "",
    "identificadorEnlaceComercio" => uniqid("compra_"),
    "monto" => 10.99,
    "nombreProducto" => "Perolito Shop",
    "formaPago" => [
        "permitirTarjetaCreditoDebido" => true,
        "permitirPagoConPuntoAgricola" => false,
        "permitirPagoEnCuotasAgricola" => true,
        "permitirPagoEnBitcoin" => false
    ],
    "cantidadMaximaCuotas" => "12",
    "infoProducto" => [
        "descripcionProducto" => "Estas a punto de obtener tus productos de Perolito Shop",
        "urlImagenProducto" => "https://i.imgur.com/m60HKKt.png"
    ],
    "configuracion" => [
        "urlRedirect" => "http://localhost:8080/response.php",
        "esMontoEditable" => false,
        "esCantidadEditable" => false,
        "cantidadPorDefecto" => 1,
        "duracionInterfazIntentoMinutos" => 15,
        "urlRetorno" => "http://localhost:8080/",
        "emailsNotificacion" => "g.alexis7112@gmail.com",
        "urlWebhook" => "http://localhost:8080/wompi/webhook.php",
        "telefonosNotificacion" => "60696984",
        "notificarTransaccionCliente" => true
    ],
    "vigencia" => [
        "fechaInicio" => gmdate('Y-m-d\TH:i:s.000\Z'),
        "fechaFin" => "2025-12-31T23:59:59.999Z"
    ],
    "limitesDeUso" => [
        "cantidadMaximaPagosExitosos" => 1000,
        "cantidadMaximaPagosFallidos" => 5
    ]
];

$ch = curl_init("https://api.wompi.sv/EnlacePago");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json-patch+json"
    ]
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['urlEnlace'])) {
    header("Location: " . $response['urlEnlace']);
    exit;
} else {
    echo "Error al generar enlace de pago: ";
    print_r($response);
}
