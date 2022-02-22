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
require_once './controllers/EncuestaController.php';

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

date_default_timezone_set("America/Argentina/Buenos_Aires");

// Routes

$app->post('/login[/]', \LoginController::class . ':IniciarSesion')
  ->add(new Logger("Inicio de sesion"));

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos')
      ->add(new Logger("busca usuario"));
    $group->get('/{usuario}', \UsuarioController::class . ':TraerUno')
      ->add(new Logger("busca todos los usuarios"));
    $group->post('[/]', \UsuarioController::class . ':CargarUno')
      ->add(new Logger("creando usuario"));
    $group->post('/csv', \UsuarioController::class . ':CargarPorCsv')
      ->add(new Logger("creando usuarios por csv"));
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
  $group->get('/buscar/{mesa}', \MesaController::class . ':TraerCodigo')
    ->add(new Logger("Busca mesa"));
  $group->post('[/]', \MesaController::class . ':CargarUno')
    ->add(\AuthMW::class . ':LoginSocio')  
    ->add(new Logger("Agrega mesa"));
  $group->post('/estado', \MesaController::class . ":ModificarUno")
    ->add(new Logger("modificado estado de mesa"));
  $group->post('/borrar[/]', \MesaController::class . ":BorrarUno")
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Borra mesa"));
  $group->post('/cuenta[/]', \MesaController::class . ":TraerCuenta")
    ->add(new Logger("Traer cuenta de mesa"));
  $group->post('/cerrar[/]', \MesaController::class . ":PagarMesa")
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Cierra mesa"));
  $group->get('/mejor[/]', \MesaController::class . ":MejorMesa")
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Buscando mejor mesa"));
})->add(\AuthMW::class . ':LoginSocioMozo');



$app->group('/pedidos', function (RouteCollectorProxy $group){
  $group->get('[/]', \PedidoController::class . ':TraerTodos')
    ->add(\AuthMW::class . ':Login')  
    ->add(new Logger("Busca todos los pedidos"));
  
  $group->get('/codigo/{pedido}', \PedidoController::class . ':MostrarUno')
    ->add(new Logger("Busca pedido"));
  
  $group->post('[/]', \PedidoController::class . ':CargarUno')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger("Nuevo pedido"));
  
  $group->get('/sector[/]', \PedidoController::class . ":TraerTodosSector")
    ->add(\AuthMW::class . ':Login')
    ->add(new Logger('Verificar productos de pedido'));

  #region SETEO DE ESTADO
  $group->post('/preparando[/]', \PedidoController::class . ":SetearPreparando")
    ->add(\AuthMW::class . ':Login')
    ->add(new Logger('Preparando pedido'));
  
  $group->post('/listo[/]', \PedidoController::class . ":SetearListo")
    ->add(\AuthMW::class . ':Login')
    ->add(new Logger('Pedido listo'));
  
  $group->post('/entregado[/]', \PedidoController::class . ":SetearEntregado")
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Entrega pedido'));

  $group->post('/pagado[/]', \PedidoController::class . ":SetearPagado")
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger('Terminar pago de mesa'));
  
  $group->post('/cancelar[/]', \PedidoController::class . ":SetearCancelado")
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Cancelar pedido'));
  #endregion
  
  $group->get('/{mesa}/{codigo}', \PedidoController::class.':TraerUnoConMesa');
  
  $group->get('/listos', \PedidoController::class . ":TraerListos")
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Busca todos los pedidos listos'));
  
  $group->post('/total', \PedidoController::class . ':TraerCuenta')
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Pide Factura'));
  
  $group->get('/pdf', \PedidoController::class . ":TraerPdf")
    ->add(\AuthMW::class . ':LoginSocioMozo')
    ->add(new Logger('Genera PDF'));
  
  $group->get('/tarde', \PedidoController::class . ':TraerTardios')
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Buscando pedidos tardios"));
  
  $group->get('/puntual', \PedidoController::class . ':TraerPuntuales')
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Buscando pedidos a tiempo"));
});

$app->group("/encuesta",function (RouteCollectorProxy $group){
  $group->post("[/]", \EncuestaController::class . ":CargarUno");
  $group->get("/todos[/]", \EncuestaController::class . ':TraerTodos')
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Buscando reviews"));
  $group->get("/mejores[/]", \EncuestaController::class . ':TraerMejores')
    ->add(\AuthMW::class . ':LoginSocio')
    ->add(new Logger("Buscando mejores reviews"));  
});

$app->get('[/]', function (Request $request, Response $response) {    
  $response->getBody()->write("La comanda - Lamas Juan Pablo");
  return $response;
});

$app->run();
