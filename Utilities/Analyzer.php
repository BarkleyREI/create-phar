<?php

namespace Barkley\CreatePhar\Utilities;

class Analyzer {

    private string $_projectDirectory;
    private string $_codeDirectory;

    function __construct(string $projectDirectory) {
        $this->_projectDirectory = $projectDirectory;
        $this->_codeDirectory = 'src/php';
    }

    public function FullAnalyze() {
        file_put_contents($this->_projectDirectory.'/phpstan.neon', file_get_contents(__DIR__.'/../phpstan.neon'));
        shell_exec(__DIR__.'/../vendor/bin/phpstan analyze --error-format=prettyJson --no-ansi --no-progress -c '.$this->_projectDirectory.'/phpstan.neon > analysis.json');
    }

    public function GetErrorCountTotal() : int {
        $json = json_decode(file_get_contents($this->_projectDirectory.'/analysis.json'),true);
        return $json['totals']['errors'] + $json['totals']['file_errors'];
    }

    public function GetFullAnalysisInfo() : string {
        $json = json_decode(file_get_contents($this->_projectDirectory.'/analysis.json'),true);
        return $json['totals']['errors'] . ' errors and ' . $json['totals']['file_errors'] . ' file errors found. View analysis.json to see full report.';
    }

    public function GetFileErrorCounts() : array {
        $ret = array();
        $json = json_decode(file_get_contents($this->_projectDirectory.'/analysis.json'),true);
        foreach ($json['files'] as $filename => $info) {
            $key = str_replace($this->_projectDirectory, '', $filename);
            $key = str_replace($this->_codeDirectory, '', $key);
            $ret[$key] = $info['errors'];
        }
        return $ret;
    }

}