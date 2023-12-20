<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Barkley\CreatePhar\Utilities\Composer;
use Barkley\CreatePhar\Utilities\Docs;
use Barkley\CreatePhar\Utilities\Validation;
use Barkley\CreatePhar\Utilities\Version;
use rei\CreatePhar\Output;

require_once(__DIR__.'/Utilities/Composer.php');
require_once(__DIR__.'/Utilities/Docs.php');
require_once(__DIR__.'/Utilities/Version.php');
//require_once(__DIR__.'/GitHub/Repository.php');
require_once(__DIR__ . '/Validation.php');
require_once(__DIR__ . '/Utilities/Analyzer.php');


//$repo = new Repository();
//$_latestReleaseCPhar = $repo->GetLatestReleaseVersion();

// Settings
$_createPharVersion = '2.0.0-BETA1';
$_minPhpVersion = '8.1.0';
$showColors = true;

$_latestReleaseCPhar = $_createPharVersion;

/**
 * View version history on GitHub: https://github.com/BarkleyREI/create-phar
 */

/*--- Includes --*/
require_once(__DIR__.'/Output.php');
require_once(__DIR__.'/PharUtilities.php');

array_shift($argv);

/*--- Variable setup --*/
$projectDirectory = getcwd(); // directory that the project is running from
$configIniPath = $projectDirectory.'/src/php/config.ini';   // project config file
$composer = new Composer($projectDirectory);
$docs = new Docs($projectDirectory);
$version = new Version($projectDirectory, $_createPharVersion);

/*-- Version check/notification --*/
$vc = version_compare($_createPharVersion, $_latestReleaseCPhar);
if ($vc == -1) {
    Output::OutputVisualLine();
    Output::Warning("You are using version $_createPharVersion of Create-Phar. Version $_latestReleaseCPhar is now available.");
    //Output::Message("Download the latest release at ".$repo->GetReleasesUrl());
    Output::OutputVisualLine();
} elseif ($vc == 1) {
    Output::OutputVisualLine();
    Output::Warning("You are using an unreleased version of Create-Phar ($_createPharVersion). The latest release is $_latestReleaseCPhar.");
    //Output::Message("Unless this is expected, download and use the latest release from ".$repo->GetReleasesUrl());
    Output::OutputVisualLine();
}


/*-- Arguments --*/
if (hasArgument('-h')) {
    Output::Heading('create-phar');
    Output::Message('Repository is available online at https://github.com/BarkleyREI/create-phar');
    Output::Message('This project helps manage versioning for PHP projects, by creating stand-alone distributable PHAR files.');

    Output::Heading('Usage Examples');
    Output::Define('create-phar init', 'Initializes a new project in your current directory.');
    Output::Define('create-phar','Builds the project in your current directory, without modifying the version number.');
    Output::Define('create-phar 1.0.0','Builds the project in your current directory, and changes the version number to 1.0.0');

    Output::Heading('Flags');
    Output::Define('-h','Help screen (what you see now)');
    Output::Define('-v','Enables verbose output mode.');
    Output::Define('-u','Updates all Composer dependencies during the build.');
    Output::Define('-i','Provides information on current setup, without running the build.');
    Output::Define('-c','Runs a command against the create-phar version of Composer (ex: create-phar -c self-update)');
    Output::Define('-fixpsr','Setup autoload/psr-4 section of your composer.json to support auto-loading. (ex: create-phar -fixpsr namespace)');

    Output::NewLine();
    exit();
} elseif (hasArgument('-i')) {
    Output::Heading('Builder Information');
    Output::Define('create-phar Version', $_createPharVersion);
    Output::Define('create-phar Directory', __DIR__);
    Output::Define('Composer Version', $composer->GetComposerVersion());
    Output::Define('PHP Version', phpversion());

    Output::Heading('Project Information');
    Output::Define('Current Version',$version->GetCurrentVersion()??'Not set');

    Output::NewLine();
    exit();
} elseif (hasArgument('-c')) {
    Output::Heading('Composer Command Run');
    $v = getValuesFollowingArgument('-c');
    if (empty($v)) {
        Output::Error('Need to pass a value after -c');
    } else {
        $o = $composer->RunCommand($v, true);
        if ($o !== null) {
            Output::Message($o, false);
        }
    }
    exit();
} elseif (hasArgument('-fixpsr')) {

    if ($composer->HasValue('autoload')) {
        Output::Error('There is already an autoload section defined in your composer.json file.');
    }

    $newNamespace = getValuesFollowingArgument('-fixpsr');
    if (empty($newNamespace)) {
        Output::Error('You must specific the new namespace to set');
    }

    if (str_starts_with($newNamespace, '\\')) {
        $newNamespace = substr($newNamespace, 1);
    }
    if (!str_ends_with($newNamespace, '\\')) {
        $newNamespace = $newNamespace . '\\';
    }

    $composerConfig = array();
    $composerConfig['autoload'] = array();
    $composerConfig['autoload']['psr-4'] = array();
    $composerConfig['autoload']['psr-4'][$newNamespace] = "";
    $composer->AddUpdateConfig($composerConfig);

    Output::SuccessEnd("Updated PSR-4 autoload settings to have namespace ".$newNamespace.'. Run create-phar again to build the project.');

}

