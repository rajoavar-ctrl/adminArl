<?php

require_once __DIR__ . '/../../config/database.php';

class ContratistaController {

    // 🔒 RESPUESTA JSON LIMPIA
    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================
    // 📄 LISTAR
    // ========================
    public function listar() {
        ini_set('display_errors', 0);

        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->query("
                SELECT id, nombre, nit
                FROM contratistas
                ORDER BY id DESC
            ");

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json($data);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // 🔍 OBTENER POR ID
    // ========================
    public function obtener($id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                SELECT * FROM contratistas WHERE id = ?
            ");
            $stmt->execute([$id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                $this->json(["error" => "Contratista no encontrado"], 404);
            }

            $this->json($data);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // ➕ CREAR
    // ========================
    public function crear() {
        ini_set('display_errors', 0);

        try {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            // 🔥 validar JSON
            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            // 🔥 validar campos
            if (
                empty($data['nombre']) ||
                empty($data['nit'])
            ) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                INSERT INTO contratistas (nombre, nit)
                VALUES (?, ?)
            ");

            $stmt->execute([
                trim($data['nombre']),
                trim($data['nit'])
            ]);

            $this->json([
                "mensaje" => "Contratista creado correctamente"
            ]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // ✏️ ACTUALIZAR
    // ========================
    public function actualizar($id) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            if (
                empty($data['nombre']) ||
                empty($data['nit'])
            ) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                UPDATE contratistas 
                SET nombre = ?, nit = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($data['nombre']),
                trim($data['nit']),
                $id
            ]);

            $this->json(["mensaje" => "Contratista actualizado"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // ❌ ELIMINAR
    // ========================
    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                DELETE FROM contratistas WHERE id = ?
            ");
            $stmt->execute([$id]);

            $this->json(["mensaje" => "Contratista eliminado"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }
}