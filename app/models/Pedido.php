<?php

use Psr7Middlewares\Middleware\AccessLog;

require_once './models/Producto.php';

class Pedido
{
    public $id;
    public $id_usuario;
    public $nombre_cliente;
    public $dir_foto = NULL;
    public $array_producto = array();
    public $estado = 'preparando';
    public $estimado;

    public function crearPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO pedidos (id_usuario, nombre_cliente, dir_foto, array_producto) VALUES (:id_usuario, :nombre_cliente, :dir_foto, :array)");
        
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre_cliente', $this->nombre_cliente);
        $consulta->bindValue(':dir_foto', $this->dir_foto);
        $consulta->bindValue(':array', '[]');

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public function agregarProducto($producto){
        try{
            $prod = Producto::obtenerProducto($producto);
            if(!isset($prod->nombre)){
                throw new Exception('Producto no encontrado');
            }
            array_push($this->array_producto, $prod->id);
            $this->modificarPedido();
            $mensaje = "Exito con agregar producto " . $producto;
        }catch(Exception $e){
            $mensaje = $e->getMessage();
        }
        return $mensaje;
    }

    public function cambiarEstado($estado)
    {
        try{
            if(!Pedido::validarEstado($estado)){
                throw new Exception('Estado no valido');
            }
            $this->estado = $estado;
            $this->modificarPedido();
            $mensaje = "Pedido ". $this->id . " cambiado a " . $this->estado;
        }catch(Exception $e){
            $mensaje = $e->getMessage();
        }
        return $mensaje;
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, id_usuario, nombre_cliente, dir_foto, array_producto FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerPedido($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, id_usuario, nombre_cliente, dir_foto, array_producto FROM usuarios WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $pedido = $consulta->fetchObject('Pedido');
        $pedido->array_producto = json_decode($pedido->array_producto);
        return $pedido;
    }

    public function modificarPedido()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE pedidos SET array_producto = :array_producto, estado = :estado WHERE id = :id");
        $consulta->bindValue(':array_producto', $this->array_producto, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->execute();
    }

    public static function borrarPedido($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM pedidos where id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function validarEstado($estado)
    {
        return ($estado == 'preparando' || $estado == 'listo');
    }

    public function actualizarEstimado()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE pedidos set estimado = :estimado where id = :id");
        $consulta->bindValue(":estimado", $this->estimado, PDO::PARAM_INT);
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->execute();

    }
}