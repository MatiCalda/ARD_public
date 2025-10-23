<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\BaseController;
use App\Models\JsonResponse;
use App\Auth\JwtHandler;
use Psr\Log\LoggerInterface;
use PDO;
use Ramsey\Uuid\Uuid;
use \DateTime;

class AuthController extends BaseController {
    private $logger;
    private $pdo;
    private $emailService;
    public function __construct(LoggerInterface $logger, PDO $pdo, \App\Services\EmailService $emailService) {
        $this->logger = $logger;
        $this->pdo = $pdo;
        $this->emailService = $emailService;
    }

    public function login(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $body = $request->getBody();
            $params = json_decode($body, true);
            $email = $params['email'] ?? '';
            $password = $params['pass'] ?? '';

            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT uc.user_id, uc.password_hash, r.nombre as rol FROM user_credentials uc JOIN users u ON uc.user_id = u.id JOIN roles r ON u.rol_id = r.id WHERE u.correo = :correo LIMIT 1");
            $stmt->execute([
                'correo' => $email,
            ]);
            $row = $stmt->fetch();
            $clave = $row->password_hash ?? null;


            if ($clave && password_verify($password, $clave)) {
                // Usuario válido, generar token
                $token = JwtHandler::encode([
                    'user_id' => $row->user_id,
                    'rol' => $row->rol
                ]);
                $this->logger->info("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message . ": " . $email);
                $response->getBody()->write(json_encode(['token' => $token]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                // Usuario o contraseña incorrecta
                $jsonResponse->message = "Usuario o contraseña incorrectos";
                $response->getBody()->write(json_encode($jsonResponse));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }

        } catch (\Exception $e) {
            $jsonResponse->message = "Error al procesar la solicitud";
            $this->logger->error("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message . ": " . $e->getMessage());
            $response->getBody()->write(json_encode($jsonResponse));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    public function recoverPwd(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $body = $request->getBody();
            $params = json_decode($body, true);
            $email = trim($params['email'] ?? '');

            // Verificar si el usuario existe
            $stmt = $this->pdo->prepare("SELECT id, nombre, correo FROM usuarios WHERE correo = :correo and activo = 1");
            $stmt->execute(['correo' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $jsonResponse->message = 'El usuario no existe o esta inactivo.';
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $this->pdo->beginTransaction();
            try {
                // Crear token de recuperación (puede ser JWT o un token aleatorio)
                $recoveryToken = bin2hex(random_bytes(32)); // Token seguro

                // si ya tiene un token de recuperación, lo actualizamos
                $stmt = $this->pdo->prepare("SELECT id FROM recovery_tokens WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $user->id]);
                if ($stmt->fetch()) {
                    // Actualizar el token de recuperación
                    $stmt = $this->pdo->prepare("UPDATE recovery_tokens SET token = :token, created_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user_id = :user_id");
                    $stmt->execute([
                        'token' => $recoveryToken,
                        'user_id' => $user->id
                    ]);
                } else {
                    // Insertar un nuevo token de recuperación
                    $stmt = $this->pdo->prepare("INSERT INTO recovery_tokens (user_id, correo, token, expires_at) VALUES (:user_id, :correo, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                    $stmt->execute([
                        'correo' => $user->correo,
                        'token' => $recoveryToken,
                        'user_id' => $user->id
                    ]);
                }
                $this->pdo->commit();
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                $jsonResponse->message = 'Error al procesar la solicitud';
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            $subject = 'Recuperacion de clave';
            $recoveryLink = "https://sitioWEB/?token=$recoveryToken"; // TODO: Cambiar a URL real
            $bodyEmail =
                '<!DOCTYPE html>' .
                '<html lang="es">' .
                '<head>' .
                '<meta charset="UTF-8">' .
                '<title>Cambio de Clave - Progress Gym</title>' . // TODO: Cambiar a nombre de sitio real
                '<style>' .
                'body {' .
                'font-family: Arial, sans-serif;' .
                'background-color: #f4f4f4;' .
                'margin: 0;' .
                'padding: 0;' .
                '}' .
                '.container {' .
                'max-width: 600px;' .
                'margin: 40px auto;' .
                'background-color: #ffffff;' .
                'padding: 30px;' .
                'border-radius: 8px;' .
                'box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);' .
                '}' .
                'h2 { color: #333333; }' .
                'p { color: #555555; font-size: 16px; line-height: 1.6; }' .
                '.button-link {' .
                'display: inline-block;' .
                'padding: 12px 20px;' .
                'margin-top: 20px;' .
                'background-color: #28a745;' .
                'color: white;' .
                'text-decoration: none;' .
                'border-radius: 5px;' .
                'font-weight: bold;' .
                '}' .
                '.footer {' .
                'margin-top: 30px;' .
                'font-size: 12px;' .
                'color: #999999;' .
                'text-align: center;' .
                '}' .
                '</style>' .
                '</head>' .
                '<body>' .
                '<div class="container">' .
                '<h2>Recuperacion de Clave</h2>' .
                '<p>Hola, has solicitado recuperar tu clave. Por favor, haz clic en el siguiente enlace para cambiarla:</p>' .
                '<a href="' . $recoveryLink . '" class="button-link">Click aqui</a>' .
                '<p>Si no solicitaste este cambio, puedes ignorar este correo.</p>' .
                '<div class="footer">' .
                '&copy; 2025 Progress Gym - Todos los derechos reservados' .
                '</div>' .
                '</div>' .
                '</body>' .
                '</html>';

            // logue en la misma clase de EmailService
            if ($this->emailService->sendEmail($email, $subject, $bodyEmail)) {
                $jsonResponse->message = 'Te enviamos un email con las instrucciones para recuperar la contraseña.';
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $jsonResponse->message = 'ocurrio un error, por favor contacta a soporte';
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } catch (\Exception $e) {
            $jsonResponse->message = 'Error al procesar la solicitud';
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function changePwd(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $body = $request->getBody();
            $params = json_decode($body, true);
            $newPass = trim($params['newPass'] ?? '');
            $recoveryToken = trim($params['token'] ?? '');

            // Validar nueva contraseña
            if (empty($newPass) || !preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()_\+\[\]{};\'":\\\\|,.<>\/?]).{8,}$/', $newPass)) {
                $jsonResponse->message = "La nueva contraseña no cumple con las normas.";
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }



            // Iniciar transacción
            $this->pdo->beginTransaction();

            try {
                // Verificar si el token existe y no está expirado
                $stmt = $this->pdo->prepare("SELECT id, correo, user_id FROM recovery_tokens
                    WHERE token = :token AND expires_at > NOW()");
                $stmt->execute(['token' => $recoveryToken]);
                $token = $stmt->fetch();

                if (!$token) {
                    $jsonResponse->message = "Token inválido.";
                    $this->logger->warning("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message);
                    $response->getBody()->write(json_encode($jsonResponse));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
                }
                // Actualizar contraseña y reactivar cuenta
                $newPassHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE usuarios SET clave = :clave WHERE id = :id");
                $stmt->execute([
                    'clave' => $newPassHash,
                    'id' => $token->user_id
                ]);
                $stmt = $this->pdo->prepare("DELETE FROM recovery_tokens WHERE id = :id");
                $stmt->execute([
                    'id' => $token->id
                ]);
                $jsonResponse->message = "Clave actualizada correctamente.";
                $this->logger->info("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message . " " . $token->correo);
                $this->pdo->commit();
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                $jsonResponse->message = "Error al procesar la solicitud.";
                $this->logger->warning("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message . " $e");
                $response->getBody()->write(json_encode($jsonResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        } catch (\Exception $e) {
            $jsonResponse->message = "Error al procesar la solicitud.";
            $this->logger->warning("[" . __CLASS__ . "::" . __FUNCTION__ . "] " . $jsonResponse->message . " $e");
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

}
