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

            $stmt = $conn->prepare("SELECT * FROM contratistas WHERE id = ?");
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
        try {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            if (empty($data['nombre']) || empty($data['nit'])) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            // Verificar si ya existe el NIT
            $checkStmt = $conn->prepare("SELECT id FROM contratistas WHERE nit = ?");
            $checkStmt->execute([trim($data['nit'])]);
            if ($checkStmt->fetch()) {
                $this->json(["error" => "Ya existe un contratista con este NIT"], 400);
            }

            $stmt = $conn->prepare("
                INSERT INTO contratistas (nombre, nit)
                VALUES (?, ?)
            ");

            $stmt->execute([
                trim($data['nombre']),
                trim($data['nit'])
            ]);

            $this->json([
                "mensaje" => "Contratista creado correctamente",
                "id" => $conn->lastInsertId()
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
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!is_array($data)) {
                $this->json(["error" => "JSON inválido"], 400);
            }

            if (empty($data['nombre']) || empty($data['nit'])) {
                $this->json(["error" => "Datos incompletos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            // Verificar si el contratista existe
            $checkStmt = $conn->prepare("SELECT id FROM contratistas WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                $this->json(["error" => "Contratista no encontrado"], 404);
            }

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

            $this->json(["mensaje" => "Contratista actualizado correctamente"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // ❌ ELIMINAR CON CASCADA (CORREGIDO)
    // ========================
    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Iniciar transacción
            $conn->beginTransaction();

            // Verificar si el contratista existe
            $checkStmt = $conn->prepare("SELECT id, nombre FROM contratistas WHERE id = ?");
            $checkStmt->execute([$id]);
            $contratista = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contratista) {
                $this->json(["error" => "Contratista no encontrado"], 404);
            }

            // CONTAR registros antes de eliminar (para feedback)
            $countConductores = $conn->prepare("SELECT COUNT(*) FROM conductores WHERE contratista_id = ?");
            $countConductores->execute([$id]);
            $numConductores = $countConductores->fetchColumn();
            
            $countVehiculos = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE contratista_id = ?");
            $countVehiculos->execute([$id]);
            $numVehiculos = $countVehiculos->fetchColumn();

            // PASO 1: Actualizar vehículos (quitar relación con conductores)
            $updateVehiculos = $conn->prepare("UPDATE vehiculos SET conductor_id = NULL WHERE contratista_id = ?");
            $updateVehiculos->execute([$id]);
            
            // PASO 2: Eliminar vehículos del contratista
            $deleteVehiculos = $conn->prepare("DELETE FROM vehiculos WHERE contratista_id = ?");
            $deleteVehiculos->execute([$id]);
            
            // PASO 3: Eliminar conductores del contratista
            $deleteConductores = $conn->prepare("DELETE FROM conductores WHERE contratista_id = ?");
            $deleteConductores->execute([$id]);
            
            // PASO 4: Finalmente eliminar el contratista
            $stmt = $conn->prepare("DELETE FROM contratistas WHERE id = ?");
            $stmt->execute([$id]);
            
            // Confirmar transacción
            $conn->commit();

            $this->json([
                "mensaje" => "Contratista '{$contratista['nombre']}' eliminado correctamente",
                "detalles" => [
                    "conductores_eliminados" => $numConductores,
                    "vehiculos_eliminados" => $numVehiculos,
                    "contratista_eliminado" => true
                ]
            ]);

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            if (isset($conn)) {
                $conn->rollBack();
            }
            $this->json(["error" => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }
}