<?php
include '../config.php';
include_once("../includes/php-jwt/JWT.php");

use \Firebase\JWT\JWT;

$secretKey = "chave_secreta";

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $payload = [
                    'iss' => 'http://localhost:4200',
                    'aud' => 'http://localhost:4200',
                    'iat' => time(),
                    'exp' => time() + (60 * 60),
                    'data' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ]
                ];

                $jwt = JWT::encode($payload, $secretKey, 'HS256');

                echo json_encode([
                    "status" => "success",
                    "message" => "Login realizado com sucesso. Bem-vindo, " . htmlspecialchars($user['name']),
                    "token" => $jwt,
                    "user" => [
                        "id" => $user['id'],
                        "name" => htmlspecialchars($user['name']),
                        "email" => htmlspecialchars($user['email'])
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    "status" => "error",
                    "message" => "Email ou senha incorretos!"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Email inválido!"
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Por favor, preencha todos os campos!"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método HTTP inválido!"
    ]);
}