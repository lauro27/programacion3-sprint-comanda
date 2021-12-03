<?php

use Slim\Psr7\Response;

require_once './models/Pedido.php';
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class PedidoController extends Pedido implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $requestHeader = $request->getHeaderLine('Authorization');
        $elToken = trim(explode('Bearer', $requestHeader)[1]);

        $parametros = $request->getParsedBody();
        $payload = json_decode(AutentificadorJWT::ObtenerData($elToken));
        $id = $payload->id;
        $cliente = $parametros['cliente'];
        $idMesa = $parametros['mesa'];

        $mesa = Mesa::obtenerMesa($idMesa);
        // Creamos el pedido
        $ped = new Pedido();
        $ped->id_usuario = $id;
        $ped->nombre_cliente = $cliente;
        $ped->cod_mesa = $idMesa;
        $ped->cod_pedido = CsvHandler::GenerarCodigo();
        $ped->crearPedido();
        //TODO: FOTO
        $payload = json_encode(array("mensaje" => "Pedido $ped->cod_pedido en mesa $ped->cod_mesa creado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response

        ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler, $args)
    {
        $id = $args['codigo'];
        $pedido = Pedido::obtenerPedido($id);

        $response = new Response();
        if(isset($pedido->id)){
            $payload = json_encode($pedido);
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUnoConMesa($request, $handler, $args)
    {
        $id = $args['codigo'];
        $mesa = $args['mesa'];
        $pedido = Pedido::obtenerConMesa($id, $mesa);

        $response = new Response();
        if(isset($pedido->id)){
            $productos = array();
            foreach ($pedido->id_productos as $key => $value) {
                $prod = Producto::obtenerPorId($value);
                if($prod){
                    array_push($productos, $prod);
                }
            }
            
            $payload = json_encode($pedido) . "\nProductos: \n" . json_encode($productos);

            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $handler)
    {
        $lista = Pedido::obtenerTodos();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    
    public function ModificarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        if($ped->nombre_cliente != NULL){
            $msg = $ped->cambiarEstado();
            $payload = json_encode(array("mensaje" => "$msg"));
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(400, "Estado no valido");    
        }

        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $pedidoId = $parametros['pedidoId'];
        Pedido::borrarPedido($pedidoId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function sumarProducto($request, $handler)
    {
        $parametros = $request->getParsedBody();
        $producto = $parametros['producto'];
        $id = $parametros['codigo'];

        $ped = Pedido::obtenerPorCodigo($id);
        $response = new Response();
        if($ped)
        {
            $payload = $ped->agregarProducto($producto);
            $response->getBody()->write($payload);
        }
        else{$response = $response->withStatus(400);}
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function setEstimado($request, $handler)
    {
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        
        $data = json_decode(AutentificadorJWT::ObtenerData($token));

        $parametros = $request->getParsedBody();
        $estimado = intval($parametros['estimado']);
        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);
        $sector = $parametros['sector'];
        $prod = $ped->obtenerProductosPorSector($sector);
        $response = new Response();
        try{
            if($ped->nombre_cliente == NULL){throw new Exception("No se encuentra pedido");}
            if($ped->estimado > $estimado){throw new Exception("Estimado menor al actual");}
            if(count($prod) == 0){throw new Exception("El pedido no tiene productos");}
            $ped->estimado = $estimado;
            $ped->actualizarEstimado();
            return $response->withStatus(200);
        }
        catch(Exception $e){
            echo $e->getMessage();
            return $response->withStatus(400, $e);
        }
        finally{
            return $response;
        }
    }

    public function TraerProductosPedidoPorSector($request, $handler, $args)
    {
        $response = new Response();
        $sector = $args['sector'];
        if(!Producto::validarSector($sector)){
            return $response->withStatus(400, "sector invalido");
        }
        $pedido = $args['cod_pedido'];

        $ped = Pedido::obtenerPorCodigo($pedido);
        if($pedido)
        {
            $productos = $ped->obtenerProductosPorSector($sector);
            $response->getBody()->write(json_encode($productos));

        }
        else{
            return $response->withStatus(404, "no se encuentra pedido");
        }
        return $response->withHeader('Content-Type', 'application/json');;

    }
    public function MostrarUno($request, $handler, $args)
    {
        $id = $args['codigo'];
        $pedido = Pedido::obtenerPedido($id);

        $response = new Response();
        if(isset($pedido->id)){
            $payload = json_encode($pedido);
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public static function TraerListos($request, $handler){
        $lista = Pedido::obtenerListos();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerCuenta($request, $handler)
    {
        
        $parametros = $request->getParsedBody();
        $id = $parametros['codigo'];
        $pedido = Pedido::obtenerPorCodigo($id);

        $response = new Response();
        if(isset($pedido->id)){
            $total = 0;
            $a = $pedido->ObtenerProductos();
            $listaProd = array();
            foreach ($a as $key => $value) {
                $total += $value->precio;
                array_push($listaProd, $value->nombre);
            }
            $payload = "Productos: " . json_encode($listaProd);
            $payload .= " - Total: " . $total;
            $response->getBody()->write($payload);

            //mesa pagando
            $mesa = Mesa::obtenerMesa($pedido->cod_mesa);
            $mesa->estado = 'pagando';
            $mesa->modificarMesa();
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        
        return $response
          ->withHeader('Content-Type', 'application/json');        
    }
}
