<?php
require_once __DIR__ . '/../../config/database.php';

class ContratistaController {

    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }



// ========================
// REGISTRO DE CONTRATISTA (AUTORREGISTRO)
// ========================
public function registro() {
    try {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        // Validar campos obligatorios
        if (empty($data['nombre']) || empty($data['nit']) || empty($data['email']) || empty($data['password'])) {
            $this->json(["error" => "Todos los campos son obligatorios"], 400);
        }

        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->json(["error" => "Email inválido"], 400);
        }

        // Validar contraseña
        if (strlen($data['password']) < 6) {
            $this->json(["error" => "La contraseña debe tener al menos 6 caracteres"], 400);
        }

        $db = new Database();
        $conn = $db->connect();

        // Verificar si ya existe el email o NIT
        $checkStmt = $conn->prepare("SELECT id FROM contratistas WHERE email = ? OR nit = ?");
        $checkStmt->execute([$data['email'], $data['nit']]);
        if ($checkStmt->fetch()) {
            $this->json(["error" => "Ya existe un registro con este email o NIT"], 400);
        }

        // Insertar nuevo contratista con estado 'pendiente'
        $stmt = $conn->prepare("
            INSERT INTO contratistas (nombre, nit, direccion, telefono, email, password, estado) 
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
        ");

        $stmt->execute([
            $data['nombre'],
            $data['nit'],
            $data['direccion'] ?? null,
            $data['telefono'] ?? null,
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT)
        ]);

        $this->json([
            "success" => true, 
            "mensaje" => "Registro exitoso. Tu solicitud ha sido enviada. Espera la aprobación del administrador."
        ]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}


// ========================
// LOGIN DE CONTRATISTA
// ========================
public function loginContratista() {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (empty($input['email']) || empty($input['password'])) {
            $this->json(["error" => "Email y contraseña requeridos"], 400);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT * FROM contratistas WHERE email = ?");
        $stmt->execute([$input['email']]);
        $contratista = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contratista) {
            $this->json(["error" => "Usuario no encontrado"], 401);
            return;
        }

        if (!password_verify($input['password'], $contratista['password'])) {
            $this->json(["error" => "Contraseña incorrecta"], 401);
            return;
        }

        if ($contratista['estado'] !== 'aprobado') {
            $this->json(["error" => "Cuenta pendiente de aprobación"], 403);
            return;
        }

        // No iniciar sesión aquí, solo guardar variables
        $_SESSION['contratista_id'] = $contratista['id'];
        $_SESSION['contratista_nombre'] = $contratista['nombre'];
        $_SESSION['contratista_email'] = $contratista['email'];

        $this->json([
            "success" => true,
            "mensaje" => "Bienvenido " . $contratista['nombre'],
            "estado" => $contratista['estado']
        ]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

// ========================
// LISTAR CONTRATISTAS PENDIENTES
// ========================
public function listarPendientes() {
    try {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->query("
            SELECT id, nombre, nit, direccion, telefono, email, estado, creado_en 
            FROM contratistas 
            WHERE estado = 'pendiente'
            ORDER BY creado_en DESC
        ");

        $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

// ========================
// APROBAR CONTRATISTA
// ========================
public function aprobar($id) {
    try {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("UPDATE contratistas SET estado = 'aprobado' WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(["success" => true, "mensaje" => "Contratista aprobado correctamente"]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

// ========================
// RECHAZAR CONTRATISTA
// ========================
public function rechazar($id) {
    try {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("UPDATE contratistas SET estado = 'rechazado' WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(["success" => true, "mensaje" => "Contratista rechazado"]);

    } catch (Exception $e) {
        $this->json(["error" => $e->getMessage()], 500);
    }
}

// ========================
// VERIFICAR SESIÓN CONTRATISTA
// ========================
public function verificarSesionContratista() {
    if (isset($_SESSION['contratista_id'])) {
        $this->json([
            "autenticado" => true,
            "contratista" => [
                "id" => $_SESSION['contratista_id'],
                "nombre" => $_SESSION['contratista_nombre'],
                "email" => $_SESSION['contratista_email']
            ]
        ]);
    } else {
        $this->json(["autenticado" => false], 401);
    }
}

// ========================
// CERRAR SESIÓN CONTRATISTA
// ========================
public function logoutContratista() {
    session_start();
    session_destroy();
    $this->json(["success" => true]);
}

// ========================
// MI PERFIL (DATOS DEL CONTRATISTA)
// ========================
public function miPerfil() {
    // Elimina esta línea: session_start();
    if (!isset($_SESSION['contratista_id'])) {
        $this->json(["error" => "No autorizado"], 401);
    }
    
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT id, nombre, nit, direccion, telefono, email, estado, creado_en FROM contratistas WHERE id = ?");
    $stmt->execute([$_SESSION['contratista_id']]);
    $this->json($stmt->fetch(PDO::FETCH_ASSOC));
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
            $sort = $_GET['sort'] ?? 'id';
            $order = $_GET['order'] ?? 'DESC';

            // Consulta para contar total
            $countSql = "SELECT COUNT(*) as total FROM contratistas WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $countSql .= " AND (nombre LIKE ? OR nit LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Consulta para datos
            $sql = "SELECT id, nombre, nit FROM contratistas WHERE 1=1";
            if (!empty($search)) {
                $sql .= " AND (nombre LIKE ? OR nit LIKE ?)";
            }

            $allowedSort = ['id', 'nombre', 'nit'];
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
            $stmt = $conn->prepare("SELECT * FROM contratistas WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) $this->json(["error" => "Contratista no encontrado"], 404);
            $this->json($data);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function crear() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) $this->json(["error" => "JSON inválido"], 400);
            if (empty($data['nombre']) || empty($data['nit'])) $this->json(["error" => "Datos incompletos"], 400);

            $db = new Database();
            $conn = $db->connect();

            $checkStmt = $conn->prepare("SELECT id FROM contratistas WHERE nit = ?");
            $checkStmt->execute([trim($data['nit'])]);
            if ($checkStmt->fetch()) $this->json(["error" => "Ya existe un contratista con este NIT"], 400);

            $stmt = $conn->prepare("INSERT INTO contratistas (nombre, nit) VALUES (?, ?)");
            $stmt->execute([trim($data['nombre']), trim($data['nit'])]);
            $this->json(["mensaje" => "Contratista creado correctamente", "id" => $conn->lastInsertId()]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function actualizar($id) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) $this->json(["error" => "JSON inválido"], 400);
            if (empty($data['nombre']) || empty($data['nit'])) $this->json(["error" => "Datos incompletos"], 400);

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("UPDATE contratistas SET nombre = ?, nit = ? WHERE id = ?");
            $stmt->execute([trim($data['nombre']), trim($data['nit']), $id]);
            $this->json(["mensaje" => "Contratista actualizado correctamente"]);
        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function eliminar($id) {
        try {
            $db = new Database();
            $conn = $db->connect();
            $conn->beginTransaction();

            $checkStmt = $conn->prepare("SELECT id, nombre FROM contratistas WHERE id = ?");
            $checkStmt->execute([$id]);
            $contratista = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$contratista) $this->json(["error" => "Contratista no encontrado"], 404);

            $countConductores = $conn->prepare("SELECT COUNT(*) FROM conductores WHERE contratista_id = ?");
            $countConductores->execute([$id]);
            $numConductores = $countConductores->fetchColumn();

            $countVehiculos = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE contratista_id = ?");
            $countVehiculos->execute([$id]);
            $numVehiculos = $countVehiculos->fetchColumn();

            $updateVehiculos = $conn->prepare("UPDATE vehiculos SET conductor_id = NULL WHERE contratista_id = ?");
            $updateVehiculos->execute([$id]);

            $deleteVehiculos = $conn->prepare("DELETE FROM vehiculos WHERE contratista_id = ?");
            $deleteVehiculos->execute([$id]);

            $deleteConductores = $conn->prepare("DELETE FROM conductores WHERE contratista_id = ?");
            $deleteConductores->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM contratistas WHERE id = ?");
            $stmt->execute([$id]);

            $conn->commit();

            $this->json([
                "mensaje" => "Contratista '{$contratista['nombre']}' eliminado correctamente",
                "detalles" => ["conductores_eliminados" => $numConductores, "vehiculos_eliminados" => $numVehiculos]
            ]);
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollBack();
            $this->json(["error" => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }
}