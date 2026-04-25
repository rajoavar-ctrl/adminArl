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

            $stmt = $conn->prepare("SELECT * FROM conductores WHERE id = ?");
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
        try {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            if (empty($data['nombre']) || empty($data['cedula']) || empty($data['email']) || 
                empty($data['password']) || empty($data['contratista_id'])) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            // Verificar si ya existe la cédula
            $checkStmt = $conn->prepare("SELECT id FROM conductores WHERE cedula = ?");
            $checkStmt->execute([trim($data['cedula'])]);
            if ($checkStmt->fetch()) {
                $this->json(["error" => "Ya existe un conductor con esta cédula"], 400);
            }

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

            $this->json(["mensaje" => "Conductor creado correctamente", "id" => $conn->lastInsertId()]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // ✏️ ACTUALIZAR
    // ========================
    public function actualizar() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!is_array($data) || empty($data['id'])) {
                $this->json(["error" => "ID no proporcionado o datos inválidos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            // Verificar si el conductor existe
            $checkStmt = $conn->prepare("SELECT id FROM conductores WHERE id = ?");
            $checkStmt->execute([$data['id']]);
            if (!$checkStmt->fetch()) {
                $this->json(["error" => "Conductor no encontrado"], 404);
            }

            $stmt = $conn->prepare("
                UPDATE conductores 
                SET nombre = ?, cedula = ?, email = ?, contratista_id = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['nombre'],
                $data['cedula'],
                $data['email'] ?? null,
                $data['contratista_id'] ?? null,
                $data['id']
            ]);

            $this->json(["mensaje" => "Conductor actualizado correctamente"]);

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

        // Verificar si el conductor existe
        $checkStmt = $conn->prepare("SELECT id FROM conductores WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            $this->json(["error" => "Conductor no encontrado"], 404);
        }

        // Verificar si tiene vehículos asociados
        $vehiculosStmt = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE conductor_id = ?");
        $vehiculosStmt->execute([$id]);
        $cantidadVehiculos = $vehiculosStmt->fetchColumn();
        
        if ($cantidadVehiculos > 0) {
            $this->json(["error" => "No se puede eliminar porque tiene $cantidadVehiculos vehículo(s) asociado(s)"], 400);
        }

        $stmt = $conn->prepare("DELETE FROM conductores WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(["mensaje" => "Conductor eliminado correctamente"]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

    // ========================
    // 🔍 POR CONTRATISTA
    // ========================
    public function porContratista($contratista_id) {
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("
                SELECT id, nombre, cedula 
                FROM conductores 
                WHERE contratista_id = ?
                ORDER BY nombre
            ");
            $stmt->execute([$contratista_id]);

            $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }  
}