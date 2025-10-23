<?php namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\JsonResponse;
use Psr\Log\LoggerInterface;
use PDO;
use Ramsey\Uuid\Uuid;

class VentaController extends BaseController {
    private $logger;
    private $pdo;

    public function __construct(LoggerInterface $logger, PDO $pdo) {
        $this->logger = $logger;
        $this->pdo = $pdo;
    }
    public function getVentas(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->query("SELECT v.id, s.nombre as seller_nombre, p.nombre as producto_nombre, v.cantidad, v.total, v.fecha FROM ventas v 
            JOIN productos p ON v.producto_id = p.id JOIN sellers s ON v.seller_id = s.id ORDER BY v.fecha DESC");
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " ventas encontradas";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando ventas: " . $e->getMessage());
            $jsonResponse->message = "Error buscando ventas";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function createVenta(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO ventas (id, producto_id, seller_id, cantidad, total, fecha) VALUES (:id, :producto_id, :seller_id, :cantidad, :total, :fecha)");
            $stmt->execute([
                'id' => Uuid::uuid4()->toString(),
                'producto_id' => $data['producto_id'],
                'seller_id' => $data['seller_id'],
                'cantidad' => $data['cantidad'],
                'total' => $data['total'],
                'fecha' => date('Y-m-d H:i:s')
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Venta creada exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error creando venta: " . $e->getMessage());
            $jsonResponse->message = "Error creando venta";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getVentasBySeller(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->prepare("SELECT v.id, s.nombre as seller_nombre, p.nombre as producto_nombre, v.cantidad, v.total, v.fecha FROM ventas v 
            JOIN productos p ON v.producto_id = p.id JOIN sellers s ON v.seller_id = s.id WHERE s.id = :seller_id ORDER BY v.fecha DESC");
            $stmt->execute([
                'seller_id' => $args['seller_id']
            ]);
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " ventas encontradas para el vendedor";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando ventas por vendedor: " . $e->getMessage());
            $jsonResponse->message = "Error buscando ventas por vendedor";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getVentasByDateRange(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->prepare("SELECT v.id, s.nombre as seller_nombre, p.nombre as producto_nombre, v.cantidad, v.total, v.fecha FROM ventas v 
            JOIN productos p ON v.producto_id = p.id JOIN sellers s ON v.seller_id = s.id WHERE v.fecha BETWEEN :start_date AND :end_date ORDER BY v.fecha DESC");
            $stmt->execute([
                'start_date' => $args['start_date'],
                'end_date' => $args['end_date']
            ]);
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " ventas encontradas en el rango de fechas";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando ventas por rango de fechas: " . $e->getMessage());
            $jsonResponse->message = "Error buscando ventas por rango de fechas";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function anularVenta(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE ventas SET activo = 0 WHERE id = :id");
            $stmt->execute([
                'id' => $args['id']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Venta anulada exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error anulando venta: " . $e->getMessage());
            $jsonResponse->message = "Error anulando venta";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function updateVenta(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE ventas SET producto_id = :producto_id, seller_id = :seller_id, cantidad = :cantidad, total = :total WHERE id = :id");
            $stmt->execute([
                'id' => $args['id'],
                'producto_id' => $data['producto_id'],
                'seller_id' => $data['seller_id'],
                'cantidad' => $data['cantidad'],
                'total' => $data['total']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Venta actualizada exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error actualizando venta: " . $e->getMessage());
            $jsonResponse->message = "Error actualizando venta";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

}
