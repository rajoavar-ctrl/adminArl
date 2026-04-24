

<?php

require_once __DIR__ . '/app.php';

class Database {

    private $conn;

    public function connect() {

        try {

            // 🔥 FALLBACK (por si $_ENV viene vacío)
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3307';
            $db   = $_ENV['DB_NAME'] ?? 'orange_project';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';


            $this->conn = new PDO(
                "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                $user,
                $pass
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $this->conn;

        } catch (PDOException $e) {

            // 🔥 RESPUESTA JSON (NO romper frontend)
            http_response_code(500);
            echo json_encode([
                "error" => "Error de conexión a BD",
                "detalle" => $e->getMessage()
            ]);

            exit;
        }
    }
}