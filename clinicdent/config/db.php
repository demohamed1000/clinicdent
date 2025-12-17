<?php


$servername = "localhost";
$username = "root";
$password = "";
$db_name = "clinicdent";

try{
    $conn = new PDO("mysql:host=$servername;dbname=$db_name;charset=utf8mb4",$username,$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    die("Connection Failed: ". $e->getMessage());
}