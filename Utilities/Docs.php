<?php

namespace Barkley\CreatePhar\Utilities;

use rei\CreatePhar\Output;

class Docs {

	private string $_projectDirectory;

	public const SHIELDS_MARKUP = '<!--shields-->';
	public const SHIELDS_MARKUP_CLOSE = '<!--/shields-->';
	public const COLOR_ROCKET_RED = 'ea002a';

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
		if (!file_exists($this->_projectDirectory.$path)) {
			file_put_contents(
				$this->_projectDirectory.$path,
				__DIR__.'/../templates'.$path
			);
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

	private function _GetShieldMarkup($label, $message, $color = self::COLOR_ROCKET_RED) : string {
		$url = 'https://img.shields.io/static/v1?label='.urlencode($label).'&message='.urlencode($message).'&color='.$color;

		$cLabel = str_replace('[','', $label);
		$cLabel = str_replace(']','', $cLabel);
		$cLabel = str_replace('"','&quot;', $cLabel);

		$cMessage = str_replace('[','', $message);
		$cMessage = str_replace(']','', $cMessage);
		$cMessage = str_replace('"','&quot;', $cMessage);

		return '<img alt="'.$cLabel.' - '.$cMessage.'" src="'.$url.'" />';
		//return '!['.$cLabel.' - '.$cMessage.']('.$url.')';
	}

	/**
	 * Updates the shield images in the README.md file of the project. Will return
	 * false if this section cannot be found within the project.
	 * @param $version
	 * @return bool
	 */
	public function UpdateShields($version) : bool {
		$filePath = $this->_projectDirectory.'/README.md';
		$content = file_get_contents($filePath);

		if (!str_contains($content, self::SHIELDS_MARKUP)) {
			return false;
		}

		$shields = self::SHIELDS_MARKUP;
		$shields .= $this->_GetShieldMarkup('Version', $version);
		$shields .= self::SHIELDS_MARKUP_CLOSE;

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