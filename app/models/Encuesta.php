<?php

class Encuesta
{
    public int $id;
    public $cod_ped;
    public int $rate_mesa;
    public int $rate_mozo;
    public int $rate_restaurante;
    public int $rate_cocinero;

    public function crearEncuesta()
    {
        
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO reviews (cod_ped, rate_mesa, rate_mozo, rate_restaurante, rate_cocinero) 
            VALUES (:cod_ped, :rate_mesa, :rate_mozo, :rate_restaurante, :rate_cocinero)");
        
        var_dump($this);
        $this->redondearValores();

        $consulta->bindValue(':cod_ped', $this->cod_ped, PDO::PARAM_STR);
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
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM reviews");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Encuesta');
    }

    public static function obtenerMejores()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "SELECT id, cod_ped, rate_mesa, rate_mozo, rate_restaurante, rate_cocinero, 
            AVG(rate_mesa, rate_mozo, rate_restaurante, rate_cocinero) as promedio 
            FROM reviews ORDER BY promedio LIMIT 10");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Encuesta');
    }

    public static function obtenerEncuesta($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Encuesta');
    }

    public function redondearValores(){
        if($this->rate_cocinero < 1){$this->rate_cocinero = 1;}
        if($this->rate_cocinero > 10){$this->rate_cocinero = 10;}
        if($this->rate_mozo < 1){$this->rate_mozo = 1;}
        if($this->rate_mozo > 10){$this->rate_mozo = 10;}
        if($this->rate_mesa < 1){$this->rate_mesa = 1;}
        if($this->rate_mesa > 10){$this->rate_mesa = 10;}
        if($this->rate_restaurante < 1){$this->rate_restaurante = 1;}
        if($this->rate_restaurante > 10){$this->rate_restaurante = 10;}
    }

}