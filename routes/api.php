<?php

// Iniciar sesión SOLO UNA VEZ al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DocumentoController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/ContratistaController.php';
require_once __DIR__ . '/../app/controllers/ConductorController.php';
require_once __DIR__ . '/../app/controllers/VehiculoController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';

// ========================
// NORMALIZAR URI
// ========================
function getNormalizedUri() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = '/orange-proyect/public';

    if (strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }

    $uri = rtrim($uri, '/');
    return $uri === '' ? '/' : $uri;
}

// ========================
// HELPERS
// ========================
function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function executeController($controller, $action, $param = null) {
    try {
        if (!method_exists($controller, $action)) {
            throw new Exception("Método $action no existe");
        }

        $param !== null 
            ? $controller->$action($param) 
            : $controller->$action();

    } catch (Exception $e) {
        errorResponse($e->getMessage(), 500);
    }
}

// ========================
// ROUTER
// ========================

$uri = getNormalizedUri();
$method = $_SERVER['REQUEST_METHOD'];

try {

    // ===== LOGIN =====
    if ($uri === '/login' && $method === 'POST') {
        executeController(new AuthController(), 'login');
    }

    // ===== TEST =====
    if ($uri === '/test' && $method === 'GET') {
        echo json_encode(["mensaje" => "API OK"]);
        exit;
    }

    // ===== DOCUMENTOS =====
    if ($uri === '/documentos' && $method === 'POST') {
        executeController(new DocumentoController(), 'subir');
    }

    if ($uri === '/admin/documentos' && $method === 'GET') {
        executeController(new AdminController(), 'listarDocumentos');
    }

    // ===== CONTRATISTAS =====
    if ($uri === '/contratistas' && $method === 'GET') {
        executeController(new ContratistaController(), 'listar');
    }

    if ($uri === '/contratistas' && $method === 'POST') {
        executeController(new ContratistaController(), 'crear');
    }

    if ($uri === '/contratistas/actualizar' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            errorResponse("ID no proporcionado", 400);
        }
        executeController(new ContratistaController(), 'actualizar', $data['id']);
    }

   // ===== CONTRATISTAS =====
if ($uri === '/contratistas/eliminar' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Debug: escribir en log
    error_log("Eliminar contratista - ID recibido: " . ($data['id'] ?? 'null'));
    
    if (!isset($data['id'])) {
        errorResponse("ID no proporcionado", 400);
    }
    executeController(new ContratistaController(), 'eliminar', $data['id']);
}

    // ===== CONDUCTORES =====
    if ($uri === '/conductores' && $method === 'GET') {
        executeController(new ConductorController(), 'listar');
    }

    if ($uri === '/conductores' && $method === 'POST') {
        executeController(new ConductorController(), 'crear');
    }

    if ($uri === '/conductores/por-contratista' && $method === 'GET') {
        $id = $_GET['contratista_id'] ?? null;
        if (!$id) {
            errorResponse("contratista_id requerido", 400);
        }
        executeController(new ConductorController(), 'porContratista', $id);
    }

    if ($uri === '/conductores/actualizar' && $method === 'POST') {
        executeController(new ConductorController(), 'actualizar');
    }

    if ($uri === '/conductores/eliminar' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            errorResponse("ID no proporcionado", 400);
        }
        executeController(new ConductorController(), 'eliminar', $data['id']);
    }

    // ===== VEHÍCULOS =====
    if ($uri === '/vehiculos' && $method === 'GET') {
        executeController(new VehiculoController(), 'listar');
    }

    if ($uri === '/vehiculos' && $method === 'POST') {
        executeController(new VehiculoController(), 'crear');
    }

    if ($uri === '/vehiculos/actualizar' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            errorResponse("ID no proporcionado", 400);
        }
        executeController(new VehiculoController(), 'actualizar', $data['id']);
    }

    if ($uri === '/vehiculos/eliminar' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            errorResponse("ID no proporcionado", 400);
        }
        executeController(new VehiculoController(), 'eliminar', $data['id']);
    }

    if ($uri === '/vehiculos/obtener' && $method === 'GET') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            errorResponse("ID requerido", 400);
        }
        executeController(new VehiculoController(), 'obtener', $id);
    }

    if ($uri === '/conductores/obtener' && $method === 'GET') {
    $id = $_GET['id'] ?? null;
    executeController(new ConductorController(), 'obtener', $id);
}

    // ===== AUTENTICACIÓN =====
if ($uri === '/auth/login' && $method === 'POST') {
    executeController(new AuthController(), 'login');
}

if ($uri === '/auth/verificar' && $method === 'GET') {
    executeController(new AuthController(), 'verificarSesion');
}

if ($uri === '/auth/logout' && $method === 'POST') {
    executeController(new AuthController(), 'logout');
}

if ($uri === '/auth/registrar' && $method === 'POST') {
    executeController(new AuthController(), 'registrar');
}

// ===== CONTRATISTAS - PERFIL Y VERIFICACIÓN =====
if ($uri === '/contratistas/verificar' && $method === 'GET') {
    executeController(new ContratistaController(), 'verificarSesionContratista');
}

if ($uri === '/contratistas/logout' && $method === 'POST') {
    executeController(new ContratistaController(), 'logoutContratista');
}

if ($uri === '/contratistas/mi-perfil' && $method === 'GET') {
    executeController(new ContratistaController(), 'miPerfil');
}

// ===== CONTRATISTAS - REGISTRO Y LOGIN =====
if ($uri === '/contratistas/registro' && $method === 'POST') {
    executeController(new ContratistaController(), 'registro');
}

if ($uri === '/contratistas/login' && $method === 'POST') {
    executeController(new ContratistaController(), 'loginContratista');
}

// ===== CONTRATISTAS - PERFIL Y VERIFICACIÓN =====
if ($uri === '/contratistas/verificar' && $method === 'GET') {
    executeController(new ContratistaController(), 'verificarSesionContratista');
}

if ($uri === '/contratistas/logout' && $method === 'POST') {
    executeController(new ContratistaController(), 'logoutContratista');
}

if ($uri === '/contratistas/mi-perfil' && $method === 'GET') {
    executeController(new ContratistaController(), 'miPerfil');
}

// ===== CONDUCTORES - FILTRADOS POR CONTRATISTA =====
if ($uri === '/conductores/mis-conductores' && $method === 'GET') {
    executeController(new ConductorController(), 'misConductores');
}

// ===== VEHÍCULOS - FILTRADOS POR CONTRATISTA =====
if ($uri === '/vehiculos/mis-vehiculos' && $method === 'GET') {
    executeController(new VehiculoController(), 'misVehiculos');
}

if ($uri === '/contratistas/pendientes' && $method === 'GET') {
    executeController(new ContratistaController(), 'listarPendientes');
}

if ($uri === '/contratistas/aprobar' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    executeController(new ContratistaController(), 'aprobar', $data['id']);
}

if ($uri === '/contratistas/rechazar' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    executeController(new ContratistaController(), 'rechazar', $data['id']);
}

if ($uri === '/dashboard' && $method === 'GET') {
    $controller = new DashboardController();
    $controller->resumen();
    exit;
}


    // ===== 404 =====
    errorResponse("Ruta no encontrada: $uri", 404);

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}