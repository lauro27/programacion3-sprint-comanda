<?php

class CsvHandler{
    public static function ObtenerDatosUsuarios($pathArchivo){
        if ($pathArchivo === NULL) return array();

        $arrayColumnas = array("usuario","clave", "rol");
        $retorno = array();
    
        if (($archivo = fopen($pathArchivo, "r")) !== false) {
            while (($data = fgetcsv($archivo, 0, ',')) !== false) {
                $count = count($data);
                $datosArchivo = array();

                if ($count != count($arrayColumnas)) {
                    fclose($archivo);
                    return array();
                }

                for ( $index = 0; $index < $count; $index++ ) {
                    $datosArchivo[$arrayColumnas[$index]] = $data[$index];
                }
                array_push($retorno, $datosArchivo);
            }
            fclose($archivo);
        }
        return $retorno;
    }

    public static function ObtenerDatosProductos($pathArchivo){
        if ($pathArchivo === NULL) return array();

        $arrayColumnas = array("tipo","precio", "descripcion");
        $retorno = array();
    
        if (($archivo = fopen($pathArchivo, "r")) !== false) {
            while (($data = fgetcsv($archivo, 0, ',')) !== false) {
                $count = count($data);
                $datosArchivo = array();

                if ($count != count($arrayColumnas)) {
                    fclose($archivo);
                    return array();
                }

                for ( $index = 0; $index < $count; $index++ ) {
                    $datosArchivo[$arrayColumnas[$index]] = $data[$index];
                }
                array_push($retorno, $datosArchivo);
            }
            fclose($archivo);
        }
        return $retorno;
    }

    public static function ObtenerArchivo ( string $nombreFile ) : ?string {
        return (key_exists($nombreFile, $_FILES)) ? $_FILES[$nombreFile]['tmp_name'] : NULL;
    }

    public static function GenerarCodigo()
    {
        return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
    }
}

?>