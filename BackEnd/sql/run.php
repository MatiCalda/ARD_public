<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

$logDir = __DIR__ . '/../logs/sql';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = "$logDir/" . date('Y-m-d') . "_sql.log";
try {
    $envPath = dirname(__DIR__) . '/.env';
    $_ENV = parseEnv($envPath);

    $hostname = $_ENV['DB_HOSTNAME'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];

    // Verificar que se pase el parámetro
    if ($argc < 2) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] - Debes indicar un archivo .sql\n", FILE_APPEND);
        exit(1);
    }

    $sqlFile = $argv[1];
    if (!file_exists($sqlFile)) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] - Archivo no encontrado: $sqlFile\n", FILE_APPEND);
        exit(1);
    }


    $conn = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents($sqlFile);
    $conn->exec($sql);

    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] - Ejecutado $sqlFile correctamente\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] - Error: {$e->getMessage()}\n", FILE_APPEND);
    exit(1);
}

function parseEnv($path) {
    if (!file_exists($path))
        return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (!strpos($line, '='))
            continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
    }
    return $env;
}