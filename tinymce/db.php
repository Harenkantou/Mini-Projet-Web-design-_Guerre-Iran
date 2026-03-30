<?php
function db_connect(): mysqli
{
    $servername = "db";
    $username = "user";
    $password = "password";
    $dbname = "mini_projet";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connexion echouee: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}
