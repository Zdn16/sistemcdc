<?php

    date_default_timezone_set('Asia/Jakarta');

    $koneksi=mysqli_connect("localhost","root","","sistemcdc");
    if (!$koneksi){
        echo "Koneksi Gagal";
    }

?>