<?php namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\JsonResponse;
use Psr\Log\LoggerInterface;
use PDO;
use Ramsey\Uuid\Uuid;

class ProductController extends BaseController {
    private $logger;
    private $pdo;

    public function __construct(LoggerInterface $logger, PDO $pdo) {
        $this->logger = $logger;
        $this->pdo = $pdo;
    }

    public function getProducts(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->query("SELECT id, nombre, descripcion, precio FROM productos WHERE activo = TRUE");
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " productos encontrados";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando productos: " . $e->getMessage());
            $jsonResponse->message = "Error buscando productos";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getProductLikeName(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $stmt = $this->pdo->prepare("SELECT id, nombre, descripcion, precio FROM productos WHERE nombre LIKE :name AND activo = TRUE");
            $stmt->execute([
                'name' => '%' . $args['name'] . '%'
            ]);
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " productos encontrados con el nombre similar";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando productos por nombre: " . $e->getMessage());
            $jsonResponse->message = "Error buscando productos por nombre";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function createProduct(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO productos (id, nombre, descripcion, precio) VALUES (:id, :nombre, :descripcion, :precio)");
            $stmt->execute([
                'id' => Uuid::uuid4()->toString(),
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'precio' => $data['precio']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Producto creado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error creando producto: " . $e->getMessage());
            $jsonResponse->message = "Error creando producto";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function updateProduct(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio WHERE id = :id");
            $stmt->execute([
                'id' => $args['id'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'precio' => $data['precio']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Producto actualizado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error actualizando producto: " . $e->getMessage());
            $jsonResponse->message = "Error actualizando producto";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function deleteProduct(Request $request, Response $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE productos SET activo = FALSE WHERE id = :id");
            $stmt->execute([
                'id' => $args['id']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Producto eliminado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Error eliminando producto: " . $e->getMessage());
            $jsonResponse->message = "Error eliminando producto";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}