$verbose = hasArgument('-v');
$composer->SetVerbose($verbose);
$update = hasArgument('-u');

/*--- Initial Output ---*/
Output::Heading('Running create-phar version '.$_createPharVersion);

/*--- Check php.ini settings ---*/
if(ini_get('phar.readonly') == true) {
    Output::Error('php.ini file needs the value for "phar.readonly" set to "Off". Your current php.ini is located at: '.php_ini_loaded_file());
}

if (version_compare($_minPhpVersion,phpversion(),'>')) {
    Output::Error("create-phar has a minimum required version of $_minPhpVersion and you are running ".phpversion());
}

/**--- Initialize if requested ---*/
if (hasArgument('init')) {
    include(__DIR__.'/Initialize.php');
    $init = new \rei\CreatePhar\Initialize($projectDirectory);
    $init->Execute();
}

/*--- Config settings ---*/
Output::Heading("Configuration settings:\n");
if (!file_exists($configIniPath)) {
    Output::Warning('This project may not have initialized. To setup a project at this location, run the command "create-phar init".');
    Output::Error('Exiting... cannot find configuration file at path '.$configIniPath);
}
Output::Message('Project directory: '.$projectDirectory);
Output::Message('Project configuration: '.$configIniPath);
Output::Message('Build script directory: '.__DIR__);

if ($verbose) {
    Output::Message('Running with verbose output.');
} else {
    Output::Message('Running with normal output. To run verbose, pass argument -v');
}

if ($update) {
    Output::Message('Updating Composer dependencies.');
} else {
    Output::Message('Will not update dependencies. To do so, pass argument -u');
}




$ini = parse_ini_file($configIniPath,true);
$project = $ini['project']['name'];
$excludeDirectories = explode(",", $ini['project']['exclude_directories']);
$excludeDirectories[] = '.idea';
if ($verbose) {
	Output::Verbose('Excluding the following directories:');
	foreach ($excludeDirectories as $exDir) {
		Output::Verbose("\t$exDir");
	}
}

$manualCopies = explode(",", $ini['project']['manual_copies']);
if ($verbose) {
	Output::Verbose('Manual copies are as follows:');
	foreach ($manualCopies as $mCopy) {
		Output::Verbose("\t$mCopy");
	}
}

$manualCopyFiles = isset($ini['project']['manual_copy_files']) ? explode(",", $ini['project']['manual_copy_files']) : array();
if (count($manualCopyFiles) > 0) {
    Output::Warning('Your project configuration has a defined \'manual_copy_files\' value. This functionality is no longer supported.');
}

