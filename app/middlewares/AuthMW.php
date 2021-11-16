<?php

use GuzzleHttp\Psr7\Response;

class AuthMW
{
    public static function ValidarToken($request, $handler){
        $token = $request->getHeader("token");
        $validacionToken = AutentificadorJWT::ObtenerPayload($token[0]);
        if($validacionToken["Estado"] == "OK"){
            $request = $request->withAttribute("payload", $validacionToken);
            $response = $handler->handle($request);
            return $response;
        }
        else{
            $response = new Response();
            $response = $response->withStatus(403, 'Error de Token');
            return $response;
        }
    }

    public static function ValidarSocio($request, $handler)
    {
        $payload = $request->getAttribute('payload')['Payload'];
        if($payload->rol == "socio"){
            return $handler->handle($request);;
        }
        else{
            $response = new Response();
            $response = $response->withStatus(403, "Forbidden");
            return $response;
        }
        $response = $handler->handle($request);
        return $response;
    }

    public static function ValidarMozo($request, $handler)
    {
        $payload = $request->getAttribute('payload')['Payload'];
        if($payload->rol == "socio" || $payload->rol == "mozo"){
            return $handler->handle($request);;
        }
        else{
            $response = new Response();
            $response = $response->withStatus(403, "Forbidden");
            return $response;
        }
        $response = $handler->handle($request);
        return $response;
    }
}