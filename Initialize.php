<?php

namespace rei\CreatePhar;

use Barkley\CreatePhar\Config\ProjectConfig;
use Barkley\CreatePhar\Utilities\Docsify;

require_once(__DIR__.'/Output.php');
require_once(__DIR__.'/Utilities/Docsify.php');

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
        Output::Heading('Creating a new project at this location...');

        if (is_dir($this->_projectDirectory.$ds.'src')) {
            Output::Error('.'.$ds.'src directory already exists. Can only initialize a project in an empty directory.');
        }

        Output::Info('Project: '.$this->_projectName);
        Output::Info('Root Namespace: rei\\'.$this->_namespace);

        mkdir($this->_projectDirectory.$ds.'src');
        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php');
        Output::Message('Add PHP code into the root of .'.$ds.'src'.$ds.'php'.$ds);
        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Config');

        mkdir($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Views');
        Output::Message('Add non-built views into .'.$ds.'src'.$ds.'php'.$ds.'Views'.$ds);

        // Add initial config
        $config = file_get_contents(__DIR__.$ds.'templates'.$ds.'config.ini');
        $config = str_replace('{project}', $this->_projectName, $config);
        $file = $ds.'src'.$ds.'php'.$ds.'config.ini';
        file_put_contents($this->_projectDirectory.$file, $config);
        Output::Message('Initial config file created at .'.$file);

        // Add initial composer.json
        $config = file_get_contents(__DIR__.$ds.'templates'.$ds.'composer.json');
        $config = str_replace('{project}', $this->_projectName, $config);
        $config = str_replace('{namespace}', $this->_namespace, $config);
        $file = $ds.'src'.$ds.'php'.$ds.'composer.json';
        file_put_contents($this->_projectDirectory.$file, $config);
        Output::Message('Initial composer.json file create at .'.$file);

        $config = file_get_contents(__DIR__.$ds.'templates'.$ds.'index.php');
        $file = $ds.'src'.$ds.'php'.$ds.'index.php';
        file_put_contents($this->_projectDirectory.$file, $config);
        Output::Message('Initial index file created at .'.$file);

        // Create empty version files
        file_put_contents($this->_projectDirectory.$ds.'src'.$ds.'php'.$ds.'Config'.$ds.'Version.php', '## DO NOT EDIT ##');
        file_put_contents($this->_projectDirectory.$ds.'version.txt', '0.0.1.0');

        // Build directory
        mkdir($this->_projectDirectory.$ds.'build');
        file_put_contents($this->_projectDirectory.$ds.'build'.$ds.'version.txt', '0.0.1.0');
        Output::Message('Builds will be placed at .'.$ds.'build'.$ds.' with an initial version number of 0.0.1.0');

        // Copy Lando Init file
        $fc = file_get_contents(__DIR__.$ds.'templates'.$ds.'.lando.yml');
        $fc = str_replace('{project}', $this->_projectName, $fc);
        file_put_contents($this->_projectDirectory.$ds.'.lando.yml', $fc);
        Output::Message('Added basic Lando config file that serves files from the built \'views\' directory. Run command \'lando start\' to initialize Lando from the root.');

        Output::Info('Initialization complete. To build the project run \'create-phar\' from this root directory.');
        Output::Info('Running initial build...');
        Output::Message('');
    }

	public function InitializeDocsify(ProjectConfig $projectConfig) : void {
		Output::Heading('Initializing Docsify');
		$docsifyVersion = Docsify::GetVersion();
		if ($docsifyVersion === null) {
			Output::Warning('Docsify not found on your system, so will not be initialized. To install follow instructions at https://docsify.js.org/#/quickstart');
			return;
		}
		Output::Message("Version {$docsifyVersion} of Docsify is installed on your system.");

		$exists = Docsify::HasBeenInitialized();
		if ($exists) {
			Output::Warning('Docsify already initialized.');
			return;
		}

		Docsify::Initialize($projectConfig);
		//shell_exec('npm i docsify-cli -g');
	}

}