<?php

use Psr7Middlewares\Middleware\Payload;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $cod = CsvHandler::GenerarCodigo();

        // Creamos la mesa
        $mesa = new Mesa();
        $mesa->cod_mesa = $cod;
        
        //FOTO ACA
        $files = $request->getUploadedFiles();

        if($files['foto'])
        {
            if(!file_exists('Mesas/')){
                mkdir('Mesas/',0777, true);
            }
            $foto = $files['foto'];
            $media = $foto->getClientMediaType();
            $ext = explode("/", $media)[1];
            $type = explode("/", $media)[0];
            if($type == "image")
            {
              $ruta = "./Mesas/" . $mesa->cod_mesa . "." . $ext;
              $foto->moveTo($ruta);
            }
            else{$ruta = "";}
        }
        else{$ruta = "";}

        //Payload
        $mesa->dir_foto = $ruta;
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

    public function TraerCuenta($request, $handler)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['codigo'];
        $mesa = Mesa::obtenerMesa($id);

        $response = new Response();
        if(isset($mesa->id)){
            $total = 0;
            $a = Pedido::ObtenerTodosPorMesa($mesa->cod_mesa, "entregado");
            $listaProd = array();
            if(count($a) > 0){
                foreach ($a as $key => $value) {
                    $prod = Producto::obtenerPorId($value->id_producto);
                    $total += $prod->precio;
                    array_push($listaProd, json_encode($prod));
                }
                $payload = "Productos: " . json_encode($listaProd);
                $payload .= " - Total: " . $total;
                $response->getBody()->write($payload);

                //mesa pagando
                $mesa->estado = 'pagando';
                $mesa->modificarMesa();
            }
            else{
                $response->withStatus(400, "mesa sin pedidos entregados");
            }
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        
        return $response
          ->withHeader('Content-Type', 'application/json');        
    }

    public static function PagarMesa($request, $handler)
    {
      $parametros = $request->getParsedBody();
      $id = $parametros['codigo'];
      $mesa = Mesa::obtenerMesa($id);
      $response = new Response();
      if(isset($mesa->id)){
          $total = 0;
          $a = Pedido::ObtenerTodosPorMesa($mesa->cod_mesa, "entregado");
          $listaProd = array();
          if(count($a) > 0){
              foreach ($a as $key => $value) {
                $value->estado = 'pagado';
                $value->modificarPedido();
              }
              //mesa cerrada
              $mesa->estado = 'cerrada';
              $mesa->modificarMesa();

              $response->getBody()->write("pedidos de mesa " . $id . " pagados y cerrados");
          }
          else{
              $response->withStatus(400, "mesa sin pedidos entregados");
          }
      }
      else{
          $response->withStatus(404, "No se encuentra pedido");
      }
      
      return $response
        ->withHeader('Content-Type', 'application/json');        
    }

    public static function MejorMesa($request, $handler)
    {
      $payload = Pedido::ObtenerMesaMasUsada();
      $response = new Response();
      $response->getBody()->write($payload);
      return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
