<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/IAService.php';
require_once __DIR__ . '/../services/ValidationService.php';

class DocumentoController {

    public function subir() {

        if (!isset($_FILES['archivo'])) {
            echo json_encode([
                "error" => "No se envió archivo"
            ]);
            return;
        }

        $archivo = $_FILES['archivo'];

        // 1. Definir ruta para guardar archivos
        $ruta = __DIR__ . '/../../storage/uploads/';
        $nombreArchivo = time() . "_" . $archivo['name'];
        $rutaFinal = $ruta . $nombreArchivo;

        // 2. Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
            echo json_encode([
                "error" => "Error al guardar archivo"
            ]);
            return;
        }

        // 3. Conexión BD
        $db = new Database();
        $conn = $db->connect();

        // 4. INSERT inicial
        $query = "INSERT INTO documentos (conductor_id, ruta_archivo)
                  VALUES (:conductor_id, :ruta_archivo)";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            ":conductor_id" => 1, // ⚠️ luego será el usuario logueado
            ":ruta_archivo" => $nombreArchivo
        ]);

        // 5. Obtener ID
        $documentoId = $conn->lastInsertId();

        // 6. IA (extracción de datos)
        $ia = new IAService();
        $resultadoIA = $ia->analizarDocumento($rutaFinal);

        // 7. Validación de negocio
        $validator = new ValidationService();

        // ⚠️ Temporal (luego viene del login)
        $conductor = [
            "cedula" => "72004719"
        ];

        $resultadoValidacion = $validator->validar($resultadoIA, $conductor);

        // 8. UPDATE con IA + validación
        $query = "UPDATE documentos SET 
            tipo_detectado = :tipo,
            nit_detectado = :nit,
            cc_detectado = :cc,
            fecha_detectada = :fecha,
            estado = :estado,
            observaciones = :observaciones
        WHERE id = :id";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            ":tipo" => $resultadoIA['tipo_documento'] ?? null,
            ":nit" => $resultadoIA['nit_pagador'] ?? null,
            ":cc" => $resultadoIA['cc_conductor'] ?? null,
            ":fecha" => $resultadoIA['fecha_afiliacion'] ?? null,
            ":estado" => $resultadoValidacion['estado'],
            ":observaciones" => $resultadoValidacion['motivo'],
            ":id" => $documentoId
        ]);

        // 9. Respuesta final
        echo json_encode([
            "mensaje" => "Archivo subido correctamente",
            "ia" => $resultadoIA,
            "validacion" => $resultadoValidacion
        ]);
    }
}
?>