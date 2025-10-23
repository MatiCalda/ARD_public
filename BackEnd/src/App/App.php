<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../')->load();

// Slim
$aux = new \DI\Container();
AppFactory::setContainer($aux);
$app = AppFactory::create();
$container = $app->getContainer();

require __DIR__ . '/Routes.php';
require __DIR__ . '/Configs.php';
require __DIR__ . '/Dependencies.php';

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setDefaultErrorHandler(function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $logger = $app->getContainer()->get(LoggerInterface::class);
    $logger->error('App error => '.$exception->getMessage(), [
        'code' => $exception instanceof HttpException ? $exception->getCode() : 500,
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode([
        'status' => 'error',
        'data' => [
            'code' => $exception instanceof HttpException ? $exception->getCode() : 500,
        ],
        'message' => 'Ha ocurrido un error. Por favor, contacta al soporte.',
    ], JSON_UNESCAPED_UNICODE));

    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
});

$app->run();
