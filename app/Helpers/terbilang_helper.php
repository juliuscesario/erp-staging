<?php

if ( ! function_exists('number_to_words'))
{
    function number_to_words($number)
    {
        $before_comma = trim(to_word($number));
        $after_comma = trim(comma($number));

        $output = '';
        switch (true){
            case !empty($after_comma): // Perbaikan kecil agar lebih aman
                $output = ucwords($before_comma.' Koma '.$after_comma.' Rupiah');
                break;
            default:
                $output = ucwords($before_comma.' Rupiah');
                break;
        }
        return $output;
    }

    function to_word($number)
    {
        $words = "";
        $arr_number = array(
            "", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam",
            "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"
        );

        if($number < 12) {
            $words = " " . $arr_number[$number];
        } else if($number < 20) {
            $words = to_word($number - 10) . " Belas";
        } else if($number < 100) {
            $words = to_word($number / 10) . " Puluh" . to_word($number % 10);
        } else if($number < 200) {
            $words = "Seratus" . to_word($number - 100);
        } else if($number < 1000) {
            $words = to_word($number / 100) . " Ratus" . to_word($number % 100);
        } else if($number < 2000) {
            $words = "Seribu" . to_word($number - 1000);
        } else if($number < 1000000) {
            $words = to_word($number / 1000) . " Ribu" . to_word($number % 1000);
        } else if($number < 1000000000) {
            $words = to_word($number / 1000000) . " Juta" . to_word($number % 1000000);
        } else {
            $words = "undefined";
        }
        return $words;
    }

    function comma($number)
    {
        // Perbaikan kecil: Ambil bagian desimal
        $after_comma = floor(($number * 100) % 100);
        if ($after_comma == 0) return "";

        return to_word($after_comma);
    }
}