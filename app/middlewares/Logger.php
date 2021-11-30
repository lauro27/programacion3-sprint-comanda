<?php

use Slim\Handlers\Strategies\RequestHandler;
use Slim\Psr7\Request;

class Logger
{
    private $op;

    public function __construct($log)
    {
        $this->op = $log;
    }

    public function __invoke($request, $handler)
    {
        $header = $request->getHeaderLine("authorization");
        $token = trim(explode('Bearer', $header)[1]);
        $payload = AutentificadorJWT::ObtenerData($token);
        $id = $payload->id;

        $response = $handler->handle($request);
        
        $status = $response->getStatusCode();

        if($status != 200){ $this->op .= " - ERROR"; }

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta('INSERT INTO operaciones(id_usuario, fecha, operacion)
            VALUES(:id_usuario, :fecha, :operacion)');
        $ahora = date('Y-m-d H:i:s');
        $consulta->bindValue(':id_usuario', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fecha', $ahora);
        $consulta->bindValue(':operacion', $this->op);
        $consulta->execute();
        
        return $response;

    }
}