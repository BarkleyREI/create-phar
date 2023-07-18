<?php

namespace Barkley\CreatePhar\Utilities;

use rei\CreatePhar\Output;

class Docs {

	private string $_projectDirectory;

	public const SHIELDS_MARKUP = '<!--shields-->';
	public const SHIELDS_MARKUP_CLOSE = '<!--/shields-->';
	public const COLOR_ROCKET_RED = 'ea002a';
    public const SIMPLE_RED = 'red';

	public function __construct(string $projectDirectory) {
		if (str_ends_with($projectDirectory,'/')) {
			$projectDirectory = substr($projectDirectory, 0, strlen($projectDirectory)-1);
		}
		$this->_projectDirectory = $projectDirectory;
	}

	/**
	 * Copies the path from the /templates directory to the project. Will return
	 * false if the files already exist, in which case no action has been done. Will
	 * return true on success.
	 * @param string $path
	 * @return bool
	 */
	private function _AddIfNotExist(string $path) : bool {
        $to = $this->_projectDirectory.$path;
		if (!file_exists($to)) {

            $pathinfo = pathinfo($to);
            if (!file_exists($pathinfo['dirname'])) {
                mkdir($pathinfo['dirname'], 0777, true);
            }

            $from =__DIR__.'/../templates'.$path;
            copy($from, $to);
			return true;
		}
		return false;
	}

	/**
	 * Adds all document templates to the project, and returns an array listing all
	 * files added.
	 * @return array
	 */
	public function AddTemplatesIfNotExists() : array {

		$success = array();
		$files = array(
			'/docs/img/barkley-logo.png',
			'/README.md'
		);

		foreach ($files as $file) {
			if ($this->_AddIfNotExist($file)) {
				$success[] = $file;
			}
		}

		return $success;

	}

	private function _GetShieldMarkup($title, $label, $message, $color = self::SIMPLE_RED, ?string $linkUrl = null) : string {
		$url = 'https://img.shields.io/static/v1?label='.urlencode($label).'&message='.urlencode($message).'&color='.$color;

        //file_put_contents($this->_projectDirectory."/docs/img/$title.svg", file_get_contents($url));

		$cLabel = str_replace('[','', $label);
		$cLabel = str_replace(']','', $cLabel);
		$cLabel = str_replace('"','&quot;', $cLabel);

		$cMessage = str_replace('[','', $message);
		$cMessage = str_replace(']','', $cMessage);
		$cMessage = str_replace('"','&quot;', $cMessage);

		//return '<img alt="'.$cLabel.' - '.$cMessage.'" src="'.$url.'" />';
        // /docs/img/'.$title.'.svg
        $imgMarkup = '!['.$cLabel.' - '.$cMessage.']('.$url.')';
        if (!empty($linkUrl)) {
            $imgMarkup = "[$imgMarkup]($linkUrl)";
        }
		return "$imgMarkup\r\n";


	}

    /**
     * Updates the shield images in the README.md file of the project. Will return
     * false if this section cannot be found within the project.
     * @param string $version
     * @param int|null $errorCount
     * @param string $createPharVersion
     * @return bool
     */
	public function UpdateShields(string $version, ?int $errorCount, string $createPharVersion) : bool {
		$filePath = $this->_projectDirectory.'/README.md';
		$content = file_get_contents($filePath);

		if (!str_contains($content, self::SHIELDS_MARKUP)) {
			return false;
		}

		$shields = self::SHIELDS_MARKUP."\r\n";
		$shields .= $this->_GetShieldMarkup('version', 'Version', $version);
        //$shields .= '<a href="https://github.com/BarkleyREI/create-phar">';
        $shields .= $this->_GetShieldMarkup('version-create-phar', 'Built with Create-Phar', $createPharVersion, 'lightgrey', 'https://github.com/BarkleyREI/create-phar');
        //$shields .= '</a>';
        //$shields .= '<a href="./analysis.json">';
        if ($errorCount == null) {
            $errorCount = "Not Run";
        }
        $shields .= $this->_GetShieldMarkup('analysis', 'Analyzer Errors', $errorCount, 'lightgrey', './analysis.json');
        //$shields .= '</a>';
		$shields .= self::SHIELDS_MARKUP_CLOSE."\r\n";;

		if (str_contains($content, self::SHIELDS_MARKUP_CLOSE)) {
			$cNew = substr($content, 0, strpos($content, self::SHIELDS_MARKUP));
			$cNew .= $shields;
			$cNew .= substr(
				$content,
				strpos($content, self::SHIELDS_MARKUP_CLOSE) + strlen(self::SHIELDS_MARKUP_CLOSE)
			);
			$content = $cNew;
		} else {
			$content = str_replace(self::SHIELDS_MARKUP, $shields, $content);
		}
		file_put_contents($filePath, $content);

		return true;
	}

	private function _GetBuildRoot() : string {
		return $this->_projectDirectory . "/build";
	}

	public function WriteFileToBuild($file, $content, $createIfNotExists = true) {
		if (!str_starts_with($file, '/')) {
			$file = '/' . $file;
		}
		$fullPath = $this->_GetBuildRoot() . $file;

		$this->_WriteFile($fullPath, $content, $createIfNotExists);
	}

	public function WriteToFile($file, $content, $createIfNotExists = true) {
		if (!str_starts_with($file, '/')) {
			$file = '/' . $file;
		}
		$fullPath = $this->_projectDirectory . $file;

		$this->_WriteFile($fullPath, $content, $createIfNotExists);
	}

	public function ReadTemplateFile($file) : string {
		return file_get_contents(__DIR__.'/../templates'.$file);
	}

	private function _WriteFile($filePath, $content, $createIfNotExists = true) {
		if (!file_exists($filePath)) {
			if (!$createIfNotExists) {
				Output::Error('File ' . $filePath . ' does not exist. Please create an empty file there first.');
			}
		}
		file_put_contents($filePath, $content);
	}

}