<?php

use \Psr\Container\ContainerInterface;
use App\Services\EmailService;
use App\Middleware\AuthMiddleware;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Controllers\AuthController;

$container->set(LoggerInterface::class, function ($c) {
    $logFile = __DIR__ . '/../../logs/app/' . date('Y-m-d') . '_app.log';
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
    return $logger;
});
$container->set(AuthController::class, function (ContainerInterface $c) {
    $logFile = __DIR__ . '/../../logs/logins/' . date('Y-m-d') . '_auth.log';
    $authLogger = new Logger('auth');
    $authLogger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

    $pdo = $c->get(PDO::class);
    $emailService = $c->get(EmailService::class);
    return new AuthController($authLogger, $pdo, $emailService);
});


$container->set(AuthMiddleware::class, function ($c) {
    return new AuthMiddleware($c->get(LoggerInterface::class));
});

$container->set(EmailService::class, function (ContainerInterface $c) {
    $config = [
        'host' => $_ENV['MAIL_HOST'],
        'port' => $_ENV['MAIL_PORT'],
        'username' => $_ENV['MAIL_USERNAME'],
        'password' => $_ENV['MAIL_PASSWORD'],
        'from' => $_ENV['MAIL_FROM'],
        'from_name' => $_ENV['MAIL_FROM_NAME'],
        'secure' => $_ENV['MAIL_SECURE'],
    ];
    return new EmailService($config, $c->get(Psr\Log\LoggerInterface::class));
});

$container->set(PDO::class, function (ContainerInterface $c) {
    $config = $c->get('db_settings');

    $dsn = "mysql:host={$config->DB_HOST};dbname={$config->DB_NAME};charset={$config->DB_CHAR};timezone={-3:00}";

    $pdo = new PDO($dsn, $config->DB_USER, $config->DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
    // Establecer zona horaria del servidor MySQL
    $pdo->exec("SET time_zone = '-03:00'");

    return $pdo;
});
