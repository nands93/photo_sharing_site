<?php
    $db_server = "mariadb";
    $db_user = "root";
    $db_password = "root";
    $db_name = "camagru";
    $db_port = "3306";
    
    try {
        $conn = mysqli_connect($db_server, $db_user, $db_password, $db_name, $db_port);
    }
    catch (Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }
?>