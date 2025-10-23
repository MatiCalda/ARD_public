<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\MiddleWare\AuthMiddleWare;
use App\Controllers\SellerController;
use App\Controllers\ProductController;
use App\Controllers\StockController;
use App\Controllers\VentaController;

$app->setBasePath('/api');

$app->group('', function (RouteCollectorProxy $group) {
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/recover', [AuthController::class, 'recoverPwd']); //TODO: Implementar
    $group->post('/change-password', [AuthController::class, 'changePwd']); //olvide la contraseña TODO: Implementar
    $group->post('/new-password', [AuthController::class, 'newPwd']); //la quiero cambiar TODO: Implementar

    $group->get('/helloworld', function ($request, $response, $args) {
    $response->getBody()->write(json_encode('Hello World'));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
});


$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/owner', [SellerController::class, 'getOwnerData']);
    $group->get('/sellers', [SellerController::class, 'getSellers']);
    $group->get('/sellers/{name}', [SellerController::class, 'getSellerByName']);
    $group->post('/seller', [SellerController::class, 'createSeller']);
    $group->put('/seller/{id}', [SellerController::class, 'updateSeller']);
    $group->delete('/seller/{id}', [SellerController::class, 'deleteSeller']);

    $group->get('/products', [ProductController::class, 'getProducts']);
    $group->get('/products/{name}', [ProductController::class, 'getProductLikeName']);
    $group->post('/product', [ProductController::class, 'createProduct']);
    $group->put('/product/{id}', [ProductController::class, 'updateProduct']);
    $group->delete('/product/{id}', [ProductController::class, 'deleteProduct']);

    $group->get('/stock', [StockController::class, 'getStock']);
    $group->post('/stock', [StockController::class, 'createStock']);
    $group->put('/stock/{id}', [StockController::class, 'updateStock']);

    $group->get('/ventas', [VentaController::class, 'getVentas']);
    $group->get('/ventas/seller/{seller_id}', [VentaController::class, 'getVentasBySeller']);
    $group->get('/ventas/date-range', [VentaController::class, 'getVentasByDateRange']);
    $group->post('/venta', [VentaController::class, 'createVenta']);
    //$group->put('/venta/{id}', [VentaController::class, 'updateVenta']);
    $group->delete('/venta/{id}', [VentaController::class, 'anularVenta']);
})->add(AuthMiddleware::class);


