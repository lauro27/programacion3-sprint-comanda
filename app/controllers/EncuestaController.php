<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once './models/Encuesta.php';
require_once './interfaces/IApiUsable.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $handler)
    {
        $parametros = $request->getQueryParams();
        
        $response = new Response();

        $cod = $parametros['codigo'];
        $rMozo = $parametros['mozo'];
        $rResta = $parametros['restaurante'];
        $rCocina = $parametros['cocinero'];
        $rMesa = $parametros['mesa'];
        
        $pedido = Pedido::obtenerPorCodigo($cod);

        if(!$pedido->estado == "listo")
        {
          return $response->withStatus(400, "Pedido no valido");
        }
        
        $encuesta = new Encuesta();
        $encuesta->cod_ped = $cod;
        $encuesta->rate_mozo = intval($rMozo);
        $encuesta->rate_restaurante = intval($rMozo);
        $encuesta->rate_cocinero = intval($rCocina);
        $encuesta->rate_mesa = intval($rMesa);
        
        $thisid = $encuesta->crearencuesta();

        $payload = json_encode(array("mensaje" => "Reseña $thisid para pedido $cod creada."));

        
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
        $lista = Mesa::obtenerMejores();
        $payload = json_encode(array("listaMesa" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
?>