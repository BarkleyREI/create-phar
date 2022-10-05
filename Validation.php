<?php

require_once(__DIR__.DIRECTORY_SEPARATOR.'Fixes.php');

class Validation {

    private string $_projectDirectory;
    private Fixes $_fixes;
    private bool $_performFixes;
    private array $_errorList;

    public function __construct($projectDirectory, $performFixes = true) {
        $this->_projectDirectory = $projectDirectory;
        $this->_fixes = new Fixes($projectDirectory);
        $this->_performFixes = $performFixes;
        $this->_errorList = array();
    }

    private function _GetComposerConfig() : array {
        $content = file_get_contents($this->_projectDirectory.DIRECTORY_SEPARATOR.'composer.json');
        $ary = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        return $ary;
    }

    /**
     * Returns an array of errors that were encountered. Validate() function needs called prior to this.
     * @return array
     */
    public function GetErrorMessages() {
        return $this->_errorList;
    }

    /**
     * Performs validations, and fixes issues if applicable. Additionally, for each failed validation, an error message
     * is added to the error message queue.
     * @return bool
     */
    public function Validate() {

        $valid = true;
        $this->_errorList = array();

        if (!$this->_IsValidLocalRepositorySymlink()) {
            $this->_errorList[] = 'Local composer repositories require options/symlink to be set to false to properly build.';
            if ($this->_performFixes) {
                $this->_fixes->FixLocalRepositorySymlink();
            } else {
                $valid = false;
            }
        }

        if (!$this->_HasIndexFile()) {
            $this->_errorList[] = 'Project requires an index.php file in the root which calls the autoloader.';
            if ($this->_performFixes) {
                $this->_fixes->FixIndexFile();
            } else {
                $valid = false;
            }
        }

        return $valid;

    }

    /**
     * Checks whether local composer repository references have their symlink to false.
     * @return bool
     */
    private function _IsValidLocalRepositorySymlink() {
        $conf = $this->_GetComposerConfig();

        if (isset($conf['repositories'])) {
            foreach ($conf['repositories'] as $repo) {

                if ($repo['type'] == 'path') {

                    if (!isset($repo['options']['symlink']) || $repo['options']['symlink'] !== false) {
                        return false;
                    }

                }

            }
        }

        return true;
    }

    /**
     * Determines if the project has an index file in the root.
     * @return bool
     */
    private function _HasIndexFile() {
        return file_exists($this->_projectDirectory.DIRECTORY_SEPARATOR.'index.php');
    }

}