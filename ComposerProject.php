<?php

class ComposerProject {

    private $_jsonPath;

    public function __construct($composerJsonPath) {
        $this->_jsonPath = $composerJsonPath;
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

    private function _ReadToArray() {
        return json_decode(file_get_contents($this->_jsonPath), true);
    }

    private function _WriteFromArray($ary) {
        $json = json_encode($ary, JSON_PRETTY_PRINT);
        file_put_contents($this->_jsonPath, $json);
    }

}

?>