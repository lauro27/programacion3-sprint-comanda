<?php

use Slim\Psr7\Response;

require_once './models/Pedido.php';
require_once './interfaces/IApiUsable.php';
require_once './models/AuthJWT.php';

class PedidoController extends Pedido implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();
        $payload = $request->getAttribute("payload")["Payload"];
        $id = $payload->id;
        $cliente = $parametros['cliente'];
        
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
        $ped->cambiarEstado($estado);

        $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
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

        
    }
}
