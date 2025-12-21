<?php
// Fecha y hora
$timestamp = date('Y-m-d H:i:s');

// Obtener IP real (manejo correcto de proxies)
function getClientIP() {
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $key) {
        if (!empty($_SERVER[$key])) {
            // En X_FORWARDED_FOR puede venir una lista de IPs
            $ipList = explode(',', $_SERVER[$key]);
            return trim($ipList[0]);
        }
    }
    return 'UNKNOWN';
}

$ipaddress = getClientIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

// Formato del log
$logEntry  = "=============================\n";
$logEntry .= "Fecha: $timestamp\n";
$logEntry .= "IP: $ipaddress\n";
$logEntry .= "User-Agent: $userAgent\n";

// Archivo destino
$file = __DIR__ . '/ip.txt';

// Escritura segura con bloqueo
if ($fp = fopen($file, 'a')) {
    flock($fp, LOCK_EX);
    fwrite($fp, $logEntry);
    flock($fp, LOCK_UN);
    fclose($fp);
}
?>
