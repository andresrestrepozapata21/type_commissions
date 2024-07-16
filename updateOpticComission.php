<?php
date_default_timezone_set('America/Bogota');

// Detalles de la conexión a la base de datos
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

// Conectar a la base de datos usando PDO
try {
    $conection = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass);
    $conection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Incluir la librería PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Cargar el archivo Excel
$inputFileName = 'optic_fixed_comission.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$worksheet = $spreadsheet->getActiveSheet();

// Obtener las filas del archivo Excel
$rows = $worksheet->toArray();

foreach ($rows as $row) {
    // Verificar que la fila tenga al menos dos columnas
    if (isset($row[0]) && isset($row[1])) {
        $id = $row[0];
        $type = $row[1];

        // Consultar la base de datos para obtener el nombre del establecimiento
        $query = "SELECT name FROM optic WHERE id_code = :id";
        $stmt = $conection->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $name = $result['name'];

            // Inicializar la variable que va a ser guardada en base de datos
            $type_commission = "";

            // Valido qué type de comisión es esta óptica
            switch ($type) {
                case "Inactiva":
                    $type_commission = "inactiva";
                    break;
                case "Comision Fija":
                    $type_commission = "fija";
                    break;
                case "TPV FLiPO":
                    $type_commission = "tpv";
                    break;
                case "SEPA":
                    $type_commission = "sepa";
                    break;
                case "Transeferencia Bancaria":
                    $type_commission = "bancaria";
                    break;
                default:
                    $type_commission = "variable";
            }

            echo "ID: $id, Nombre: $name, Tipo: $type_commission\n";

            try {
                // Preparar la consulta de actualización
                $update = "UPDATE optic SET type_commission = :type_commission WHERE id_code = :id";
                $stmtUpdate = $conection->prepare($update);

                // Vincular parámetros y ejecutar la consulta de forma sincrónica
                $stmtUpdate->bindParam(':type_commission', $type_commission, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);

                // Ejecutar la consulta y verificar el resultado
                if ($stmtUpdate->execute()) {
                    echo "Actualización exitosa para ID $id\n";
                } else {
                    echo "Error al actualizar ID $id\n";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "Fila incompleta o inválida: ";
        print_r($row);
        echo "\n";
    }
}

// Cerrar la conexión
$conection = null;
?>
