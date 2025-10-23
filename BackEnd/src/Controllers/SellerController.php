<?php namespace App\Controllers;

use App\Models\JsonResponse;
use Psr\Log\LoggerInterface;
use PDO;
use Ramsey\Uuid\Uuid;

class SellerController extends BaseController {
    private $logger;
    private $pdo;

    public function __construct(LoggerInterface $logger, PDO $pdo) {
        $this->logger = $logger;
        $this->pdo = $pdo;
    }
    public function getOwnerData($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try{
            $stmt = $this->pdo->query("SELECT nombre, contacto, comision FROM sellers WHERE is_owner = TRUE LIMIT 1");
            $jsonResponse->data = $stmt->fetch(PDO::FETCH_ASSOC);
            $jsonResponse->message = "propietario encontrado";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando datos del propietario: " . $e->getMessage());
            $jsonResponse->message = "Error buscando datos del propietario";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getSellers($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try{
            $stmt = $this->pdo->query("SELECT id, nombre, contacto, comision FROM sellers WHERE activo = TRUE and is_owner = FALSE");
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " vendedores encontrados";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando vendedores: " . $e->getMessage());
            $jsonResponse->message = "Error buscando vendedores";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getSellerByName($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try{
            $stmt = $this->pdo->prepare("SELECT id, nombre, contacto, comision FROM sellers WHERE activo = TRUE and is_owner = FALSE AND nombre LIKE :name");
            $stmt->execute([
                'name' => '%' . $args['name'] . '%'
            ]);
            $jsonResponse->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jsonResponse->message = $stmt->rowCount() . " vendedores encontrados con el nombre similar";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error buscando vendedores por nombre: " . $e->getMessage());
            $jsonResponse->message = "Error buscando vendedores por nombre";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function createSeller($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO sellers (id, nombre, contacto, comision) VALUES (:id, :nombre, :contacto, :comision)");
            $stmt->execute([
                'id' => Uuid::uuid4()->toString(),
                'nombre' => $data['nombre'],
                'contacto' => $data['contacto'],
                'comision' => $data['comision']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Vendedor creado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error("Error creando vendedor: " . $e->getMessage());
            $jsonResponse->message = "Error creando vendedor";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function updateSeller($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $data = json_decode($request->getBody(), true);
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE sellers SET nombre = :nombre, contacto = :contacto, comision = :comision WHERE id = :id AND activo = TRUE");
            $stmt->execute([
                'id' => $args['id'],
                'nombre' => $data['nombre'],
                'contacto' => $data['contacto'],
                'comision' => $data['comision']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Vendedor actualizado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error actualizando vendedor: " . $e->getMessage());
            $jsonResponse->message = "Error actualizando vendedor";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function deleteSeller($request, $response, $args) {
        $jsonResponse = new JsonResponse();
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE sellers SET activo = FALSE WHERE id = :id");
            $stmt->execute([
                'id' => $args['id']
            ]);
            $this->pdo->commit();
            $jsonResponse->message = "Vendedor eliminado exitosamente";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error("Error eliminando vendedor: " . $e->getMessage());
            $jsonResponse->message = "Error eliminando vendedor";
            $response->getBody()->write(json_encode($jsonResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}