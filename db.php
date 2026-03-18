<?php
// Supabase Database Connection
$host = 'aws-1-ap-northeast-1.pooler.supabase.com';
$port = '6543'; 
$db   = 'postgres';
$user = 'postgres.YOUR_PROJECT_REF_HERE'; // Replace with your Project Ref
$pass = 'Wh01302016!2025'; // Replace with your actual password

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
