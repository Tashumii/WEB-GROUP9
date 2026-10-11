<?php
    $host="localhost";
    $user="root";
    $password="";
    $database = "SalesDB";




    $connn = new PDO("mysql:host=$host;dbname=$database", $user, $password);

    $connn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$connn) {
        die("Connection failed: " . mysqli_connect_error());
    }




?>