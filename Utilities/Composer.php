<?php

namespace Barkley\CreatePhar\Utilities;

class Composer {

    /**
     * Returns the full path to the Composer phar file in this project.
     * @return string
     */
    public function GetComposerPath() : string {
        return __DIR__ . '/../composer.phar';
    }

    /**
     * Returns the directory path that would contain the Composer JSON file for the current project.
     * @return string
     */
    public function GetComposerJsonDirectory() : string {
        return $this->_projectDirectory . '/src/php/';
    }

    /**
     * Get full path to this project's Composer configuration
     * @return string
     */
    public function GetComposerJsonPath() : string {
        return $this->GetComposerJsonDirectory() . '/composer.json';
    }

    public function GetComposerVersion() : string {
        //$output = shell_exec('php "'.$composerPath.'" --working-dir "'.$composerJsonPath.'" -V');
        $output = shell_exec('php "'.$this->GetComposerPath().'" -V');
        $output = substr($output, strlen('Composer version '));
        return substr($output, 0, strpos($output, ' '));
    }

    /**
     * Runs a command against the create-phar's installed version of Composer
     * @param $command
     * @param bool $useWorkingDirectory
     * @return string|null
     */
    public function RunCommand($command, $useWorkingDirectory = false) : ?string {
        if ($useWorkingDirectory) {
            return $this->_ShellExec('php "'.$this->GetComposerPath().'" --working-dir="'.$this->GetComposerJsonDirectory().'" '.$command);
        }
        return $this->_ShellExec('php "'.$this->GetComposerPath().'" '.$command);
    }

    public function SelfUpdate() : string {
        $output = $this->RunCommand('self-update');
        return $output;
    }

    private function _ShellExec($cmd) : string {
        //echo "\n\n".$cmd."\n\n\n";
        return shell_exec($cmd.' 2>&1');
    }

    private $_projectDirectory;

    public function __construct($projectDirectory) {
        $this->_projectDirectory = $projectDirectory;
    }

    public function UpdateVersion($newVersion) {

        if (strpos($newVersion, "-") !== false) {
            $newVersion = explode("-",$newVersion)[0];
        }

        $contents = $this->_ReadToArray();
        $contents["version"] = $newVersion;
        $this->_WriteFromArray($contents);

        return $newVersion;

    }

    /**
     * Determines if the project's Composer configuration has the specified key defined.
     * @param $key
     * @return bool
     */
    public function HasValue(string ...$key) : bool {
        $ary = $this->_ReadToArray();
        foreach ($key as $k) {
            if (!array_key_exists($k, $ary)) {
                return false;
            }
            $ary = $ary[$k];
        }
        return true;
    }

    public function GetValue(string ...$key)  {
        $ary = $this->_ReadToArray();
        foreach ($key as $k) {
            $ary = $ary[$k];
        }
        return $ary;
    }

    /**
     * Adds or updates values in the Composer configuration to what's passed in.
     * @param $values
     * @return void
     */
    public function AddUpdateConfig($values) {
        $config = $this->_ReadToArray();
        $config = array_merge($config, $values);
        $this->_WriteFromArray($config);
    }

    private $_contents = null;
    private function _ReadToArray() {
        if ($this->_contents == null) {
            $this->_contents = json_decode(file_get_contents($this->GetComposerJsonPath()), true);
        }
        return $this->_contents;
    }

    private function _WriteFromArray($ary) {
        $json = json_encode($ary, JSON_PRETTY_PRINT);
        file_put_contents($this->GetComposerJsonPath(), $json);
        $this->_contents = $ary;
    }

}