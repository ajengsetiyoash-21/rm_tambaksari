<?php
define('QRIS_STATIS', '00020101021126610014ID.CO.QRIS.WWW01189360000900000000000215ID102000000000000303UMI51440014ID.CO.QRIS.WWW0215ID102000000000000305200303ID');

function generateDynamicQRIS($qris_statis, $nominal) {
    $qris_tanpa_crc = substr($qris_statis, 0, -4);

    $posisi_54 = strpos($qris_tanpa_crc, "54");
    if ($posisi_54 !== false && $posisi_54 > 10) {
        $len_54 = (int) substr($qris_tanpa_crc, $posisi_54 + 2, 2);
        if ($len_54 > 0 && $len_54 < 20) {
            $qris_tanpa_crc = substr($qris_tanpa_crc, 0, $posisi_54) . substr($qris_tanpa_crc, $posisi_54 + 4 + $len_54);
        }
    }

    $nominal_str = (string) intval($nominal);
    $panjang_nominal = str_pad(strlen($nominal_str), 2, "0", STR_PAD_LEFT);
    $tag_54 = "54" . $panjang_nominal . $nominal_str;

    $posisi_58 = strpos($qris_tanpa_crc, "5802ID");
    if ($posisi_58 !== false) {
        $qris_dinamis = substr($qris_tanpa_crc, 0, $posisi_58) . $tag_54 . substr($qris_tanpa_crc, $posisi_58);
    } else {
        $qris_dinamis = $qris_tanpa_crc . $tag_54;
    }

    return $qris_dinamis . hitungCRC16($qris_dinamis);
}

function hitungCRC16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, "0", STR_PAD_LEFT));
}

function qrisToImageUrl($qris_string, $size = 300) {
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($qris_string);
}