$includePharInCopiedFolders = isset($ini['project']['phar_in_manual_copies']) && $ini['project']['phar_in_manual_copies'] == '1';
if ($includePharInCopiedFolders) {
    Output::Message('PHAR file will be copied into any defined manual_copy directories.');
} else {
    Output::Message('Directories copied through manual_copy will need to reference PHAR outside of their root.');
}



$useDeprecatedVendors = false;
if (array_key_exists('vendor_includes', $ini['project'])) {
    Output::Warning("Your configuration file is using vendor_includes, which has been deprecated in version 1.2.0 and will be removed moving forward. Please move to using vendor_excludes instead.");
    $useDeprecatedVendors = true;
}

$vendorIncludes = array();
$vendorExcludes = array();

if ($useDeprecatedVendors) {
    foreach (explode(',', $ini['project']['vendor_includes']) as $viItem) {
        if (!empty($viItem)) {
            $vendorIncludes[] = $viItem;
            Output::Message("Vendor directory $viItem will be included.\n");
        }
    }
    print "\n";
} else {
    $vendorExcludesString = $ini['project']['vendor_excludes'] ?? null; // array_key_exists('vendor_excludes', $ini['project']) ?  : null;
	if ($vendorExcludesString !== null) {
		foreach (explode(',', $vendorExcludesString) as $veItem) {
			if (!empty($veItem)) {
				$vendorExcludes[] = $veItem;
				Output::Message("Vendor directory $veItem will be excluded.\n");
			}
		}
	}
}


$composerPath = $composer->GetComposerPath();
$composerJsonPath = $composer->GetComposerJsonPath();

if (!file_exists($composerPath)) {
    Output::Error("\tCannot find composer.phar file at ".$composerPath);
} elseif (!file_exists($composerJsonPath)) {
    Output::Error("\tCannot find composer.json file at ".$composerJsonPath);
}

//$composerConfig = json_decode(file_get_contents($composerJsonPath),true);



Output::Heading('Performing Analysis');
if (hasArgument('init')) {
    Output::Info('Skipping analysis for init run...');
} else {
    Output::Info('Analysis temporarily disabled for version 2.0.0');
//    $analyzer = new \Barkley\CreatePhar\Utilities\Analyzer($projectDirectory);
//    $analyzer->FullAnalyze();
//    Output::Info($analyzer->GetFullAnalysisInfo());
//    foreach ($analyzer->GetFileErrorCounts() as $filename => $count) {
//        Output::Message("$count: $filename");
//    }
}



Output::Heading('Performing Validations');
$performFixes = true;
$validation = new Validation($projectDirectory.'/src/php', $performFixes);
$valid = $validation->Validate();
$errors = $validation->GetErrorMessages();
if (count($errors) > 0) {
    Output::Error('The following validation errors have been found: ', false);
    foreach ($errors as $error) {
        Output::Error($error, false);
    }
    if ($valid && $performFixes) {
        Output::Success('These errors have all been fixed.');
    } elseif (!$valid && $performFixes) {
        Output::Warning('Not all of these errors have been able to be fixed. Rerun the build process to list all remaining validation errors.');
    }
} else {
    Output::Success('No validation errors have been found.');
}
if (!$valid) {
    Output::Error('Exiting build process due to failed validations.');
}

// Update Composer
Output::Heading("Upgrading Composer if available:\n");
echo $composer->SelfUpdate();

// Output information on Composer
Output::Heading('Composer Info');
Output::Info("Currently using version ".$composer->GetComposerVersion()." of Composer.");
file_put_contents(__DIR__.'/composer-version.txt', $composer->GetComposerVersion());

if ($update) {
    Output::Heading("Upgrading and installing from Composer:\n");
	echo $composer->RunCommand('u', true);
	echo $composer->RunCommand('i', true);
	//run_shell_cmd('php "' . $composerPath . '" --working-dir="' . escapeshellarg($composerJsonPath) . '" u');
	//run_shell_cmd('php "' . $composerPath . '" --working-dir="' . escapeshellarg($composerJsonPath) . '" i');

	Output::Heading('Installed Composer Projects:');
	//run_shell_cmd('php "' . $composerPath . '" --working-dir="' . escapeshellarg($composerJsonPath) . '" show');
	echo $composer->RunCommand('show', true);
}



