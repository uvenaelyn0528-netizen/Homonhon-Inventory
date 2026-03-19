<?php
// Supabase Transaction Pooler Credentials
$host = 'aws-1-ap-northeast-1.pooler.supabase.com';
$port = '6543'; 
$db   = 'postgres';
$user = 'postgres.otrkginfndevnotgkajc'; 
$pass = 'Wh01302016!2025'; 

try {
    // We add sslmode=require for Supabase security
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    // Using 1 instead of PDO::ATTR_ERRMODE_EXCEPTION to avoid the constant error
    $options = [
        3 => 2, // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        19 => 2 // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
