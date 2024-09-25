<?php
include '../config.php';
include_once("../includes/php-jwt/JWT.php");
include_once("../includes/php-jwt/Key.php");

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
$secretKey = "chave_secreta";

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
if (!isset(getallheaders()['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado."]);
    exit;
}
$authHeader = getallheaders()['Authorization'];
list($type, $token) = explode(" ", $authHeader, 2);

if (strcasecmp($type, 'Bearer') !== 0) {
    http_response_code(401);
    echo json_encode(["error" => "Tipo de token inválido."]);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $user_id = $decoded->data->id;

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
