<?php


class Fixes {

    private string $_projectDirectory;

    public function __construct($projectDirectory) {
        $this->_projectDirectory = $projectDirectory;
    }

    private function _GetComposerConfig() : array {
        return json_decode(file_get_contents($this->_projectDirectory.DIRECTORY_SEPARATOR.'composer.json'), true);
    }

    private function _SaveComposerConfig(string $contents) {
        file_put_contents($this->_projectDirectory.DIRECTORY_SEPARATOR.'composer.json', $contents);
    }

    public function FixLocalRepositorySymlink() {
        $conf = $this->_GetComposerConfig();

        //foreach ($conf['repositories'] as $repo) {
        for ($i = 0; $i < count($conf['repositories']); $i++) {

            if ($conf['repositories'][$i]['type'] == 'path') {

                if (!array_key_exists('options', $conf['repositories'][$i])) {
                    $conf['repositories'][$i]['options'] = array();
                }
                $conf['repositories'][$i]['options']['symlink'] = false;

            }

        }

        $this->_SaveComposerConfig(json_encode($conf));

    }

    public function FixIndexFile() {
        $contents = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'index.php');
        file_put_contents($this->_projectDirectory.DIRECTORY_SEPARATOR.'index.php', $contents);
    }

}