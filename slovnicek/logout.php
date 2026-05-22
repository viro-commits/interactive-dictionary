<?php
session_start();
session_destroy(); // SMAŽE DATA ZE SESSION (ODHLÁŠENÍ)
header("Location: index.php"); // VRÁCENÍ NA ROZCESTNÍK
exit();
?>