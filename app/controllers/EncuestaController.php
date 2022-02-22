<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once './models/Encuesta.php';
require_once './interfaces/IApiUsable.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $handler, $args)
    {
      $parametros = $request->getParsedBody();

      $response = new Response();
      if(!isset($parametros['pedido']) || 
          !isset($parametros['mozo']) || 
          !isset($parametros['restaurante']) || 
          !isset($parametros['cocinero']) || 
          !isset($parametros['mesa']) || 
          !isset($parametros['cod_mesa']))
      {
        return $response->withStatus(400, "faltan numeros");
      }
      $cPed = $parametros['pedido'];
      $cMesa = $parametros['cod_mesa'];
      $rMozo = $parametros['mozo'];
      $rResta = $parametros['restaurante'];
      $rCocina = $parametros['cocinero'];
      $rMesa = $parametros['mesa'];
      
      
      $pedido = Pedido::obtenerConMesa($cPed, $cMesa);
      if($pedido->estado != "pagado")
      {
        return $response->withStatus(400, "Pedido no valido");
      }
      
      $encuesta = new Encuesta();
      $encuesta->cod_ped = $cPed;
      $encuesta->rate_mozo = intval($rMozo);
      $encuesta->rate_restaurante = intval($rResta);
      $encuesta->rate_cocinero = intval($rCocina);
      $encuesta->rate_mesa = intval($rMesa);
      $payload = json_encode(array("mensaje" => "Reseña para pedido $cPed creada."));
      
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
        $lista = Encuesta::obtenerTodos();
        $payload = json_encode(array("listaEncuesta" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    
    public function TraerMejores($request, $handler)
    {
        $lista = Encuesta::obtenerMejores();
        $payload = json_encode(array("listaEncuesta" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
?>