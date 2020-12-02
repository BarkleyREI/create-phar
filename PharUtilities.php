<?php


class PharUtilities {

    /**
     * Determines if the passed file is a PHAR file, determined by the extension.
     * @param $filename
     * @return bool
     */
    public static function IsPhar($filename) {
        $filename = strtolower($filename);
        $e = explode('.', $filename);
        $extension = $e[count($e)-1];
        return ($extension == 'phar');
    }

    public static function ExtractToTempDirectory($rootDirectory, $filename) {

        if (!self::IsPhar($filename)) {
            return;
        }

        $phar = new Phar($filename);
        $phar->extractTo($rootDirectory . '/__lib');

    }

    public static function CleanUp($rootDirectory) {

        self::DeleteDirectoryAndContents($rootDirectory.'/__lib');

    }

    public static function DeleteDirectoryAndContents($dirPath) {

        $it = new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                self::_DeleteFile($file->getRealPath());
                //unlink($file->getRealPath());
            }
        }
        rmdir($dirPath);

    }

    private static function _DeleteFile($filename) {

        if (!defined('WINDOWS_SERVER')) {
            $tmp = dirname(__FILE__);
            if (strpos($tmp, '/', 0) !== false) {
                define('WINDOWS_SERVER', false);
            } else {
                define('WINDOWS_SERVER', true);
            }
        }

        $deleteError = 0;
        if (!WINDOWS_SERVER) {
            if (!unlink($filename)) {
                $deleteError = 1;
            }
        } else {
            $lines = array();
            exec("DEL /F/Q \"$filename\"", $lines, $deleteError);
        }
        if ($deleteError) {
            echo 'file delete error';
        }
    }

}