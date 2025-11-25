<?php 
// Arquivo: Conec.php

$host = 'localhost'; 
$dbname = 'trabsistemanota'; // Seu nome de banco de dados
$username = 'root'; 
$password = '';             // Sua senha (vazio, como padrão do XAMPP/WAMP)

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    // 1. Lança exceções em caso de erros (PDO::ERRMODE_EXCEPTION)
    // Isso facilita a depuração e o tratamento de erros.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // 2. Define o fetch padrão como array associativo (PDO::FETCH_ASSOC)
    // Garante que os resultados venham com nomes de colunas como chaves.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // 3. Desativa emulação de prepared statements (PDO::ATTR_EMULATE_PREPARES)
    // Essencial para segurança (prevenção de injeção de SQL) e performance.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try { 
    $pdo = new PDO($dsn, $username, $password, $options); 
} 
catch (\PDOException $e) { 
    // Garante que o script pare e exiba a mensagem de erro
    // Use \PDOException para compatibilidade de namespaces (boa prática)
    die("Erro de conexão com o Banco de Dados: " . $e->getMessage()); 
} 
 
// O objeto $pdo agora está disponível para os outros arquivos.
?>