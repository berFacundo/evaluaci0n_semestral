<?php

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") { 

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Error al subir el archivo.";
    } else {
        $archivo_temporal = $_FILES['archivo']['tmp_name'];
        $nombre_archivo = $_FILES['archivo']['name'];

        $tipo = $_POST['tipo'] ?? '';

        if ($tipo == '') {
            $errors[] = "Debe seleccionar un tipo de dato.";
        } elseif (!in_array($tipo, ['temp', 'prec', 'viento', 'hum'])) {
            $errors[] = "Tipo de dato no válido.";
        } else {
            switch ($tipo) {
            case 'temp':
                procesarTemp($archivo_temporal);
                break;
            case 'prec':
                procesarPrec($archivo_temporal);
                break;
            case 'viento':
                procesarViento($archivo_temporal);
                break;
            case 'hum':
                procesarHum($archivo_temporal);
                break;
            default:
                $errors[] = "Tipo de dato no reconocido.";
            }
        }
       
    }

    $archivo_entrada = 'datos/inumet_humedad_relativa.csv';

    $archivo_salida = 'datosProcesados/' . date("Y-m-d-H:i:s");



    if (($archivo = fopen($archivo_entrada, "r")) ==! false) {

        while(($row = fgetcsv($handle, 1000, ";")) !== false) {
        if($linea == 0 || count($row)<3 || $row[2] === ''){
        $linea++;
    }
    // el codigo
    }
    } else {
        $errors[] = "No se pudo abrir el archivo de entrada.";
    }

} else {
    echo "Método no permitido.";
}

function procesarTemp($archivo) {
    echo "Procesando archivo de temperatura";
}

function procesarPrec($archivo) {
    echo "Procesando archivo de precipitacion";
}

function procesarViento($archivo) {
echo "Procesando archivo de viento";
}

function procesarHum($archivo) {
echo "Procesando archivo de humedad";
}
?>