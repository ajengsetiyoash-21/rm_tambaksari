<?php

$server = "localhost";
$username = "root";
$password = "";
$database = "rm_tambaksari";

$db = mysqli_connect($server, $username, $password, $database);

if(!$db){
    die("koneksi gagal");
}
?>