/**
 * Setup Composer autloads for this project
 */
Output::Heading("Setting up project to support autoload through Composer:\n");

if (!$composer->HasValue('autoload','psr-4')) {
    Output::Error('You must have setup autoload/psr-4 section of your composer.json to support autoloading. Please fix before continuing. You can add this with the command "create-phar -fixpsr namespace", replacing "namespace" with your root namespace.');
}

echo $composer->RunCommand('dump-autoload --optimize',true);

$namespace = array_keys($composer->GetValue('autoload','psr-4'))[0];


$doPhar = true;
$doManual = true;

$srcRoot = $projectDirectory . "/src/php";
$buildRoot = $projectDirectory . "/build";
$copyRoot = $projectDirectory . "/build";

$fullPath = $buildRoot . "/" . $project . ".phar";

Output::Heading("\nFinalizing Output:\n");



$v = $version->GetNewVersion($argv);
$version->SetNewVersion($v, $namespace);
Output::Info('Set version to '.$v);



// Delete from build root





// define if we are under Windows
$tmp = dirname(__FILE__);
if (strpos($tmp, '/', 0)!==false) {
    define('WINDOWS_SERVER', false);
} else {
    define('WINDOWS_SERVER', true);
}
$deleteError = 0;
if (!WINDOWS_SERVER) {
    $files = glob($buildRoot);
    foreach ($files as $file) {
        // chown($file, 666);
        //array_map('unlink', glob($buildRoot."/*"));
        //rmdir($buildRoot);

        function delTree($dir, $deleteSelf = true) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
            }
            if ($deleteSelf) {
                rmdir($dir);
            }
        }
        delTree($buildRoot, false);

    }

} else {
    $lines = array();
    exec("rd /s /q \"$buildRoot\"", $lines, $deleteError);
    exec("mkdir \"$buildRoot\"", $lines, $deleteError);
}
if ($deleteError) {
    Output::Error('File Deletion Error');
}
//if (is_dir($buildRoot)) {
//    PharUtilities::DeleteDirectoryAndContents($buildRoot);
//}
//exec("mkdir \"$buildRoot\"", $lines, $deleteError);
////mkdir($buildRoot);



if ($doPhar) {

    Output::Heading('Building PHAR file:');

    $phar = new Phar(
        $fullPath,
        0,
        $project . ".phar"
    );


    // Iterate through and create

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS)
    );


//    foreach ($iterator as $key => $value) {
//        PharUtilities::ExtractToTempDirectory($srcRoot, $key);
//    }

    $filterIterator = new CallbackFilterIterator($iterator, function ($file) {

        global $excludeDirectories, $vendorIncludes, $vendorExcludes, $useDeprecatedVendors, $verbose;

        $lcFile = strtolower($file);

		// Fix directory slashes to keep consistent
		$lcFile = str_replace("/", DIRECTORY_SEPARATOR, $lcFile);
		$lcFile = str_replace("\\", DIRECTORY_SEPARATOR, $lcFile);

        $dispFile = $lcFile;
        $maxDispLength = 40;
        if (strlen($dispFile) > ($maxDispLength + 3)) {
            $dispFile = "..." . substr($dispFile, -$maxDispLength);
        }

        foreach ($excludeDirectories as $exDir) {

            $exDir = strtolower($exDir);

            if (strPos($lcFile, DIRECTORY_SEPARATOR."$exDir".DIRECTORY_SEPARATOR) !== false) {
                Output::Verbose("Exclusion: Match on /$exDir/ to $dispFile", $verbose);
                return false;
            } elseif (strPos($lcFile, "/$exDir/") !== false) {
                Output::Verbose("Exclusion: Match on /$exDir/ to $dispFile", $verbose);
                return false;
            }
        }

        if (strpos($lcFile, 'vendor') !== false) {

            $inc = !$useDeprecatedVendors;
            $output = array();

            if ($useDeprecatedVendors) {
                foreach ($vendorIncludes as $vendorInclude) {
                    if (strpos($lcFile, 'vendor/' . $vendorInclude) !== false || strpos($lcFile, 'vendor/autoload.php') !== false) {
                        $inc = true;
                        break;
                    }
                }
            } else {
                foreach ($vendorExcludes as $vendorExclude) {
                    if (strpos($lcFile, 'vendor/' . $vendorExclude) !== false) {
                        $inc = false;
                        break;
                    }
                }
            }

            if (!$inc) {
                Output::Verbose('Vendor exclusion: ' . $dispFile, $verbose);
                return false;
            }

        }

        Output::Verbose('Including: ' . $dispFile, $verbose);
        return true;

    });

    $phar->buildFromIterator($filterIterator, $srcRoot);

    // Add index file from root, if it exists
