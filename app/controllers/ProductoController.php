<?php

use Slim\Psr7\Response;

require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function CargarUno($request, $handler){
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $precio = $parametros['precio'];
        $sector = $parametros['sector'];

        // Creamos el producto
        $prod = new Producto();
        $prod->nombre = $nombre;
        $prod->precio = $precio;
        $prod->sector = $sector;
        $prod->crearProducto();

        $payload = json_encode(array("mensaje" => "Producto $prod->nombre creado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler, $args){
        // Buscamos producto por nombre
        $prod = $args['nombre'];
        $producto = Producto::obtenerProducto($prod);
        $payload = json_encode($producto);

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $handler){
        $lista = Producto::obtenerTodos();
        $payload = json_encode(array("listaProducto" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $handler){
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $precio = $parametros['precio'];
        $sector = $parametros['sector'];
        Producto::modificarProducto($nombre);

        $payload = json_encode(array("mensaje" => "Producto modificado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $handler){
      $parametros = $request->getParsedBody();

      $productoId = $parametros['productoId'];
      Usuario::borrarUsuario($productoId);

      $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

      $response = new Response();
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    public function TraerPorSector($request, $handler, $args){
      $parametros = $request->getParsedBody();
      $sector = $args['sector'];
      $lista = Producto::obtenerSector($sector);
      
      $payload = json_encode(array("listaProducto" => $lista));

      $response = new Response();
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
}