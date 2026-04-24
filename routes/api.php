<?php

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DocumentoController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/ContratistaController.php';
require_once __DIR__ . '/../app/controllers/ConductorController.php';
require_once __DIR__ . '/../app/controllers/VehiculoController.php';

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

    // ===== CONDUCTORES =====
    if ($uri === '/conductores' && $method === 'GET') {
        executeController(new ConductorController(), 'listar');
    }

    if ($uri === '/conductores' && $method === 'POST') {
        executeController(new ConductorController(), 'crear');
    }

    if ($uri === '/conductores/por-contratista' && $method === 'GET') {
        $id = $_GET['contratista_id'] ?? null;
        executeController(new ConductorController(), 'porContratista', $id);
    }

    if ($uri === '/conductores/eliminar' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        executeController(new ConductorController(), 'eliminar', $data['id']);
    }

    // ===== VEHÍCULOS =====
    if ($uri === '/vehiculos' && $method === 'GET') {
        executeController(new VehiculoController(), 'listar');
    }

    if ($uri === '/vehiculos' && $method === 'POST') {
        executeController(new VehiculoController(), 'crear');
    }

    // ===== 404 =====
    errorResponse("Ruta no encontrada: $uri", 404);

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}