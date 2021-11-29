<?php
require_once './models/Usuario.php';
require_once './models/AuthJWT.php';

use Slim\Psr7\Response;

class LoginController{

    public function IniciarSesion($request, $handler){
        $arrayParam = $request->getParsedBody();
        $user = $arrayParam['usuario'];
        $pass = $arrayParam['clave'];

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $cmp = Usuario::obtenerUsuario($user);

        if(password_verify($pass, $cmp->clave)){
            $value = $cmp;
        }

        $response = new Response();
        if(!isset($value->usuario) || !is_null($value->fecha_baja)){
            $payload = json_encode(array("mensaje" => "Usuario no existente"));
            $response->getBody()->write($payload);
            return $response;
        }
        else{
            $datos = json_encode(array("usuario" => $value->usuario, "id" => $value->id, "rol" => $value->rol));
            $token = AutentificadorJWT::CrearToken($datos);
            $rol = $value->rol;
            $response->getBody()->write($token);
            return $response->withStatus(200, 'OK ' . $rol);
        }
    }

    public function ProbarDatos($request, $handler){
        $requestHeader = $request->getHeaderLine('Authorization');
        $elToken = trim(explode('Bearer', $requestHeader)[1]);

        $response = new Response();
        $response->getBody()->write(AutentificadorJWT::ObtenerData($elToken));
        return $response;
    }
}

?>