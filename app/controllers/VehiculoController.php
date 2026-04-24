<?php
require_once __DIR__ . '/../../config/database.php';

class VehiculoController {

    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CREAR
    public function crear() {
        ini_set('display_errors', 0);

        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (
                empty($data['placa']) ||
                empty($data['marca']) ||
                empty($data['modelo']) ||
                empty($data['contratista_id'])
            ) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                INSERT INTO vehiculos (placa, marca, modelo, contratista_id, conductor_id)
                VALUES (:placa, :marca, :modelo, :contratista_id, :conductor_id)
            ");

            $stmt->execute([
                ":placa" => strtoupper($data['placa']),
                ":marca" => $data['marca'],
                ":modelo" => $data['modelo'],
                ":contratista_id" => $data['contratista_id'],
                ":conductor_id" => $data['conductor_id'] ?? null
            ]);

            $this->json(["mensaje" => "Vehículo creado correctamente"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // LISTAR
    public function listar() {
        ini_set('display_errors', 0);

        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->query("
                SELECT v.id, v.placa, v.marca, v.modelo, ct.nombre AS empresa
                FROM vehiculos v
                JOIN contratistas ct ON v.contratista_id = ct.id
                ORDER BY v.id DESC
            ");

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json($data);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // OBTENER
    public function obtener($id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE id = ?");
            $stmt->execute([$id]);

            $this->json($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ACTUALIZAR
    public function actualizar($id) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                UPDATE vehiculos 
                SET placa=?, marca=?, modelo=?, contratista_id=?, conductor_id=? 
                WHERE id=?
            ");

            $stmt->execute([
                $data['placa'],
                $data['marca'],
                $data['modelo'],
                $data['contratista_id'],
                $data['conductor_id'],
                $id
            ]);

            $this->json(["mensaje" => "Vehículo actualizado"]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id=?");
            $stmt->execute([$id]);

            $this->json(["mensaje" => "Vehículo eliminado"]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }
}