//    $indexFilePath = $srcRoot . DIRECTORY_SEPARATOR . 'index.php';
//    if (file_exists($indexFilePath)) {
//        $phar->addFile($indexFilePath);
//        Output::Verbose('Added root index.php file',true);
//    }


    $phar->setStub($phar->createDefaultStub("index.php"));

    copy($srcRoot . "/config.ini", $buildRoot . "/" . $project . ".config.ini");

    Output::Info("PHAR file created as " . $buildRoot . "/" . $project . ".phar");

    copy($buildRoot . "/" . $project . ".phar", $buildRoot . "/" . $project . ".ext");
    Output::Info("PHAR file copied as .ext\n");

    // Copy .htaccess file
	$docs->WriteFileToBuild('/.htaccess', file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'.htaccess'));

    //PharUtilities::CleanUp($srcRoot);

}

if ($doManual) {

    function listFolderFiles($dir){
        $ffs = scandir($dir);
        foreach($ffs as $ff){
            if($ff != '.' && $ff != '..'){
                if(is_dir($dir.'/'.$ff)) {
                    listFolderFiles($dir.'/'.$ff);
                }
            }
        }
    }

    Output::Verbose('Copying the following files to build directory:', $verbose);

    $dir = new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS);

    foreach ($dir as $fileinfo) {

        $lcfile = strtolower($fileinfo->getFilename());
        $filename = $fileinfo->getFilename();

        $found = false;

        if (!$found) {
            //$include = false;
            foreach ($manualCopies as $manualDir) {

//Output::Warning($manualDir);

                $manualDir = strtolower($manualDir);
                if (strPos($lcfile, $manualDir) !== false) {

                    Output::Verbose("\t$filename to $copyRoot/$filename", $verbose);

                    //copy_r($srcRoot."/".$lcfile, $copyRoot."/".$lcfile);

                    $files = scandir($srcRoot . DIRECTORY_SEPARATOR . $filename);
                    foreach ($files as $file) {
                        if( $file == "." || $file == ".." ) {
                            continue;
                        }
                        Output::Verbose("\t\t$file", $verbose);

                        $copyDir = $copyRoot.DIRECTORY_SEPARATOR.$filename;
                        if (!file_exists($copyDir)) {
                            Output::Verbose("Creating $copyDir...", $verbose);
                            $mkdirSuccess = mkdir($copyDir, 0777, true);
                            if (!$mkdirSuccess) {
                                die("Directory creation failure on ".$copyDir);
                            }
                        }


                        custom_copy(
                            $srcRoot.DIRECTORY_SEPARATOR.$filename.DIRECTORY_SEPARATOR.$file,
                            $copyDir.DIRECTORY_SEPARATOR.$file
                        );
                    }

                    //$include = true;
                    break;
                }



            }
        }



        //var_dump($fileinfo->getFilename());
    }

    if ($includePharInCopiedFolders) {
        foreach ($manualCopies as $manualDir) {
            $src = $buildRoot . "/" . $project . ".phar";
            $dest = $copyRoot.DIRECTORY_SEPARATOR.$manualDir.DIRECTORY_SEPARATOR.$project.'.phar';
            copy($src, $dest);

            $add = '';
            if (!file_exists($copyRoot.DIRECTORY_SEPARATOR.$manualDir.DIRECTORY_SEPARATOR.'.htaccess')) {
                copy($buildRoot.'/.htaccess', $copyRoot.DIRECTORY_SEPARATOR.$manualDir.DIRECTORY_SEPARATOR.'.htaccess');
                $add = ' and .htaccess';
            }

            Output::Info('Copied PHAR'.$add.' to manually copied directory '.$manualDir);
        }

    }

    // Copy version file in
    copy($projectDirectory."/version.txt", $copyRoot.DIRECTORY_SEPARATOR."version.txt");

}
Output::Success("PHAR creation process completed!");

