<?php
$host = 'db.otrkginfndevnotgkajc.supabase.co'; 
$db   = 'postgres';
$user = 'postgres';
$pass = 'Wh01302016!2025'; 
$port = "5432";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ATTR_ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    throw new Exception("Connection failed: " . $e->getMessage());
}
?>
