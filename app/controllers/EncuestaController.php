<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once './models/Encuesta.php';
require_once './interfaces/IApiUsable.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $handler, $args)
    {
        $args = $request->getQueryParams();
        
        $response = new Response();
        if(!isset($args['pedido']) || !isset($args['mozo']) || !isset($args['restaurante']) || !isset($args['cocinero']) || !isset($args['mesa']) || !isset($args['cod_mesa']))
        {
          return $response->withStatus(400, "faltan numeros");
        }
        var_dump($args);
        $cPed = $args['pedido'];
        $cMesa = $args['cod_mesa'];
        $rMozo = $args['mozo'];
        $rResta = $args['restaurante'];
        $rCocina = $args['cocinero'];
        $rMesa = $args['mesa'];
        
        
        $pedido = Pedido::obtenerConMesa($cPed, $cMesa);

        if(!$pedido->estado == "listo")
        {
          return $response->withStatus(400, "Pedido no valido");
        }
        
        $encuesta = new Encuesta();
        $encuesta->cod_ped = $cPed;
        $encuesta->rate_mozo = intval($rMozo);
        $encuesta->rate_restaurante = intval($rResta);
        $encuesta->rate_cocinero = intval($rCocina);
        $encuesta->rate_mesa = intval($rMesa);
        var_dump($encuesta);
        $thisid = $encuesta->crearencuesta();

        $payload = json_encode(array("mensaje" => "Reseña $thisid para pedido $cPed creada."));

        
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler, $args)
    {
      // Buscamos Mesa por codigo
      $id = $args['id'];
      $encuesta = Encuesta::obtenerEncuesta($id);
      
      $response = new Response();
      
      if($encuesta)
      {
        $payload = json_encode($encuesta);
        $response->getBody()->write($payload);  
      }
      else{
        $response->withStatus(400);
      }

      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $handler)
    {
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesa" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    
    public function TraerMejores($request, $handler)
    {
        $lista = Encuesta::obtenerMejores();
        $payload = json_encode(array("listaMesa" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
?>