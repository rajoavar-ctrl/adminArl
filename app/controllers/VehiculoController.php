<?php
require_once __DIR__ . '/../../config/database.php';

class VehiculoController {

    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

   public function crear() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['placa']) || empty($data['marca']) || empty($data['modelo']) || empty($data['contratista_id'])) {
            $this->json(["error" => "Datos incompletos"], 400);
        }

        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("
            INSERT INTO vehiculos (placa, marca, modelo, tipo, contratista_id, conductor_id)
            VALUES (:placa, :marca, :modelo, :tipo, :contratista_id, :conductor_id)
        ");

        $stmt->execute([
            ":placa" => strtoupper($data['placa']),
            ":marca" => $data['marca'],
            ":modelo" => $data['modelo'],
            ":tipo" => $data['tipo'] ?? 'automovil',
            ":contratista_id" => $data['contratista_id'],
            ":conductor_id" => isset($data['conductor_id']) && $data['conductor_id'] != '' ? $data['conductor_id'] : null
        ]);

        $this->json(["mensaje" => "Vehículo creado correctamente"]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

    // ========================
    // 📄 LISTAR CON PAGINACIÓN Y FILTROS
    // ========================
    public function listar() {
        try {
            $db = new Database();
            $conn = $db->connect();

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $empresa_id = $_GET['empresa_id'] ?? '';
            $conductor_id = $_GET['conductor_id'] ?? '';

            $sql = "
                SELECT v.id, v.placa, v.marca, v.modelo, ct.nombre AS empresa, c.nombre AS conductor_nombre, v.conductor_id, v.contratista_id
                FROM vehiculos v
                JOIN contratistas ct ON v.contratista_id = ct.id
                LEFT JOIN conductores c ON v.conductor_id = c.id
                WHERE 1=1
            ";
            $countSql = "
                SELECT COUNT(*) as total FROM vehiculos v
                JOIN contratistas ct ON v.contratista_id = ct.id
                LEFT JOIN conductores c ON v.conductor_id = c.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR ct.nombre LIKE ?)";
                $countSql .= " AND (v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR ct.nombre LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($empresa_id)) {
                $sql .= " AND v.contratista_id = ?";
                $countSql .= " AND v.contratista_id = ?";
                $params[] = $empresa_id;
            }

            if (!empty($conductor_id)) {
                $sql .= " AND v.conductor_id = ?";
                $countSql .= " AND v.conductor_id = ?";
                $params[] = $conductor_id;
            }

            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $sql .= " ORDER BY v.id DESC LIMIT $limit OFFSET $offset";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }


// En VehiculoController.php
// ========================
// MIS VEHÍCULOS (SOLO LOS DEL CONTRATISTA LOGUEADO)
// ========================
public function misVehiculos() {
    // Elimina esta línea: session_start();
    if (!isset($_SESSION['contratista_id'])) {
        $this->json(["error" => "No autorizado"], 401);
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT v.id, v.placa, v.marca, v.modelo, v.tipo, c.nombre AS conductor_nombre 
            FROM vehiculos v 
            LEFT JOIN conductores c ON v.conductor_id = c.id 
            WHERE v.contratista_id = ?
        ");
        $stmt->execute([$_SESSION['contratista_id']]);
        $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

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

    public function actualizar($id) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);

        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("
            UPDATE vehiculos 
            SET placa=?, marca=?, modelo=?, tipo=?, contratista_id=?, conductor_id=? 
            WHERE id=?
        ");

        $stmt->execute([
            $data['placa'],
            $data['marca'],
            $data['modelo'],
            $data['tipo'] ?? 'automovil',
            $data['contratista_id'],
            $data['conductor_id'],
            $id
        ]);

        $this->json(["mensaje" => "Vehículo actualizado"]);
    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            $checkStmt = $conn->prepare("SELECT id, placa FROM vehiculos WHERE id = ?");
            $checkStmt->execute([$id]);
            $vehiculo = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$vehiculo) $this->json(["error" => "Vehículo no encontrado"], 404);

            $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id = ?");
            $stmt->execute([$id]);
            $this->json(["mensaje" => "Vehículo '{$vehiculo['placa']}' eliminado correctamente"]);
        } catch (Exception $e) {
            $this->json(["error" => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }
}