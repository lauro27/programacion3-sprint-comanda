<?php

use Slim\Psr7\Response;

require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $cod = $parametros['cod_mesa'];
        $est = "cerrada";

        // Creamos la mesa
        $mesa = new Mesa();
        $mesa->cod_mesa = $cod;
        $mesa->estado = $est;
        $mesa->crearMesa();

        $payload = json_encode(array("mensaje" => "Mesa creada con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler)
    {
        // Buscamos Mesa por codigo
        $parametros = $request->getParsedBody();
        $m = $parametros['cod_mesa'];
        $mesa = Mesa::obtenerMesa($m);
        $payload = json_encode($mesa);

        $response = new Response();
        $response->getBody()->write($payload);
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
    
    public function ModificarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();
        
        $cod = $parametros['cod_mesa'];
        $m = Mesa::obtenerMesa($cod);
        $e = $parametros['estado'];
        if(Mesa::validarEstado($e)){ $m->estado = $e; }
        $m->modificarMesa();
        
        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $MesaId = $parametros['idMesa'];
        Mesa::borrarMesa($MesaId);

        $payload = json_encode(array("mensaje" => "Mesa borrada con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
