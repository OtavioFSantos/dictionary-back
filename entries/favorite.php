<?php
include '../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido. Use POST."]);
    exit;
}

if (!isset($_GET['word']) || empty($_GET['word'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'word' é obrigatório."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado."]);
    exit;
}

$word = urlencode($_GET['word']);
$user_id = $_SESSION['user_id'];

try {
    $checkQuery = "SELECT * FROM favorites WHERE word = :word AND user_id = :user_id";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([':word' => urldecode($word), ':user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["error" => "Essa palavra já foi favoritada."]);
        exit;
    }

    $insertQuery = "INSERT INTO favorites (word, user_id) VALUES (:word, :user_id)";
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([':word' => urldecode($word), ':user_id' => $user_id]);

    http_response_code(201);
    echo json_encode(["message" => "Palavra favoritada com sucesso!"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao salvar palavra favorita: " . $e->getMessage()]);
}