/******************/

Output::Heading('Handling Document Logic');
$filesAdded = $docs->AddTemplatesIfNotExists();
if ($verbose) {
	if (count($filesAdded) == 0) {
		Output::Info('No new files added.');
	} else {
		foreach ($filesAdded as $file) {
			Output::Info('Added file ' . $file);
		}
	}
}

$errorCount = null;
if (isset($analyzer)) {
    $errorCount = $analyzer->GetErrorCountTotal();
}

$shieldResult = $docs->UpdateShields($version->GetCurrentShortVersion(), $errorCount, $_createPharVersion);
if (!$shieldResult) {
	Output::Warning('To add dynamic shield icons to your project\'s README.md file, add the text '.Docs::SHIELDS_MARKUP.' within the file.');
} else {
	Output::Info('Icons updated in your project\'s README.md file.');
}

/*******************/

Output::Heading("Create local composer project:");

$cp_v = $composer->UpdateVersion($v);
Output::Message("Composer project updated as version $cp_v");











/**
 * Determines if a certain argument value was set
 * @param $argCheck
 * @return bool
 */
function hasArgument($argCheck) {
    global $argv;
    if ($argv === null || count($argv) === 0) {
        return false;
    }
    foreach ($argv as $arg) {
        if (strtolower($arg) === strtolower($argCheck)) {
            return true;
        }
    }
    return false;
}

function getValuesFollowingArgument($arg) : bool|string {
    global $argv;
    if (!hasArgument($arg)) {
        return false;
    }

    for ($i = 0; $i < count($argv); $i++) {
        $iArg = $argv[$i];
        if (strtolower($iArg) === strtolower($arg)) {
            if ($i >= count($argv) + 1) {
                return false;
            }
            return implode(' ',array_slice($argv, $i+1));
        }
    }

    return false;

}

function getArgument($index) {
    global $argv;
    if ($argv === null) { return null; }
    if ($index > count($argv)) {
        return null;
    }
    return $argv[$index];
}







function custom_copy($src, $dst) {

    global $verbose;

    Output::Verbose("custom_copy:", $verbose);
    Output::Verbose("\tSource: $src", $verbose);
    Output::Verbose("\tSource: $dst", $verbose);
    
    if (!is_dir($src)) {
        copy($src, $dst);
        return;
    }

    // open the source directory
    $dir = opendir($src);

    // Make the destination directory if not exist
    @mkdir($dst);

    // Loop through the files in source directory
    while( $file = readdir($dir) ) {

        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) )
            {

                // Recursively calling custom copy function
                // for sub directory
                custom_copy($src . '/' . $file, $dst . '/' . $file);

            }
            else {
                $source = $src . DIRECTORY_SEPARATOR . $file;
                $destination = $dst . DIRECTORY_SEPARATOR . $file;
                //copy($src . '/' . $file, $dst . '/' . $file);
                copy($source, $destination);
                Output::Verbose('Copied '.$destination, $verbose);
            }
        }
    }

    closedir($dir);
}


function run_shell_cmd($shellCmd) {

	global $verbose;

	Output::Verbose('Running Shell Command: '.$shellCmd, $verbose);
	echo shell_exec($shellCmd);
}