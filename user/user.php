<?php
include '../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido. Use GET."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado."]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT id, name, email FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Usuário não encontrado."]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode([
        "id" => $user['id'],
        "username" => $user['name'],
        "email" => $user['email']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao consultar o banco de dados: " . $e->getMessage()]);
}
