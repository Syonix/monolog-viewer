<?php
namespace Syonix\Util;

class String
{
    public static function toAscii($str, $replace=array(), $delimiter='-')
    {
        // Courtesy of Cubiq http://cubiq.org/the-perfect-php-clean-url-generator
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }
}
