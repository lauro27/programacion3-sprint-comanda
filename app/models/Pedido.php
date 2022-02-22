<?php

use Psr7Middlewares\Middleware\AccessLog;
use Symfony\Component\Console\Descriptor\JsonDescriptor;

require_once './models/Producto.php';

class Pedido
{
    public $id;
    public $id_usuario;
    public $nombre_cliente;
    public $id_producto;
    public $estado = 'recibido';
    public $estimado;
    public $hora_inicio;
    public $hora_entrega = NULL;
    public $cod_mesa;
    public $cod_pedido;

    public function crearPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "INSERT INTO pedidos (id_usuario, nombre_cliente, id_producto, estado, hora_inicio, cod_mesa, cod_pedido)
            VALUES (:id_usuario, :nombre_cliente, :id_producto, :estado, :hora_inicio, :cod_mesa, :cod_pedido)");
        
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre_cliente', $this->nombre_cliente);
        $consulta->bindValue(':id_producto', $this->id_producto);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':cod_mesa', $this->cod_mesa);
        $consulta->bindValue(':cod_pedido', $this->cod_pedido);

        $this->hora_inicio = date('Y-m-d H:i:s');
        $consulta->bindValue(':hora_inicio', $this->hora_inicio);

        $consulta->execute();

        $mesa = Mesa::obtenerMesa($this->cod_mesa);
        if($mesa->estado != "esperando")
        {
            $mesa->estado = "esperando";
            $mesa->modificarMesa();
        }

        return $objAccesoDatos->obtenerUltimoId();
    }

    

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos");

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        foreach ($respuesta as $key => $value) {
            $respuesta[$key]->id_producto = json_decode($respuesta[$key]->id_producto);
        }
        return $respuesta;
    }

    public static function obtenerListos()
    {
        $e = "listo";
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos WHERE estado = :est");
        $consulta->bindValue(":est", $e);

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        foreach ($respuesta as $key => $value) {
            $respuesta[$key]->id_producto = json_decode($respuesta[$key]->id_producto);
        }
        return $respuesta;
    }

    public static function obtenerPedido($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos WHERE id = :id");

        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $pedido = $consulta->fetchObject('Pedido');
        return $pedido;
    }

    public static function obtenerPorCodigo($cod)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT * FROM pedidos WHERE cod_pedido = :cod");

        $consulta->bindValue(':cod', $cod);
        $consulta->execute();
        $pedido = $consulta->fetchObject('Pedido');
        return $pedido;
    }

    public static function obtenerConMesa($cod, $mesa)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT * FROM pedidos WHERE cod_pedido = :cod and cod_mesa = :mesa");
        $consulta->bindValue(':cod', $cod);
        $consulta->bindValue(':mesa', $mesa);
        $consulta->execute();
        $pedido = $consulta->fetchObject('Pedido');
        return $pedido;
    }

    public function modificarPedido()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta(
            "UPDATE pedidos 
            SET id_producto = :id_producto, 
                estado = :estado, 
                estimado = :estimado, 
                hora_entrega = :hora_entrega
                WHERE id = :id");
        $consulta->bindValue(':id_producto', $this->id_producto, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':estimado', $this->estimado);
        $entrega = date('Y-m-d H:i:s', strtotime($this->hora_entrega));
        var_dump($entrega);
        $consulta->bindValue(':hora_entrega', $entrega);
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarPedido($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta(
            "DELETE FROM pedidos where id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function validarEstado($estado)
    {
        return ($estado == 'recibido' || 
            $estado == 'preparando' || 
            $estado == 'listo' || 
            $estado == 'entregado' ||
            $estado == 'pagado' ||
            $estado == 'cancelado');
    }

    public function actualizarEstimado()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta(
            "UPDATE pedidos set estimado = :estimado where id = :id");
        $consulta->bindValue(":estimado", $this->estimado);
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public function obtenerProductosPorSector($sector){
        $resultado = array();

        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta(
            "SELECT * FROM productos where sector = :sector AND id = :id");
        $consulta->bindValue(':sector', $sector);
        foreach ($this->id_producto as $key => $value) {
            $consulta->bindValue(':id', $value, PDO::PARAM_INT);
            $consulta->execute();
            $temp = $consulta->fetchObject('Producto');
            if(isset($temp->nombre))
            {
                array_push($resultado, $temp);
            }
        }
        
        return $resultado;
    }

    public static function ObtenerMesaMasUsada(){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta(
            "SELECT cod_mesa, count(*) FROM pedidos GROUP BY cod_mesa limit 1");
        $consulta->execute();

        $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
        return $resultado['cod_mesa'];
    }

    public static function obtenerTodosTardios()
    {
        $listo = "listo";
        $entregado = "entregado";
        $pagado = "pagado";
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT * FROM pedidos 
                WHERE (estado = :listo OR estado = :entregado OR estado = :pagado) 
                AND hora_entrega > estimado");
        $consulta->bindValue(":listo", $listo);
        $consulta->bindValue(":entregado", $entregado);
        $consulta->bindValue(":pagado", $pagado);

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        $array = array();

        return json_encode($array);
    }

    public static function obtenerTodosEnTiempo()
    {
        $listo = "listo";
        $entregado = "entregado";
        $pagado = "pagado";
        
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT * FROM pedidos 
                WHERE (estado = :listo OR estado = :entregado OR estado = :pagado) 
                AND hora_entrega <= estimado");
        $consulta->bindValue(":listo", $listo);
        $consulta->bindValue(":entregado", $entregado);
        $consulta->bindValue(":pagado", $pagado);

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        $array = array();

        return json_encode($array);
    }

    public static function ObtenerTodosPorMesa(string $codigo, string $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos 
                WHERE cod_mesa = :cod_mesa and estado = :estado");
        $consulta->bindValue(":codigo", $codigo);
        $consulta->bindValue(":estado", $estado);

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        foreach ($respuesta as $key => $value) {
            $respuesta[$key]->id_producto = json_decode($respuesta[$key]->id_producto);
        }
        return $respuesta;
    }

    public static function ObtenerPorRol(string $rol)
    {
        $sPrep = "preparando";
        $sRecibido = "recibido";
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT * FROM pedidos 
                WHERE (estado = :recibido or estado = :preparando)");
                $consulta->bindValue(":recibido", $sRecibido);
                $consulta->bindValue(":preparando", $sPrep);
        $consulta->execute();
        $todos = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        $resultado = array();
        foreach ($todos as $key => $value) {
            $prod = Producto::obtenerPorId($value->id_producto);
            if($prod->SectorCorrecto($rol))
            {
                array_push($resultado, $value);
            }
        }

        return json_encode($resultado);
    }
}