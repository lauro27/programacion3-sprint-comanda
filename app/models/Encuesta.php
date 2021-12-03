<?php

class Mesa
{
    public $id;
    public $num_ped;
    public $rate_mesa;
    public $rate_mozo;
    public $rate_restaurante;
    public $rate_cocinero;

    public function crearEncuesta()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO reviews (num_ped, rate_mesa, rate_mozo, rate_restaurante, rate_cocinero) 
            VALUES (:num_ped, :rate_mesa, :rate_mozo, :rate_restaurante, :rate_cocinero)");
        
        $consulta->bindValue(':num_ped', $this->num_ped, PDO::PARAM_STR);
        $consulta->bindValue(':rate_mesa', $this->rate_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':rate_mozo', $this->rate_mozo, PDO::PARAM_INT);
        $consulta->bindValue(':rate_restaurante', $this->rate_restaurante, PDO::PARAM_INT);
        $consulta->bindValue(':rate_cocinero', $this->rate_cocinero, PDO::PARAM_INT);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, cod_mesa, estado FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerEncuesta($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, cod_mesa, estado FROM mesas WHERE cod_mesa = :cod_mesa");
        $consulta->bindValue(':cod_mesa', $mesa, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }

    public function modificarMesa()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarMesa($mesa)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE mesas WHERE id = :id");
        $consulta->bindValue(':id', $mesa, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function validarEstado(string $rol){
        return ($rol == 'esperando' || $rol == 'comiendo'||
            $rol == 'pagando'|| $rol == 'cerrada');
    }

}