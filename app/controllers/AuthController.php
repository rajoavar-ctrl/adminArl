<?php

require_once __DIR__ . '/../../config/database.php';

class AuthController {

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            echo json_encode([
                "error" => "Email y password son obligatorios"
            ]);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        $query = "SELECT * FROM conductores WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode([
                "error" => "Usuario no encontrado"
            ]);
            return;
        }

        // ⚠️ por ahora comparación directa (luego mejoramos con hash)
        if ($password !== $user['password']) {
            echo json_encode([
                "error" => "Contraseña incorrecta"
            ]);
            return;
        }

        echo json_encode([
            "mensaje" => "Login exitoso",
            "user" => [
                "id" => $user['id'],
                "nombre" => $user['nombre'],
                "email" => $user['email']
            ]
        ]);
    }
}

?>