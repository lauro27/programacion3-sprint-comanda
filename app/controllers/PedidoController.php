<?php

use Slim\Psr7\Response;

require_once './models/Pedido.php';
require_once './interfaces/IApiUsable.php';

class PedidoController extends Pedido implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $requestHeader = $request->getHeaderLine('Authorization');
        $elToken = trim(explode('Bearer', $requestHeader)[1]);

        $parametros = $request->getParsedBody();
        $payload = AutentificadorJWT::ObtenerData($elToken);
        $id = $payload->id;
        $cliente = $parametros['cliente'];
        $idMesa = $parametros['idMesa'];

        
        // Creamos el pedido
        $ped = new Pedido();
        $ped->id_usuario = $id;
        $ped->cliente = $cliente;
        $ped->crearPedido();

        $payload = json_encode(array("mensaje" => "Usuario creado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler)
    {
        // Buscamos usuario por nombre
        $parametros = $request->getParsedBody();
        $id = $parametros['id'];
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

        $estado = $parametros['estado'];
        $ped = Pedido::obtenerPedido($parametros['id']);

        $response = new Response();

        if(Pedido::validarEstado($estado)){
            $msg = $ped->cambiarEstado($estado);
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
        $id = $parametros['id'];

        $ped = Pedido::obtenerPedido($id);
        $payload = $ped->agregarProducto($producto);
        
        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
