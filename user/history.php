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

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

try {
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
    // Retornar uma resposta de erro caso algo dê errado
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Erro ao consultar o banco de dados: " . $e->getMessage()]);
}
