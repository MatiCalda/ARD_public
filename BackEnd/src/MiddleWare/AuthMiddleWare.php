<?php
namespace App\Middleware;

use App\Auth\JwtHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use Psr\Log\LoggerInterface;

class AuthMiddleware {
    protected $logger;
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    public function __invoke(Request $request, Handler $handler): Response {
        $authHeader = $request->getHeaderLine('X-Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->logger->info('token no enviado');
            return $this->unauthorized("Token no enviado");
        }

        $token = $matches[1];
        $decoded = JwtHandler::decode($token);

        if (!$decoded) {
            return $this->unauthorized("Token inválido");
        }

        $data = (array) $decoded->data;
        // Pasar datos al request
        $request = $request->withAttribute('user_id', $data['user_id']);
        return $handler->handle($request);
    }

    private function unauthorized(string $msg): Response {
        $res = new SlimResponse();
        $res->getBody()->write(json_encode(['error' => $msg]));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}
