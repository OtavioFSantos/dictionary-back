<?php
include '../config.php';

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$response = [
    "results" => [],
    "totalDocs" => 0,
    "page" => $page,
    "totalPages" => 0,
    "hasNext" => false,
    "hasPrev" => false
];

try {
    $countQuery = "SELECT COUNT(*) AS total FROM words WHERE word LIKE :search";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute([':search' => $search . '%']);
    $totalDocs = $stmt->fetchColumn();

    if ($totalDocs > 0) {
        $totalPages = ceil($totalDocs / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        $sql = "SELECT word FROM words WHERE word LIKE :search LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search', $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $words = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $response = [
            "results" => $words,
            "totalDocs" => $totalDocs,
            "page" => $page,
            "totalPages" => $totalPages,
            "hasNext" => $hasNext,
            "hasPrev" => $hasPrev
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao consultar o banco de dados: " . $e->getMessage()]);
}
