<?php

function procesarPrecipitacion($archivo) {
    $arregloDeDatos = [];

    while (($row = fgetcsv($archivo, 1000, ";")) !== false) {
        list($fechaHora, $estacion, $valor) = $row;

        if (stripos($estacion, 'Colonia G3') !== false) {
            $fecha = date('Y-m-d', strtotime($fechaHora));
            $valor = floatval(str_replace(',', '.', $valor));
            if (!isset($arregloDeDatos[$fecha])) {
                $arregloDeDatos[$fecha] = 0.0;
            }
            $arregloDeDatos[$fecha] += $valor;
        }
    }
    return $arregloDeDatos;
}

function procesarTemperatura($archivo) {
    $sumaPorFecha = [];
    $conteoPorFecha = [];

    while (($row = fgetcsv($archivo, 1000, ";")) !== false) {
        list($fechaHora, $estacion, $valor) = $row;

        if (stripos($estacion, 'Colonia G3') !== false) {
            $fecha = date('Y-m-d', strtotime($fechaHora));
            $valor = floatval(str_replace(',', '.', $valor));
            if (!isset($sumaPorFecha[$fecha])) {
                $sumaPorFecha[$fecha] = 0.0;
                $conteoPorFecha[$fecha] = 0;
            }
            $sumaPorFecha[$fecha] += $valor;
            $conteoPorFecha[$fecha]++;
        }
    }

    // Calcula el promedio por fecha
    $arregloDeDatos = [];
    foreach ($sumaPorFecha as $fecha => $suma) {
        $arregloDeDatos[$fecha] = $conteoPorFecha[$fecha] > 0
            ? $suma / $conteoPorFecha[$fecha]
            : 0;
    }

    return $arregloDeDatos;
}

function procesarViento($archivo) {
    $sumaDirPorFecha = [];
    $sumaIntPorFecha = [];
    $conteoPorFecha = [];

    while (($row = fgetcsv($archivo, 1000, ";")) !== false) {
        $fechaHora = $row[0];
        $estacion = $row[1];
        $dirViento = isset($row[2]) ? $row[2] : 0;
        $intViento = isset($row[3]) ? $row[3] : 0;

        if (stripos($estacion, 'Colonia G3') !== false) {
            $fecha = date('Y-m-d', strtotime($fechaHora));
            
            // Convertir a valores numéricos
            $dirViento = floatval(str_replace(',', '.', $dirViento));
            $intViento = floatval(str_replace(',', '.', $intViento));
            
            if (!isset($sumaDirPorFecha[$fecha])) {
                $sumaDirPorFecha[$fecha] = 0.0;
                $sumaIntPorFecha[$fecha] = 0.0;
                $conteoPorFecha[$fecha] = 0;
            }
            
            $sumaDirPorFecha[$fecha] += $dirViento;
            $sumaIntPorFecha[$fecha] += $intViento;
            $conteoPorFecha[$fecha]++;
        }
    }

    // Calcula los promedios por fecha
    $arregloDeDatos = [];
    foreach ($sumaDirPorFecha as $fecha => $sumaDir) {
        $count = $conteoPorFecha[$fecha];
        $arregloDeDatos[$fecha] = [
            'dir_promedio' => $count > 0 ? round($sumaDir / $count) : 0,
            'int_promedio' => $count > 0 ? round($sumaIntPorFecha[$fecha] / $count, 1) : 0
        ];
    }

    return $arregloDeDatos;
}

function procesarHumedad($archivo) {
    $sumaPorFecha = [];
    $conteoPorFecha = [];

    while (($row = fgetcsv($archivo, 1000, ";")) !== false) {
        list($fechaHora, $estacion, $valor) = $row;

        if (stripos($estacion, 'Colonia G3') !== false) {
            $fecha = date('Y-m-d', strtotime($fechaHora));
            $valor = floatval(str_replace(',', '.', $valor));
            if (!isset($sumaPorFecha[$fecha])) {
                $sumaPorFecha[$fecha] = 0.0;
                $conteoPorFecha[$fecha] = 0;
            }
            $sumaPorFecha[$fecha] += $valor;
            $conteoPorFecha[$fecha]++;
        }
    }

    // Calcula el promedio por fecha
    $arregloDeDatos = [];
    foreach ($sumaPorFecha as $fecha => $suma) {
        $arregloDeDatos[$fecha] = $conteoPorFecha[$fecha] > 0
            ? $suma / $conteoPorFecha[$fecha]
            : 0;
    }

    return $arregloDeDatos;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv']) && isset($_POST['tipo'])) {
    $file = $_FILES['csv']['tmp_name'];
    $tipo = $_POST['tipo'];

    //Añade una zona horaria ya que no mostraba la hora correcta
    date_default_timezone_set('America/Montevideo');
    $fechaHoraActual = date('Ymd_His');
    $archivoDeSalida = "{$tipo}_colonia_diaria_{$fechaHoraActual}.csv";

    // Intenta abrir el archivo CSV para lectura
    if (($archivo = fopen($file, "r")) !== false) {
        fgetcsv($archivo, 1000, ";"); // Salta la primera línea

        switch ($tipo) {
            case "temperatura":
                $arregloDeDatos = procesarTemperatura($archivo);
                break;
            case "viento":
                $arregloDeDatos = procesarViento($archivo);
                break;
            case "humedad":
                $arregloDeDatos = procesarHumedad($archivo);
                break;
            case "precipitacion":
                $arregloDeDatos = procesarPrecipitacion($archivo);
                break;
            default:
                echo "Tipo de dato no válido.";
                fclose($archivo);
                exit;
        }

        fclose($archivo); // Cierra el archivo original

        // Abre (o crea) un nuevo archivo CSV para guardar el resultado
        $fp = fopen($archivoDeSalida, 'w');

        // Escribe la cabecera del nuevo archivo según el tipo
        if ($tipo === "viento") {
            fputcsv($fp, ['fecha', 'dir_viento_promedio', 'int_viento_promedio']);
        } else {
            fputcsv($fp, ['fecha', $tipo]);
        }

        // Escribe cada fila con los datos procesados
        foreach ($arregloDeDatos as $fecha => $datos) {
            if ($tipo === "viento") {
                fputcsv($fp, [
                    $fecha, 
                    number_format($datos['dir_promedio'], 1, '.', ''), 
                    number_format($datos['int_promedio'], 1, '.', '')
                ]);
            } else {
                fputcsv($fp, [$fecha, number_format($datos, 1, '.', '')]);
            }
        }

        fclose($fp); // Cierra el archivo de salida

        // Muestra un mensaje de éxito y link para descargar el archivo
        echo "<p>Archivo generado correctamente: <a href='$archivoDeSalida'>Descargar CSV</a></p>";
    } else {
        // Si no pudo abrir el archivo
        echo "Error al abrir el archivo.";
    }
} else {
    // Si no se envió correctamente el formulario
    echo "No se recibió ningún archivo o tipo de dato.";
}
?>
