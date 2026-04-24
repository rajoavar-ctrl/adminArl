<?php

require_once __DIR__ . '/../../config/database.php';

class AdminController {

    public function listarDocumentos() {

        $db = new Database();
        $conn = $db->connect();

        $query = "SELECT 
                    d.id,
                    d.ruta_archivo,
                    d.tipo_detectado,
                    d.nit_detectado,
                    d.cc_detectado,
                    d.fecha_detectada,
                    d.estado,
                    d.observaciones,
                    d.created_at
                  FROM documentos d
                  ORDER BY d.created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $data = $stmt->fetchAll();

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
?>