<?php
include "../../config.php";

$id_kategori = $_GET['id_kategori'];
mysqli_query($db, "DELETE FROM kategori WHERE id_kategori='$id_kategori'");

header("Location: index.php");
?>