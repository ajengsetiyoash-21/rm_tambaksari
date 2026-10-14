<?php
include "../../config.php";

$id_user = $_GET['id_user'];
mysqli_query($db, "DELETE FROM users WHERE id_user='$id_user'");

header("Location: index.php");
?>