<?php

namespace rei\CreatePhar;

class Output {

    private const _lineBreak = "\n";
    private const _lightGreen = '1;32';
    private const _lightCyan = '1;36';
    private const _yellow = '1;33';

    // more colors: http://blog.lenss.nl/2012/05/adding-colors-to-php-cli-script-output/
    public static function colorString($str, $colorCode) {
        global $showColors;
        if ($showColors) {
            return "\033[${colorCode}m$str\033[0m";
        } else {
            return $str;
        }
    }
    public static function printColor($str, $colorCode) {
        echo self::colorString($str, $colorCode);
    }
    public static function printLightGreen($str) {
        self::printColor($str, self::_lightGreen);
    }
    public static function printLightRed($str) {
        self::printColor($str, "1;31");
    }
    public static function printLightCyan($str) {
        self::printColor($str, self::_lightCyan);
    }

    /**
     * Outputs a message about halting the process, and calls die(), ending execution.
     * @param $str
     */
    public static function dieMsg($str) {
        die(self::colorString($str.self::_lineBreak.self::_lineBreak, '1;31'));
    }

    public static function Warning($str) {
        self::printColor($str.self::_lineBreak, '1;33');
    }
    public static function Verbose($str, $verbose = true) {
        if (!$verbose) {
            return;
        }
        self::printColor($str.self::_lineBreak, self::_yellow);
    }
    public static function Error($str = '', $exit = true) {
        $str = self::colorString($str.self::_lineBreak.self::_lineBreak, '1;31');
        if ($exit) {
            die($str);
        } else {
            echo $str;
        }
    }
    public static function PrintLine($str = ''){
        echo $str.self::_lineBreak;
    }
    public static function PrintAttentionLine($str = '') {
        echo self::colorString($str, self::_lightCyan).self::_lineBreak;
    }
    public static function PrintHeading($str) {
        echo self::_lineBreak.self::colorString($str,self::_lightGreen).self::_lineBreak;
    }


}