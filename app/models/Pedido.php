<?php

use Psr7Middlewares\Middleware\AccessLog;
use Symfony\Component\Console\Descriptor\JsonDescriptor;

require_once './models/Producto.php';

class Pedido
{
    public $id;
    public $id_usuario;
    public $nombre_cliente;
    public $dir_foto = NULL;
    public $id_productos = array();
    public $estado = 'preparando';
    public $estimado; // en minutos
    public $hora_inicio;
    public $hora_entrega = NULL;

    public function crearPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO pedidos (id_usuario, nombre_cliente, dir_foto, id_productos, estado, hora_inicio)
            VALUES (:id_usuario, :nombre_cliente, :dir_foto, :array, :estado, :hora_inicio)");
        
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre_cliente', $this->nombre_cliente);
        $consulta->bindValue(':dir_foto', $this->dir_foto);
        $consulta->bindValue(':array', '[]');
        $consulta->bindValue(':estado', $this->estado);

        $this->hora_inicio = date('Y-m-d H:i:s');
        $consulta->bindValue(':hora_inicio', $this->hora_inicio);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public function agregarProducto($producto){
        try{
            $prod = Producto::obtenerProducto($producto);
            if(!isset($prod->nombre)){
                throw new Exception('Producto no encontrado');
            }
            array_push($this->id_productos, $prod->id);
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
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, id_usuario, nombre_cliente, dir_foto, id_productos 
            FROM pedidos");

        $consulta->execute();

        $respuesta = $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
        foreach ($respuesta as $key => $value) {
            $respuesta[$key]->id_productos = json_decode($respuesta[$key]->id_productos);
        }
        return $respuesta;
    }

    public static function obtenerPedido($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, id_usuario, nombre_cliente, dir_foto, id_productos 
            FROM pedidos WHERE id = :id");

        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $pedido = $consulta->fetchObject('Pedido');
        $pedido->id_productos = json_decode($pedido->id_productos);
        return $pedido;
    }

    public function modificarPedido()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE pedidos 
        SET id_productos = :id_productos, estado = :estado, estimado = :estimado, hora_entrega = :hora_entrega WHERE id = :id");
        json_encode($this->id_productos);
        $consulta->bindValue(':id_productos', $this->id_productos, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':estimado', $this->estimado);
        $consulta->bindValue(':hora_entrega', $this->hora_entrega);
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

    public function obtenerPorRol($rol){

        $qMarks = str_repeat('?,', count($this->id_productos) - 1) . '?';

        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("SELECT * FROM productos where sector = :rol AND id IN($qMarks)");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':rol', $rol);
        $consulta->execute($this->id_productos);
        $resultado = $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
        
        return $resultado
    
    }
}