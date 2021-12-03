<?php

<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once './models/Encuesta.php';
require_once './interfaces/IApiUsable.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();
        
        $cod = $parametros['codigo'];
        
        $mesa = new Mesa();
        $mesa->cod_mesa = $cod;
        $mesa->estado = $est;
        $mesa->crearMesa();

        $payload = json_encode(array("mensaje" => "Mesa $mesa->cod_mesa creada con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler, $args)
    {
        // Buscamos Mesa por codigo
        $m = $args['codigo'];
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
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        
        $data = json_decode(AutentificadorJWT::ObtenerData($token));

        $parametros = $request->getParsedBody();
        
        $cod = $parametros['codigo'];
        $m = Mesa::obtenerMesa($cod);
        $e = $parametros['estado'];

        $response = new Response();

        if(!Mesa::validarEstado($e)){ return $response->withStatus(400, "estado no valido"); }
        $m->estado = $e;
        if($e == "cerrada" && $data->rol != 'socio'){ 
          return $response->withStatus(403, "solo un socio puede cerrar una mesa");
        }
        $m->modificarMesa();
        
        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));

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



?>