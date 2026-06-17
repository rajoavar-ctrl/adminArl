<?php

require_once __DIR__ . '/../../config/database.php';

class DashboardController {

    public function resumen() {

    

        $db = new Database();
        $conn = $db->connect();

        $empresas =
            $conn->query("SELECT COUNT(*) total FROM contratistas")
                 ->fetch()['total'];

        $conductores =
            $conn->query("SELECT COUNT(*) total FROM conductores")
                 ->fetch()['total'];

        $vehiculos =
            $conn->query("SELECT COUNT(*) total FROM vehiculos")
                 ->fetch()['total'];

        $documentos =
            $conn->query("SELECT COUNT(*) total FROM documentos")
                 ->fetch()['total'];

        echo json_encode([
            "empresas" => $empresas,
            "conductores" => $conductores,
            "vehiculos" => $vehiculos,
            "documentos" => $documentos
        ]);
    }
}