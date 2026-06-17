<?php
require_once __DIR__ . '/../../config/database.php';

class ConductorController {

    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
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
            $sort = $_GET['sort'] ?? 'id';
            $order = $_GET['order'] ?? 'DESC';

            $sql = "
                SELECT 
                    c.id, c.nombre, c.cedula, c.email, ct.nombre AS empresa, c.contratista_id
                FROM conductores c
                JOIN contratistas ct ON c.contratista_id = ct.id
                WHERE 1=1
            ";
            $countSql = "SELECT COUNT(*) as total FROM conductores c JOIN contratistas ct ON c.contratista_id = ct.id WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (c.nombre LIKE ? OR c.cedula LIKE ? OR c.email LIKE ?)";
                $countSql .= " AND (c.nombre LIKE ? OR c.cedula LIKE ? OR c.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($empresa_id)) {
                $sql .= " AND c.contratista_id = ?";
                $countSql .= " AND c.contratista_id = ?";
                $params[] = $empresa_id;
            }

            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $allowedSort = ['id', 'nombre', 'cedula', 'email'];
            $sort = in_array($sort, $allowedSort) ? $sort : 'id';
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY $sort $order LIMIT $limit OFFSET $offset";

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

    public function obtener($id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT * FROM conductores WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) $this->json(["error" => "Conductor no encontrado"], 404);
            $this->json($data);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function crear() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);

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
            (nombre, cedula, email, telefono, password, contratista_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($data['nombre']),
            trim($data['cedula']),
            trim($data['email']),
            $data['telefono'] ?? null,  // 👈 Agregar teléfono
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['contratista_id']
        ]);

        $this->json(["mensaje" => "Conductor creado correctamente", "id" => $conn->lastInsertId()]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}





    // En ConductorController.php
// ========================
// MIS CONDUCTORES (SOLO LOS DEL CONTRATISTA LOGUEADO)
// ========================
public function misConductores() {
    if (!isset($_SESSION['contratista_id'])) {
        $this->json(["error" => "No autorizado"], 401);
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT id, nombre, cedula, email, telefono FROM conductores WHERE contratista_id = ?");
        $stmt->execute([$_SESSION['contratista_id']]);
        $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}
    public function actualizar() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data) || empty($data['id'])) $this->json(["error" => "ID no proporcionado"], 400);

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("UPDATE conductores SET nombre = ?, cedula = ?, email = ?, contratista_id = ? WHERE id = ?");
            $stmt->execute([$data['nombre'], $data['cedula'], $data['email'] ?? null, $data['contratista_id'] ?? null, $data['id']]);
            $this->json(["mensaje" => "Conductor actualizado correctamente"]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            $conn->beginTransaction();

            $checkStmt = $conn->prepare("SELECT id, nombre FROM conductores WHERE id = ?");
            $checkStmt->execute([$id]);
            $conductor = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$conductor) $this->json(["error" => "Conductor no encontrado"], 404);

            $countVehiculos = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE conductor_id = ?");
            $countVehiculos->execute([$id]);
            $numVehiculos = $countVehiculos->fetchColumn();

            $updateVehiculos = $conn->prepare("UPDATE vehiculos SET conductor_id = NULL WHERE conductor_id = ?");
            $updateVehiculos->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM conductores WHERE id = ?");
            $stmt->execute([$id]);

            $conn->commit();
            $this->json(["mensaje" => "Conductor '{$conductor['nombre']}' eliminado correctamente", "detalles" => ["vehiculos_actualizados" => $numVehiculos]]);
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollBack();
            $this->json(["error" => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }

    public function porContratista($contratista_id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT id, nombre, cedula FROM conductores WHERE contratista_id = ? ORDER BY nombre");
            $stmt->execute([$contratista_id]);
            $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }
}