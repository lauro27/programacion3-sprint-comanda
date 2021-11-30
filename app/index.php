<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

require_once './db/AccesoDatos.php';
require_once './middlewares/Logger.php';
require_once './middlewares/AuthMW.php';
require_once './utils/AuthJWT.php';
require_once './utils/CsvHandler.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/LoginController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/ProductoController.php';


// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

// Routes

$app->post('/login[/]', \LoginController::class . ':IniciarSesion');

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');
    $group->get('/{usuario}', \UsuarioController::class . ':TraerUno');
    $group->post('[/]', \UsuarioController::class . ':CargarUno');
})->add(\AuthMW::class . ':LoginSocio');

$app->group('/productos', function (RouteCollectorProxy $group){
  $group->get('[/]', \ProductoController::class . ':TraerTodos');
    $group->get('/{producto}', \ProductoController::class . ':TraerUno');
    $group->post('[/]', \ProductoController::class . ':CargarUno')
      ->add(\AuthMW::class . ':LoginSocio')
      ->add(new Logger("Agrega producto"));
});

$app->group('/mesas', function (RouteCollectorProxy $group){
  $group->get('[/]', \MesaController::class . ':TraerTodos')
    ->add(new Logger("Busca todas las mesas"));
  $group->get('/{mesa}', \MesaController::class . ':TraerCodigo')
    ->add(new Logger("Busca mesa"));
  $group->post('[/]', \MesaController::class . ':CargarUno')
    ->add(\AuthMW::class . ':LoginSocio')  
    ->add(new Logger("Agrega mesa"));
  $group->post('/estado', \MesaController::class . ":ModificarUno")
    ->add(new Logger("modificado estado de mesa"));
})->add(\AuthMW::class . ':LoginSocioMozo');

$app->group('/pedidos', function (RouteCollectorProxy $group){
  $group->get('[/]', \PedidoController::class . ':TraerTodos')
    ->add(new Logger("Busca todos los pedidos"));
  $group->get('/codigo/{pedido}', \PedidoController::class . ':TraerCodigo')
    ->add(new Logger("Busca pedido"));
  $group->post('[/]', \PedidoController::class . ':CargarUno')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger("Nuevo pedido"));
  $group->put('[/]', \PedidoController::class . ':sumarProducto')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger("Suma producto a pedido"));
  $group->post('/estado', \PedidoController::class . ':ModificarUno')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Cambiar estado de pedido'));
  $group->get('/sector/{cod_pedido}/{sector}', \PedidoController::class . ":TraerProductosPedidoPorSector")
    ->add(\AuthMW::class . ':Login')
    ->add(new Logger('Verificar productos de pedido'));
  $group->put('/estimado[/]', \PedidoController::class . ":setEstimado")
    ->add(\AuthMW::class . ':Login')
    ->add(new Logger('Setear estimado en pedido'));
  $group->get('/{mesa}/{codigo}', \PedidoController::class.':TraerUnoConMesa');
  $group->get('/listos', \PedidoController::class . ":TraerListos")
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Busca todos los pedidos listos'));
  $group->post('/total', \PedidoController::class . ':TraerCuenta')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Pide Factura'));
});
  


$app->get('[/]', function (Request $request, Response $response) {    
  $response->getBody()->write("La comanda - Lamas Juan Pablo");
  return $response;
});

$app->run();
