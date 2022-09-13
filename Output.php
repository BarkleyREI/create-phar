<?php

namespace rei\CreatePhar;

class Output {

    private const _lineBreak = "\n";
    private const _lightGreen = '1;32';
    private const _lightCyan = '1;36';
    private const _yellow = '1;33';
    private const _brightGreen = '1;92';

//$codes=[
//'bold'=>1,
//'italic'=>3, 'underline'=>4, 'strikethrough'=>9,
//'black'=>30, 'red'=>31, 'green'=>32, 'yellow'=>33,'blue'=>34, 'magenta'=>35, 'cyan'=>36, 'white'=>37,
//'blackbg'=>40, 'redbg'=>41, 'greenbg'=>42, 'yellowbg'=>44,'bluebg'=>44, 'magentabg'=>45, 'cyanbg'=>46, 'lightgreybg'=>47
//];

    // more colors: http://blog.lenss.nl/2012/05/adding-colors-to-php-cli-script-output/
    // https://docs.microsoft.com/en-us/windows/console/console-virtual-terminal-sequences
    public static function colorString($str, $colorCode) {
        global $showColors;
        if ($showColors) {
            return "\033[${colorCode}m$str\033[0m";
        } else {
            return $str;
        }
    }
    private static function printColor($str, $colorCode) {
        echo self::colorString($str, $colorCode);
    }
    private static function printLightGreen($str) {
        self::printColor($str, self::_lightGreen);
    }
    private static function printLightRed($str) {
        self::printColor($str, "1;31");
    }
    private static function printLightCyan($str) {
        self::printColor($str, self::_lightCyan);
    }

    /**
     * Outputs a message about halting the process, and calls die(), ending execution.
     * @param $str
     */
//    public static function dieMsg($str) {
//        die(self::colorString($str.self::_lineBreak.self::_lineBreak, '1;31'));
//    }

    public static function Warning($str) {
        self::printColor("⚠ ".$str.self::_lineBreak, self::_yellow);
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
    public static function SuccessEnd($str = '', $exit = true) {
        echo self::colorString($str, self::_brightGreen);
        if ($exit) { exit(); }
    }
    /**
     * Output a general message
     * @param string $str
     */
    public static function Message(string $str){
        $str = self::_RemoveLineBreaks($str);
        echo $str.self::_lineBreak;
    }

    /**
     * Output a message that should require user attention
     * @param string $str
     */
    public static function Info(string $str) {
        $str = self::_RemoveLineBreaks($str);
        echo self::colorString($str, self::_lightCyan).self::_lineBreak;
    }
//    private static function PrintAttentionLine($str = '') {
//        echo self::colorString($str, self::_lightCyan).self::_lineBreak;
//    }
    public static function Heading($str) {
        $str = self::_RemoveLineBreaks($str);
        echo self::_lineBreak.self::colorString($str,self::_lightGreen).self::_lineBreak;
    }
    public static function Success($str) {
        echo self::colorString("🆗 ".$str,self::_lightGreen).self::_lineBreak;
    }
    private static function _RemoveLineBreaks($str) {
        $str = str_replace("\n", "", $str);
        $str = str_replace("\r", "", $str);
        return $str;
    }


}