<?php

namespace Barkley\CreatePhar\Utilities;

require_once(__DIR__.'/Docs.php');

class Version {

	private string $_projectDirectory;
	private string $_createPharVersion;
	private string $_namespace;
	private Docs $_docs;

	public function __construct(string $projectDirectory, string $createPharVersion) {
		$this->_projectDirectory = $projectDirectory;
		$this->_createPharVersion = $createPharVersion;
		$this->_docs = new Docs($this->_projectDirectory);
	}

	public function GetCurrentVersion() : ?string {
		if (!file_exists($this->_projectDirectory.'/version.txt')) {
			file_put_contents($this->_projectDirectory.'/version.txt', '1.0.0.0');
			return '1.0.0.0';
		}
		$fgc = file_get_contents($this->_projectDirectory.'/version.txt');
		if ($fgc === false) {
			return null;
		}
		return $fgc;
	}

	public function GetCurrentShortVersion() : string {
		$v = $this->GetCurrentVersion();
		return self::ConvertToShortVersion($v);
	}

	public static function ConvertToShortVersion(string $version) : string {
		$e = explode('.', $version);
		return $e[0].'.'.$e[1].'.'.$e[2];
	}

	public function SetNewVersion(string $v, string $namespace) {

		$buildString = "Built with create-phar (".$this->_createPharVersion.") on ". date("D M d, Y G:i");

		$this->_docs->WriteToFile('/version.txt', $v, true);

		$vClassStr = $this->_docs->ReadTemplateFile('/Config/Version.php.txt');
		$vClassStr = str_replace('{{namespace}}', $namespace, $vClassStr);
		$vClassStr = str_replace('{{build_string}}', $buildString, $vClassStr);
		$vClassStr = str_replace('{{version_full}}', $this->GetCurrentVersion(), $vClassStr);
		$vClassStr = str_replace('{{version_short}}', $this->GetCurrentShortVersion(), $vClassStr);
		$vClassStr = str_replace('{{createphar_version}}', $this->_createPharVersion, $vClassStr);

		$this->_docs->WriteToFile('/src/php/Config/Version.php', $vClassStr);

	}

	/**
	 * Creates a new version number, using the command line arguments if applicable.
	 * @param array $argv
	 * @return string
	 */
	public function GetNewVersion(array $argv) : string {

		$v = null;

		// Attempt to read version from arguments
		if ($argv !== null && is_array($argv)) {
			foreach ($argv as $arg) {
				$p = strpos($arg, '.');
				if ($p !== false && strpos($arg, '.', $p) !== false) {
					$v = $arg;
				}
			}
		}

		// If argument is passed in, use that (or 0.0.1.0 if this is an init)
		if ($v !== null) {

			if (hasArgument('init')) {
				$v = '0.0.1.0';
			}

			//Output::Info("Using $v as new version number\n");

		} else {

			$v = $this->GetCurrentVersion();

			if ($v === null) {
				$v = "1.0.0";
				//Output::Info("Unable to determine current version. Using $v\n");
			} else {
				//Output::Info("Current version is $v\n");
			}

		}

		// Set $v to the first three version numbers
		if (substr_count($v, ".") > 2) {
			$e = explode(".",$v);
			$v = $e[0].".".$e[1].".".$e[2];
		}

		// Append the time() as the fourth element
		$v .= ".".time();

		//$v .= "-composer".str_replace('.','_', $composerVersion);

		//Output::Info("New version being set to $v\n");

		return $v;
	}

}