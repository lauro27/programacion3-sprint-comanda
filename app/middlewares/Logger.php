<?php

class Logger
{
    public static function LogOperacion($request, $handler)
    {
        $response = $handler->handle($request);
        return $response;
    }
}