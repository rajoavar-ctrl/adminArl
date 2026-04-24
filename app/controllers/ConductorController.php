<?php
require_once __DIR__ . '/../../config/database.php';

class ConductorController {

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
                SELECT 
                    c.id,
                    c.nombre,
                    c.cedula,
                    c.email,
                    ct.nombre AS empresa,
                    c.contratista_id
                FROM conductores c
                JOIN contratistas ct ON c.contratista_id = ct.id
                ORDER BY c.id DESC
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
                SELECT * FROM conductores WHERE id = ?
            ");
            $stmt->execute([$id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                $this->json(["error" => "Conductor no encontrado"], 404);
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

            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            if (
                empty($data['nombre']) ||
                empty($data['cedula']) ||
                empty($data['email']) ||
                empty($data['password']) ||
                empty($data['contratista_id'])
            ) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                INSERT INTO conductores 
                (nombre, cedula, email, password, contratista_id)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($data['nombre']),
                trim($data['cedula']),
                trim($data['email']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['contratista_id']
            ]);

            $this->json([
                "mensaje" => "Conductor creado correctamente"
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

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                UPDATE conductores 
                SET nombre=?, cedula=?, email=?, contratista_id=?
                WHERE id=?
            ");

            $stmt->execute([
                $data['nombre'],
                $data['cedula'],
                $data['email'],
                $data['contratista_id'],
                $id
            ]);

            $this->json(["mensaje" => "Conductor actualizado"]);

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

            $stmt = $conn->prepare("DELETE FROM conductores WHERE id=?");
            $stmt->execute([$id]);

            $this->json(["mensaje" => "Conductor eliminado"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // 🔍 POR CONTRATISTA (CLAVE PARA VEHÍCULOS)
    // ========================
    public function porContratista($contratista_id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                SELECT id, nombre 
                FROM conductores 
                WHERE contratista_id = ?
            ");
            $stmt->execute([$contratista_id]);

            $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    
    
    
}

