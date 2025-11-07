<?php
// conexao.php
$host = 'localhost';
$db   = 'greentech_db'; 
$user = 'root';
$pass = ''; // Verifique sua senha aqui
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Adicionado para segurança e conformidade
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // MODIFICADO PARA EXIBIR A MENSAGEM DETALHADA DO ERRO DE CONEXÃO
    // Você verá o erro exato do MySQL/MariaDB, como "Acesso negado para o usuário..."
    die('Erro de conexão com o banco de dados: ' . $e->getMessage());
}
?>