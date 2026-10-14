<?php

session_start();

include "../../config.php";

if (!isset($_GET['id_meja'])) {
    header("Location: index.php");
    exit;
}

$id_meja = $_GET['id_meja'];

$hapus = mysqli_query($db, "
    DELETE FROM meja
    WHERE id_meja = '$id_meja'
");

if ($hapus) {

    echo "<script>
            alert('Meja berhasil dihapus!');
            window.location='index.php';
          </script>";

} else {

    echo "<script>
            alert('Meja gagal dihapus!');
            window.location='index.php';
          </script>";
}

?>