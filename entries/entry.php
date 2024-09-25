<?php
include '../config.php';
include_once("../includes/php-jwt/JWT.php");
include_once("../includes/php-jwt/Key.php");

session_start();

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
$secretKey = "chave_secreta";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_GET['word']) || empty($_GET['word'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'word' é obrigatório."]);
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

    $word = urlencode($_GET['word']);

    $apiUrl = "https://api.dictionaryapi.dev/api/v2/entries/en/$word";
    $response = file_get_contents($apiUrl);

    if ($response === FALSE) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao acessar a API externa."]);
        exit;
    }

    $insertQuery = "INSERT INTO history (word, user_id) VALUES (:word, :user_id)";
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([':word' => urldecode($word), ':user_id' => $user_id]);

    echo $response;

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Erro de autenticação: " . $e->getMessage()]);
}
