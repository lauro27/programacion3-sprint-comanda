<?php

use Slim\Psr7\Response;

require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';
require_once './utils/CsvHandler.php';

class UsuarioController extends Usuario implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $usuario = $parametros['usuario'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];
        // Creamos el usuario
        $usr = new Usuario();
        $usr->usuario = $usuario;
        $usr->clave = $clave;
        $usr->rol = $rol;

        if(Usuario::validarRol($usr->rol)){
          if($usr->rol != "socio")
          {
            $usr->crearUsuario();
            $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
            $status = 200;
          }
          else{
            $payload = json_encode(array("mensaje" => "Error: no puede haber mas socios"));
            $status = 400;
          }
        }
        else{
          $payload = json_encode(array("mensaje" => "Error: rol invalido"));
          $status = 400;
        }
        
        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus($status);
    }

    public function TraerUno($request, $handler, $args)
    {
        // Buscamos usuario por nombre
        $parametros = $request->getParsedBody();
        $usr = $parametros['usuario'];
        $usuario = Usuario::obtenerUsuario($usr);
        $payload = json_encode($usuario);

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $handler)
    {
        $lista = Usuario::obtenerTodos();
        $payload = json_encode(array("listaUsuario" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    
    public function ModificarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        Usuario::modificarUsuario($nombre);

        $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $usuarioId = $parametros['usuarioId'];
        Usuario::borrarUsuario($usuarioId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function CargarPorCsv($request, $handler)
    {
      $path = CsvHandler::ObtenerArchivo('archivoCSV');
      $response = new Response();
      if($path == null){ return $response->withStatus(400, "error con archivo");}
      
      $array = CsvHandler::ObtenerDatosUsuarios($path);

      foreach ($array as $key => $value) {
        $user = new Usuario();
        $user->usuario = $value['usuario'];
        $user->usuario = $value['clave'];
        $user->usuario = $value['rol'];

        $user->crearUsuario();
      }
      
      if(count($array) == 0)
      {
        $response->withStatus(404, "no hay usuarios en CSV");
      }
      else
      {
        $response->getBody()->write("Csv Cargado Correctamente");
      }
      return $response;
    }
}
