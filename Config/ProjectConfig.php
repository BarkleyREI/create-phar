<?php

namespace Barkley\CreatePhar\Config;

use rei\CreatePhar\Output;

require_once(__DIR__.'/../Output.php');

class ProjectConfig {

    private string $_filename;
    private ?bool $_validated = null;
    private array $_ini = array();

    public function __construct(string $filename) {
        $this->_filename = $filename;
        $this->_validated = $this->Validate(false);
        if ($this->_validated) {
            $this->_ini = parse_ini_file($this->_filename, true);
        }
    }

    public function Validate(bool $doOutput = true) : bool {

        if (!file_exists($this->_filename)) {
            if ($doOutput) {
                Output::Warning('This project may not have initialized. To setup a project at this location, run the command "create-phar init".');
                Output::Error('Exiting... cannot find configuration file at path ' . $this->_filename);
            }
            return false;
        }
        return true;

    }

    public function GetProjectName() : string {
        return $this->_ini['project']['name'];
    }

    public function GetProjectNameAsFunctionPostfix() : string {
        $pn = $this->GetProjectName();
        $pn = strtolower($pn);
        return '_BCP_'.preg_replace("/[^a-z]/", "_", $pn);
    }

    public function GetExcludeDirectories() : array {
        return explode(',', $this->_ini['project']['exclude_directories']);
    }

    public function GetManualCopies() : array {
        return explode(',', $this->_ini['project']['manual_copies']);
    }

    public function GetManualCopyFiles() : array {
        if (!isset($this->_ini['project']['manual_copy_files'])) { return array(); }
        return explode(',', $this->_ini['project']['manual_copy_files']);
    }

    public function GetPharInManualCopies() : bool {
        return isset($this->_ini['project']['phar_in_manual_copies']) && $this->_ini['project']['phar_in_manual_copies'] == '1'
    }

    public function GetRawConfigArray() : array {
        return $this->_ini;
    }

}