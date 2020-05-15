<?php

namespace rei\CreatePhar;

require_once(__DIR__.'/Output.php');

class Initialize {

    private $_projectDirectory;
    private $_namespace;
    private $_projectName;

    public function __construct($projectDirectory) {
        $this->_projectDirectory = $projectDirectory;
        $e = explode(DIRECTORY_SEPARATOR, $projectDirectory);
        $this->_namespace = $e[count($e)-1];
        $this->_projectName = strtolower($this->_namespace);

    }

    public function Execute() {
        $ds = DIRECTORY_SEPARATOR;
        Output::PrintHeading('Creating a new project at this location...');

        if (is_dir($this->_projectDirectory.$ds.'src')) {
            Output::Error('.'.$ds.'src directory already exists. Can only initialize a project in an empty directory.');
        }

        Output::PrintAttentionLine('Project: '.$this->_projectName);
        Output::PrintAttentionLine('Root Namespace: rei\\'.$this->_namespace);

        mkdir($this->_projectDirectory.$ds.'src');
        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php');
        Output::PrintLine('Add PHP code into the root of .'.$ds.'src'.$ds.'php'.$ds);
        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Config');

        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Views');
        Output::PrintLine('Add non-built views into .'.$ds.'src'.$ds.'php'.$ds.'Views'.$ds);

        // Add initial config
        $config = file_get_contents(__DIR__.$ds.'init-config.ini');
        $config = str_replace('{project}', $this->_projectName, $config);
        $file = $ds.'src'.$ds.'php'.$ds.'config.ini';
        file_put_contents($this->_projectDirectory.$file, $config);
        Output::PrintLine('Initial config file created at .'.$file);

        // Add initial composer.json
        $config = file_get_contents(__DIR__.$ds.'init-composer.json');
        $config = str_replace('{project}', $this->_projectName, $config);
        $config = str_replace('{namespace}', $this->_namespace, $config);
        $file = $ds.'src'.$ds.'php'.$ds.'composer.json';
        file_put_contents($this->_projectDirectory.$file, $config);
        Output::PrintLine('Initial composer.json file create at .'.$file);

        // Create empty version files
        file_put_contents($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Config'.$ds.'Version.php', '## DO NOT EDIT ##');
        file_put_contents($this->_projectDirectory.$ds.'version.txt', '0.0.1.0');

        // Build directory
        mkdir($this->_projectDirectory.$ds.'build');
        file_put_contents($this->_projectDirectory.$ds.'build'.$ds.'version.txt', '0.0.1.0');
        Output::PrintLine('Builds will be placed at .'.$ds.'build'.$ds.' with an initial version number of 0.0.1.0');

        Output::PrintAttentionLine('Initialization complete. To build the project run \'create-phar\' from this root directory.');
        Output::PrintAttentionLine('Running initial build...');
        Output::PrintLine();
    }

}