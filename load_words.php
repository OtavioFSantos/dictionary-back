<?php
set_time_limit(300);
include 'config.php';

//$file_path = 'english.txt';

if (file_exists($file_path)) {
    $file = fopen($file_path, 'r');

    if ($file) {
        while (($line = fgets($file)) !== false) {
            $word = trim($line);

            if (!empty($word)) {
                $sql = "INSERT INTO words (word) VALUES (:word)";
                $stmt = $pdo->prepare($sql);

                try {
                    $stmt->execute([':word' => $word]);
                } catch (PDOException $e) {
                    echo "Erro ao inserir '{$word}': " . $e->getMessage() . "<br>";
                }
            }
        }
        fclose($file);
    } else {
        echo "Erro ao abrir o arquivo";
    }
} else {
    echo "Arquivo não encontrado";
}