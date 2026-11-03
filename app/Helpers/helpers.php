<?php

if (!function_exists('statusJumlah')) {
    function statusJumlah(int $jumlah): string
    {
        if ($jumlah >= 3) {
            return 'Banyak';
        }
        return 'Sedikit';
    }
}