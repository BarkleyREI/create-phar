<?php

namespace Barkley\CreatePhar\Utilities;

use Barkley\CreatePhar\GitHub\Repository;
use rei\CreatePhar\Output;

class Docs {

	private string $_projectDirectory;

	public const SHIELDS_MARKUP = '<!--shields-->';
	public const SHIELDS_MARKUP_CLOSE = '<!--/shields-->';

	public const LOGO_MARKUP = '<!--logo-->';
	public const LOGO_MARKUP_CLOSE = '<!--/logo-->';

	public const DOCSIFY_MARKUP = '<!--docsify-->';
	public const DOCSIFY_MARKUP_CLOSE = '<!--/docsify-->';

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

	/**
	 * Perform updates to pre-existing templates due to version updates.
	 * @return array
	 */
	public function DoTemplateVersionUpdates() : array {

		$changes = array();

		$file = $this->_projectDirectory . '/README.md';
		$contents = file_get_contents($file);

		$v_readme_pre_2_1_1 = '<p align="center">
    <a href="https://www.barkleyus.com">
        <img src="./docs/img/barkley-logo.png" alt="Barkley" >
    </a>
</p>';

		if (str_contains($contents, $v_readme_pre_2_1_1)) {

			$contents = str_replace($v_readme_pre_2_1_1, '<!--logo-->', $contents);
			file_put_contents($file, $contents);
			$changes['2.1.1'][] = 'Previous hardcoded Barkley logo present. Updated to placeholder which will use BarkleyOKRP branding moving forward.';

		}

		return $changes;

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

	private function _DoReplacement(string $fullContent, string $fillContent, string $startTag, string $endTag) : bool|string {
		if (!str_contains($fullContent, $startTag)) {
			return false;
		}

		$fillContent = $startTag . $fillContent . $endTag;

		if (str_contains($fullContent, $endTag)) {
			$cNew = substr($fullContent, 0, strpos($fullContent, $startTag));
			$cNew .= $fillContent;
			$cNew .= substr(
				$fullContent,
				strpos($fullContent, $endTag) + strlen($endTag)
			);
			$content = $cNew;
		} else {
			$content = str_replace($startTag, $fillContent, $fullContent);
		}
		return $content;
	}

	public function UpdateDocsifyInfo(Repository $repository) : bool {
		$filePath = $this->_projectDirectory.'/README.md';
		$content = file_get_contents($filePath);

		$url = $repository->GetGithubPagesUrl();
		$info = '<p>Refer to the <a href="'.$url.'" target="_blank">Project Documentation</a> page for this project for more information.</p>';

		$res = $this->_DoReplacement($content, $info, self::DOCSIFY_MARKUP, self::DOCSIFY_MARKUP_CLOSE);

		if ($res === false) { return false; }

		file_put_contents($filePath, $res);

		return true;
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

	/**
	 * Updates the logo area README.md file of the project. Will return
	 * false if this section cannot be found within the project.
	 * @return bool
	 */
	public function UpdateLogo() : bool {
		$filePath = $this->_projectDirectory.'/README.md';
		$content = file_get_contents($filePath);

		if (!str_contains($content, self::LOGO_MARKUP)) {
			return false;
		}

		$logo = self::LOGO_MARKUP."\r\n";
		$logo .= '<p align="center"><a href="https://www.barkleyokrp.com" target="_blank"><img src="https://raw.githubusercontent.com/BarkleyREI/create-phar/master/templates/docs/img/barkley-logo.png" alt="BarkleyOKRP" ></a></p>';
		$logo .= self::LOGO_MARKUP_CLOSE."\r\n";

		if (str_contains($content, self::LOGO_MARKUP_CLOSE)) {
			$cNew = substr($content, 0, strpos($content, self::LOGO_MARKUP));
			$cNew .= $logo;
			$cNew .= substr(
				$content,
				strpos($content, self::LOGO_MARKUP_CLOSE) + strlen(self::LOGO_MARKUP_CLOSE)
			);
			$content = $cNew;
		} else {
			$content = str_replace(self::LOGO_MARKUP, $logo, $content);
		}
		file_put_contents($filePath, $content);

		return true;
	}

	public function GetContents(bool $clean = false) : string {

		$contents = file_get_contents($this->_projectDirectory.'/README.md');

		if (!$clean) { return $contents; }

		if (str_contains($contents, self::LOGO_MARKUP)) {
			$contents =
				substr($contents, 0, strpos($contents, self::LOGO_MARKUP)) .
				substr($contents, strpos($contents, self::LOGO_MARKUP_CLOSE)+strlen(self::LOGO_MARKUP_CLOSE));
		}

		if (str_contains($contents, self::SHIELDS_MARKUP)) {
			$contents =
				substr($contents, 0, strpos($contents, self::SHIELDS_MARKUP)) .
				substr($contents, strpos($contents, self::SHIELDS_MARKUP_CLOSE)+strlen(self::SHIELDS_MARKUP_CLOSE));
		}

		return $contents;

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