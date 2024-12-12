<?php

namespace Barkley\CreatePhar\Utilities;

use Barkley\CreatePhar\Config\ProjectConfig;
use Barkley\CreatePhar\Utilities\Docs;
use Barkley\CreatePhar\Utilities\Version;
use InvalidArgumentException;
use rei\CreatePhar\Output;

require_once __DIR__.'/../Output.php';

class Docsify {

	/**
	 * Returns the version of Docsify currently installed on the system, or null if its not found.
	 * @return string|null
	 */
	public static function GetVersion() : ?string {
		$o = shell_exec('docsify -v');
		if ($o === null || str_contains($o, 'not found')) { return null; }
		$o = array_map(fn($e): string => trim($e)??null, explode("\n", $o));
		$o = array_filter($o);
		return array_pop($o);
	}

	public static function HasBeenInitialized() : bool {
		return is_dir('./docsify');
	}

	public static function Initialize(ProjectConfig $projectConfig) : void {
		self::_MakeDir('./docsify');

		$contents = file_get_contents(__DIR__.'/../templates/docsify/index.html');

		// Replacements
		$contents = str_replace('{{name}}', str_replace("'", "&apos;", $projectConfig->GetProjectName()), $contents);

		// Replacements for language highlighting
		$languages = $projectConfig->GetDocsifyCodeHighlights();
		$embeds = '';
		foreach ($languages as $language) {
			$embeds .= '<script src="//cdn.jsdelivr.net/npm/prismjs@1/components/prism-'.$language.'.min.js"></script>';
		}
		$contents = str_replace('{{highlights}}',$embeds, $contents);

		file_put_contents('./docsify/index.html', $contents);

		copy(__DIR__.'/../templates/docsify/theme-overrides.css', './docsify/theme-overrides.css');

		self::_MakeDir('./.github');
		self::_MakeDir('./.github/workflows');
		copy(__DIR__.'/../templates/.github/workflows/static.yml', './.github/workflows/static.yml');

	}

	public static function CopyRootReadme() : void {

		// If there is a root /docs/README.md file under the project, use that instead
		if (file_exists('./docs/README.md')) {
			copy('./docs/README.md', './docsify/README.md');
		} else {
			$docs = new Docs('.');
			file_put_contents('./docsify/README.md', $docs->GetContents(true));
		}


//		if (file_exists('./docsify/README.md')) {
//			unlink('./docsify/README.md');
//		}
//		copy('./README.md', './docsify/README.md');
	}

	public static function CopyDocsFolder() : void {
		self::_CopyDirectoryAndContents('./docs', './docsify/docs');
	}

	private static function _RecursiveGlob($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			$files = array_merge(
				[],
				...[$files, self::_RecursiveGlob($dir . "/" . basename($pattern), $flags)]
			);
		}
		return $files;
	}

	private static function _MultiExplode(array $delimiters, string $string) : array {
		$ready = str_replace($delimiters, $delimiters[0], $string);
		return explode($delimiters[0], $ready);
	}

	public static function BuildSidebar(string $version, ?string $createPharVersion = null, array $exclusions = ['README.md']) : void {

		Output::Verbose('Building Sidebar...');

		$contents = array();
		$contents[] = '- [Overview](README)';

		//foreach(self::_RecursiveGlob("./docsify/docs/*.md") as $file) {

		$dir = './docsify/docs';
		foreach (array_diff(scandir($dir), ['..','.']) as $filename) {

			$file = "{$dir}/{$filename}";
			Output::Verbose("\t{$file}");

			if (is_file($file) && str_ends_with($file, '.md')) {
				$bn = basename($file);
				$bnWithDir = str_replace('./docsify/docs/', '', $file);
				Output::Verbose("\t\t✅ File added to sidebar!");
				if (!in_array($bnWithDir, $exclusions)) {
					$dn = '';
					// If the file is a nested README, treat it as the folder index
					if (strtolower($bn) === 'readme.md') {
						$e = self::_MultiExplode(['/', '\\'], $file);
						$dn = $e[count($e)-2];
					} else {
						$dn = self::_GetDisplayName($bn);
					}
					$contents[] = "- [{$dn}](docs/{$bn})";
				}
			} elseif (is_dir($file) && !in_array($filename, ['img'])) {

				Output::Verbose("\t\tIs Directory");

				// Look for directory index
				if (is_file("$file/README.md")) {
					$contents[] = "- [{$filename}](docs/{$filename}/README.md)";
					Output::Verbose("\t\t✅ Added index file for {$filename}");
				} else {
					Output::Error("Docsify directory {$file} does not have a README.md index file.");
				}

				foreach (array_diff(scandir($file), ['..','.', 'README.md']) as $nestedFilename) {
					if (is_file("./docsify/docs/{$filename}/{$nestedFilename}")) {
						Output::Verbose("\t\t✅ Nested File: {$nestedFilename}");
						$dn = self::_GetDisplayName($nestedFilename);
						$contents[] = "  - [{$dn}](docs/{$filename}/{$nestedFilename})";
					}
				}

			}
		}

		$contents[] = '';
		$contents[] = '';
		$short = Version::ConvertToShortVersion($version);
		$contents[] = "<div id='sidebar-version'>Documentation built for version <strong>{$short}</strong>";
		if ($createPharVersion !== null) {
			$contents[] = "<br/>Built with create-phar version <strong>{$createPharVersion}</strong>";
		}
		$contents[] .= '</div>';

		file_put_contents('./docsify/_sidebar.md', implode("\n", $contents));

	}

	private static function _GetDisplayName(string $filename) : string {
		$dn = str_replace('.md', '', $filename);
		$dn = str_replace('.MD', '', $dn);
		$dn = str_replace(['-','_'], ' ', $dn);

		return $dn;
	}

	private static function _CopyDirectoryAndContents(string $source, string $destination, array $exclusions = ['README.md']) : void {

		if (!is_dir($source)) {
			Output::Warning("Directory {$source} does not exist");
			return;
		}
		self::_MakeDir($destination);

		foreach(glob("{$source}/*") as $file) {
			//Output::Info('Copying...'.$file);
			if (is_dir($file)) {
				self::_CopyDirectoryAndContents($file, $destination.'/'.basename($file), []);
			} else {

				if (!in_array(basename($file), $exclusions)) {
					copy($file, $destination . '/' . basename($file));
				}
				//Output::Info('Copied file to '.$destination.'/'.$file);
			}
		}

	}

	private static function _DeleteDir(string $dir) : void {
		if (! is_dir($dir)) {
			throw new InvalidArgumentException("$dir must be a directory");
		}
		if (substr($dir, strlen($dir) - 1, 1) != '/') {
			$dir .= '/';
		}
		$files = glob($dir . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::_DeleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}

	private static function _MakeDir(string $dir) : void {
		if (is_dir($dir)) {
			self::_DeleteDir($dir);
		}

		mkdir($dir);
	}

}