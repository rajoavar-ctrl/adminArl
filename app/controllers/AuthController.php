<?php
require_once __DIR__ . '/../../config/database.php';

class AuthController {

    private function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================
    // LOGIN
    // ========================
    public function login() {
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($input['email']) || !isset($input['password'])) {
                $this->json(["error" => "Email y contraseña son requeridos"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$input['email']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->json(["error" => "Usuario no encontrado"], 401);
            }

            if (!password_verify($input['password'], $usuario['password'])) {
                $this->json(["error" => "Contraseña incorrecta"], 401);
            }

            session_start();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            $this->json([
                "success" => true,
                "mensaje" => "Bienvenido " . $usuario['nombre'],
                "usuario" => [
                    "id" => $usuario['id'],
                    "nombre" => $usuario['nombre'],
                    "email" => $usuario['email'],
                    "rol" => $usuario['rol']
                ]
            ]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    // ========================
    // VERIFICAR SESIÓN
    // ========================
    public function verificarSesion() {
        session_start();
        if (isset($_SESSION['usuario_id'])) {
            $this->json([
                "autenticado" => true,
                "usuario" => [
                    "nombre" => $_SESSION['usuario_nombre'],
                    "email" => $_SESSION['usuario_email'],
                    "rol" => $_SESSION['usuario_rol']
                ]
            ]);
        } else {
            $this->json(["autenticado" => false], 401);
        }
    }

    // ========================
    // CERRAR SESIÓN
    // ========================
    public function logout() {
        session_start();
        session_destroy();
        $this->json(["success" => true, "mensaje" => "Sesión cerrada correctamente"]);
    }

    // ========================
    // REGISTRO DE USUARIOS
    // ========================
    public function registrar() {
        try {
            $input = json_decode(file_get_contents("php://input"), true);

            if (empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
                $this->json(["error" => "Todos los campos son obligatorios"], 400);
            }

            $db = new Database();
            $conn = $db->connect();

            $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $checkStmt->execute([$input['email']]);
            if ($checkStmt->fetch()) {
                $this->json(["error" => "El email ya está registrado"], 400);
            }

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute([
                $input['nombre'],
                $input['email'],
                password_hash($input['password'], PASSWORD_DEFAULT)
            ]);

            $this->json(["success" => true, "mensaje" => "Usuario registrado correctamente"]);

        } catch (Exception $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }
}