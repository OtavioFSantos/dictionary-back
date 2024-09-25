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

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = ($page - 1) * $limit;

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $user_id = $decoded->data->id;

    $countQuery = "SELECT COUNT(*) AS totalDocs FROM history WHERE user_id = :user_id";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute([':user_id' => $user_id]);
    $totalDocs = $stmt->fetchColumn();

    if ($totalDocs === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Nenhuma palavra encontrada no histórico."]);
        exit;
    }

    $query = "SELECT word, added FROM history WHERE user_id = :user_id ORDER BY added DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPages = ceil($totalDocs / $limit);

    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;

    $response = [
        "results" => $words,
        "totalDocs" => $totalDocs,
        "page" => $page,
        "totalPages" => $totalPages,
        "hasNext" => $hasNext,
        "hasPrev" => $hasPrev
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao consultar o banco de dados: " . $e->getMessage()]);
}
