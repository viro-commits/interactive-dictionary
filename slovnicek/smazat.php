<?php
session_start();
require 'dbconnect.php';

if(!isset($_SESSION["user_id"])){
    header("Location: prihlaseni.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// SMAZÁNÍ SLOVÍČKA Z DATABÁZE (SLOVNÍČKU)
$id = $_GET["id"];
$sql = "DELETE FROM words WHERE id = '$id' AND user_id = '$user_id'";
mysqli_query($conn, $sql);

if(isset($_GET["jazyk"])){
    $jazyk = $_GET["jazyk"];
}else{
    $jazyk = "vse";
}

if($jazyk == "vse"){
    header("Location: slovicka.php");
}else{
    header("Location: slovicka.php?jazyk=" . $jazyk);
}
exit();
?>
