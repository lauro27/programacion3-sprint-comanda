<?php

use Slim\Psr7\Response;
use Fpdf\Fpdf;

require_once './models/Pedido.php';
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';


class PedidoController extends Pedido implements IApiUsable
{
    public function CargarUno($request, $handler)
    {
        $requestHeader = $request->getHeaderLine('Authorization');
        $elToken = trim(explode('Bearer', $requestHeader)[1]);

        $parametros = $request->getParsedBody();
        $payload = json_decode(AutentificadorJWT::ObtenerData($elToken));
        $id = $payload->id;

        $response = new Response();
        
        if(isset($parametros['cliente']) && 
                isset($parametros['mesa']) && 
                isset($parametros["producto"]))
        {
            $cliente = $parametros['cliente'];
            $idMesa = $parametros['mesa'];
            $prod = Producto::obtenerProducto($parametros['producto']);
        }
        else{
            return $response->withStatus(400, "Faltan datos en request");
        }

        if(!isset($prod->nombre)){
            return $response->withStatus(404, "No se encuentra el producto");
        }

        $mesa = Mesa::obtenerMesa($idMesa);

        if(!$mesa)
        {
            return $response->withStatus(400, "Mesa no valida");
        }
        // Creamos el pedido
        $ped = new Pedido();
        $ped->id_usuario = $id;
        $ped->nombre_cliente = $cliente;
        $ped->cod_mesa = $idMesa;
        $ped->id_producto = $prod->id;
        $ped->cod_pedido = CsvHandler::GenerarCodigo();
        
        $ped->crearPedido();

        $payload = json_encode(array("mensaje" => "Pedido $ped->cod_pedido en mesa $ped->cod_mesa creado con exito"));

        $response->getBody()->write($payload);
        return $response

        ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $handler, $args)
    {
        $id = $args['codigo'];
        $pedido = Pedido::obtenerPorCodigo($id);

        $response = new Response();
        if(isset($pedido->id)){
            $payload = json_encode($pedido);
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUnoConMesa($request, $handler, $args)
    {
        $id = $args['codigo'];
        $mesa = $args['mesa'];
        $pedido = Pedido::obtenerConMesa($id, $mesa);

        $response = new Response();
        if(isset($pedido->id)){
            $prod = Producto::obtenerPorId($pedido->id_producto);
                        
            $payload = json_encode($pedido) . "\nProducto: \n" . $prod->nombre;

            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $handler)
    {
        $lista = Pedido::obtenerTodos();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    #region SETEO DE ESTADO

    public function SetearPreparando($request, $handler)
    {
        //decodear token
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);

        $data = json_decode(AutentificadorJWT::ObtenerData($token));

        //decodear request
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);
        $minEstimado = intval($parametros['minutos']);
        

        $response = new Response();
        //revisando si el pedido existe y no tiene estimado aun
        if($ped->id_producto == NULL || $ped->estado != "recibido")
        {
            $response->withStatus(400, "pedido no valido");

        }//revisando si el estimado en minutos existe y es mayor a 0
        elseif($minEstimado == NULL || $minEstimado <= 0)
        {
            $response->withStatus(400, "estimado no valido");
        }else{
            
            $producto = Producto::obtenerPorId($ped->id_producto);
            //revisando si el producto es del sector del empleado o si es socio
            if($producto->SectorCorrecto($data->rol)){
                //cuando todo esta bien, arrancamos
                $ped->estado = "preparando";
                $tiempoEstimado = new DateTime($ped->hora_inicio);
                $tiempoEstimado->modify(" + " . $minEstimado . " minute");
                $ped->estimado = $tiempoEstimado;
                $msg = $ped->modificarPedido();
                $payload = json_encode(array("mensaje" => "Pedido ". $ped->cod_pedido . " seteado a preparando."));
                $response->getBody()->write($payload);
            }
            else{
                echo("FAKE");
                $response->withStatus(403, "Forbidden: Rol invalido");
            }
        }

        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function SetearListo($request, $handler)
    {
        //decodear token
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);

        $data = json_decode(AutentificadorJWT::ObtenerData($token));

        //decodear request
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        //revisando si el pedido existe y esta en preparacion
        if($ped->estado != "preparando")
        {
            $response->withStatus(400, "pedido no valido");
        }else{
            $producto = Producto::obtenerPorId($ped->id_producto);
            //revisando si el producto es del sector del empleado o si es socio
            if($producto->SectorCorrecto($data->rol)){
                //cuando todo esta bien, arrancamos
                $ped->estado = "listo";
                $tiempoFinal = new DateTime("now");
                $ped->hora_entrega = $tiempoFinal;
                $msg = $ped->modificarPedido();
                $payload = json_encode(array("mensaje" => "Pedido ". $ped->cod_pedido . " seteado a listo."));
                $response->getBody()->write($payload);
            }
            else{
                $response->withStatus(403, "Forbidden: Rol invalido");
            }
        }

        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function SetearEntregado($request, $handler)
    {
        //decodear request
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        //revisando si el pedido existe y esta siendo preparado
        if($ped->estado != "listo")
        {
            $response->withStatus(400, "pedido no valido");
        }else{
            //cuando todo esta bien, arrancamos
            $ped->estado = "entregado";
            $msg = $ped->modificarPedido();
            $payload = json_encode(array("mensaje" => "Pedido ". $ped->cod_pedido . " seteado a entregado."));
            $response->getBody()->write($payload);
        }
        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function SetearPagado($request, $handler)
    {
        //decodear request
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        //revisando si el pedido existe y esta siendo preparado
        if($ped->estado != "entregado")
        {
            $response->withStatus(400, "pedido no valido");
        }else{
            //cuando todo esta bien, arrancamos
            $ped->estado = "pagado";
            $msg = $ped->modificarPedido();
            $payload = json_encode(array("mensaje" => "Pedido ". $ped->cod_pedido . " seteado a pagado."));
            $response->getBody()->write($payload);
        }
        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function SetearCancelado($request, $handler)
    {
        //decodear request
        $parametros = $request->getParsedBody();

        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        //revisando si el pedido existe y no fue entregado al cliente
        if($ped->estado == "pagado" || $ped->estado == "cancelado" || $ped->estado == "entregado")
        {
            $response->withStatus(400, "pedido no valido");
        }else{
            //cuando todo esta bien, arrancamos
            $ped->estado = "cancelado";
            $msg = $ped->modificarPedido();
            $payload = json_encode(array("mensaje" => "Pedido ". $ped->cod_pedido . " seteado a cancelado."));
            $response->getBody()->write($payload);
        }
        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    #endregion
    
    public function ModificarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $estado = $parametros['estado'];
        $ped = Pedido::obtenerPorCodigo($parametros['codigo']);

        $response = new Response();

        if($ped->nombre_cliente != NULL && Pedido::validarEstado($estado)){
            $ped->estado = $estado;
            $msg = $ped->cambiarEstado();
            $payload = json_encode(array("mensaje" => "$msg"));
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(400, "Estado no valido");    
        }

        
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $handler)
    {
        $parametros = $request->getParsedBody();

        $pedidoId = $parametros['pedidoId'];
        Pedido::borrarPedido($pedidoId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function MostrarUno($request, $handler, $args)
    {
        $id = $args['codigo'];
        $pedido = Pedido::obtenerPedido($id);

        $response = new Response();
        if(isset($pedido->id)){
            $payload = json_encode($pedido);
            $response->getBody()->write($payload);
        }
        else{
            $response->withStatus(404, "No se encuentra pedido");
        }
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public static function TraerListos($request, $handler){
        $lista = Pedido::obtenerListos();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public static function TraerPdf($request, $handler)
    {
        $lista = Pedido::obtenerTodos();
        $pdf = new Fpdf('L');
        $pdf->AddPage();
        $pdf->Image("./logo.png");
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        foreach ($lista as $key => $value) {
            
            $pdf->Cell(8, 6, $value->id, 1);
            $pdf->Cell(8, 6, $value->id_usuario, 1);
            $pdf->Cell(30, 6, $value->nombre_cliente, 1);
            $pdf->Cell(15, 6, $value->cod_mesa, 1);
            $pdf->Cell(15, 6, $value->cod_pedido, 1);
            $pdf->Cell(20, 6, $value->id_producto, 1);
            $pdf->Cell(40, 6, $value->hora_inicio, 1);
            $pdf->Cell(40, 6, $value->hora_entrega, 1);
            $pdf->Ln();
        }
        $content = $pdf->Output('S');


        $response = new Response();
        $body = $response->getBody();
        $body->write($content);
        return $response
            ->withBody($body)
            ->withHeader('Content-Type', 'application/pdf')
            ->withStatus(200);
    }

    public function TraerTardios($request, $handler)
    {
        $lista = Pedido::obtenerTodosTardios();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerPuntuales($request, $handler)
    {
        $lista = Pedido::obtenerTodosEnTiempo();
        $payload = json_encode(array("listaPedido" => $lista));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodosSector($request, $handler)
    {
        //decodear token
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);

        $data = json_decode(AutentificadorJWT::ObtenerData($token));

        $array = Pedido::ObtenerPorRol($data->rol);
        $payload = json_encode(array("listaPedido" => $array));

        $response = new Response();
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}