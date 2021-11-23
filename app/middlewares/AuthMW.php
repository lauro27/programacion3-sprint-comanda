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

    public static function LoginSocio($request, $handler){
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        
        $response = new Response();
        
        try{
            $payload = json_decode(AutentificadorJWT::ObtenerData($token)); 
            if($payload->rol != "socio"){ throw new Exception("No autorizado");}
            $response = $handler->handle($request);
        }
        catch(Exception $e){
            $payload = json_encode(array('error'=> $e->getMessage()));
            $response = $response->withStatus(403);
        }
        return $response;
    }

    public static function LoginSocioMozo($request, $handler){
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        
        $response = new Response();
        
        try{
            $payload = json_decode(AutentificadorJWT::ObtenerData($token)); 
            if($payload->rol != "socio" && $payload->rol != 'mozo'){ throw new Exception("No autorizado");}
            
            $response = $handler->handle($request);
        }
        catch(Exception $e){
            $payload = json_encode(array('error'=> $e->getMessage()));
            $response = $response->withStatus(403);
        }
        return $response;
    }

    
    public static function Login($request, $handler){
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        
        $response = new Response();
        
        try{
            $payload = json_decode(AutentificadorJWT::ObtenerData($token)); 
            if(Usuario::validarRol($payload->rol)){ throw new Exception("No autorizado");}
            $response = $handler->handle($request);
        }
        catch(Exception $e){
            $payload = json_encode(array('error'=> $e->getMessage()));
            $response = $response->withStatus(403);
        }
        return $response;
    }
}