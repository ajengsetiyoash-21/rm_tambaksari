<?php
include "../../config.php";

$id_menu = $_GET['id_menu'];
mysqli_query($db, "DELETE FROM menu WHERE id_menu='$id_menu'");

header("Location: index.php");
?>