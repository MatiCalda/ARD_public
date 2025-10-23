<?php namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\JsonResponse;
use Psr\Log\LoggerInterface;
use PDO;
use Ramsey\Uuid\Uuid;

class StockController extends BaseController {
    private $logger;
    private $pdo;

    public function __construct(LoggerInterface $logger, PDO $pdo) {
        $this->logger = $logger;
        $this->pdo = $pdo;
    }
    public function getStock(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->query("SELECT s.id, se.nombre as seller_nombre, c.nombre as categoria_nombre, p.nombre as producto_nombre, 
            p.descripcion, p.precio FROM stock s 
            JOIN productos p ON s.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id JOIN sellers se ON s.seller_id = se.id
            WHERE p.activo = TRUE ORDER BY p.id, se.is_owner DESC, s.cantidad DESC");
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " productos encontrados";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando stock: " . $e->getMessage());
            $jsonResponse->message = "Error buscando stock";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function createStock(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO stock (id, producto_id, seller_id, cantidad) VALUES (:id, :producto_id, :seller_id, :cantidad)");
            $stmt->execute([
                'id' => Uuid::uuid4()->toString(),
                'producto_id' => $data['producto_id'],
                'seller_id' => $data['seller_id'],
                'cantidad' => $data['cantidad']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Stock creado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error creando stock: " . $e->getMessage());
            $jsonResponse->message = "Error creando stock";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function updateStock(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE stock SET cantidad = :cantidad WHERE id = :id");
            $stmt->execute([
                'id' => $args['id'],
                'cantidad' => $data['cantidad']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Stock actualizado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error actualizando stock: " . $e->getMessage());
            $jsonResponse->message = "Error actualizando stock";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
