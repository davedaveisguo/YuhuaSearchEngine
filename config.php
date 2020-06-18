<?php
ob_start();
session_start();  // we gonna use session

date_default_timezone_set("America/Swift_Current");

try {
    //code...
    $con=new PDO("mysql:dbname=doodle;port=3307","root");
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch (PDOException $e) {
    //throw $th;
    echo "Connection failed ". $e->getMessage();
}

?>