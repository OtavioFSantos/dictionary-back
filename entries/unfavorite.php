<?php
include '../config.php';
include_once("../includes/php-jwt/JWT.php");
include_once("../includes/php-jwt/Key.php");

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
$secretKey = "chave_secreta";

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
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
if (!isset($_GET['word']) || empty($_GET['word'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'word' é obrigatório."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido. Use DELETE."]);
    exit;
}
if (!isset($_GET['word']) || empty($_GET['word'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'word' é obrigatório."]);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $user_id = $decoded->data->id;
    $word = urlencode($_GET['word']);

    $checkQuery = "SELECT * FROM favorites WHERE word = :word AND user_id = :user_id";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([':word' => urldecode($word), ':user_id' => $user_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Essa palavra não está favoritada."]);
        exit;
    }

    $deleteQuery = "DELETE FROM favorites WHERE word = :word AND user_id = :user_id";
    $stmt = $pdo->prepare($deleteQuery);
    $stmt->execute([':word' => urldecode($word), ':user_id' => $user_id]);

    http_response_code(200);
    echo json_encode(["message" => "Palavra desfavoritada com sucesso!"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao desfavoritar a palavra: " . $e->getMessage()]);
}
