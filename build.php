<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use rei\CreatePhar\Output;

require_once('ComposerProject.php');
require_once('Validation.php');

// Settings
$_createPharVersion = '1.3.16';
$showColors = true;

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

/*-- Arguments --*/
$verbose = hasArgument('-v');
$update = hasArgument('-u');

/*--- Initial Output ---*/
Output::Heading('Running create-phar version '.$_createPharVersion);

/*--- Check php.ini settings ---*/
if(ini_get('phar.readonly') == true) {
    Output::Error('php.ini file needs the value for "phar.readonly" set to "Off". Your current php.ini is located at: '.php_ini_loaded_file());
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
    Output::Message('Updating Composer and dependencies.');
} else {
    Output::Message('Will not update Composer or dependencies. To do so, pass argument -u');
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


$composerPath = __DIR__.'/composer.phar';
$composerJsonPath = $projectDirectory.'/src/php/';

if (!file_exists($composerPath)) {
    Output::Error("\tCannot find composer.phar file at ".$composerPath);
} elseif (!file_exists($composerJsonPath.'composer.json')) {
    Output::Error("\tCannot find composer.json file at ".$composerJsonPath."composer.json");
}
$composerConfig = json_decode(file_get_contents($composerJsonPath.'composer.json'),true);



if (hasArgument('fix-psr')) {

    if (array_key_exists('autoload', $composerConfig)) {
        Output::Error('There is already an autoload section defined in your composer.json file.');
    }

    $newNamespace = getArgument(1);
    if (str_starts_with($newNamespace, '\\')) {
        $newNamespace = substr($newNamespace, 1);
    }
    if (!str_ends_with($newNamespace, '\\')) {
        $newNamespace = $newNamespace . '\\';
    }

    $composerConfig['autoload'] = array();
    $composerConfig['autoload']['psr-4'] = array();
    $composerConfig['autoload']['psr-4'][$newNamespace] = "";
    file_put_contents($composerJsonPath.'composer.json', json_encode($composerConfig));

    Output::SuccessEnd("Updated PSR-4 autoload settings to have namespace ".$newNamespace.'. Run create-phar again to build the project.');

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





if ($update) {
    Output::Heading("Upgrading Composer if available:\n");
    echo shell_exec('php "' . $composerPath . '" --working-dir="' . $composerJsonPath . '" self-update');
}

// Get composer version
Output::Heading('Composer Info');
$output = shell_exec('php "'.$composerPath.'" --working-dir "'.$composerJsonPath.'" -V');
$output = substr($output, strlen('Composer version '));
$composerVersion = substr($output, 0, strpos($output, ' '));
Output::Info("Currently using version ".$composerVersion." of Composer.");
file_put_contents(__DIR__.'/composer-version.txt', $composerVersion);

if ($update) {
    Output::Heading("Upgrading and installing from Composer:\n");
    echo shell_exec('php "' . $composerPath . '" --working-dir="' . $composerJsonPath . '" u');
    echo shell_exec('php "' . $composerPath . '" --working-dir="' . $composerJsonPath . '" i');

	Output::Heading('Installed Composer Projects:');
	echo shell_exec('php "' . $composerPath . '" --working-dir="' . $composerJsonPath . '" show');
}



/**
 * Setup Composer autloads for this project
 */
Output::Heading("Setting up project to support autoload through Composer:\n");
if (!isset($composerConfig['autoload']['psr-4'])) {
    Output::Error('You must have setup autoload/psr-4 section of your composer.json to support autoloading. Please fix before continuing. You can add this with the command "create-phar fix-psr namespace", replacing "namespace" with your root namespace.');
}

echo shell_exec('php "'.$composerPath.'" --working-dir="'.$composerJsonPath.'" dump-autoload -o');
$namespace = array_keys($composerConfig['autoload']['psr-4'])[0];


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

function getArgument($index) {
    global $argv;
    if ($argv === null) { return null; }
    if ($index > count($argv)) {
        return null;
    }
    return $argv[$index];
}

function getCurrentVersion() {
    global $projectDirectory;
    if (!file_exists($projectDirectory.'/version.txt')) {
        file_put_contents($projectDirectory.'/version.txt', '1.0.0.0');
        return '1.0.0.0';
    }
    $fgc = file_get_contents($projectDirectory.'/version.txt');
    if ($fgc === false) {
        return null;
    }
    return $fgc;
}

function writeToFile($file, $content, $createIfNotExist = true) {
    if (!file_exists($file)) {
        if (!$createIfNotExist) {
            Output::Error('File ' . $file . ' does not exist. Please create an empty file there first.');
        }
    }
    file_put_contents($file, $content);
}

function setNewVersion($v) {
    global $_createPharVersion, $namespace, $projectDirectory;
    $buildString = "Built with create-phar ($_createPharVersion) on ". date("D M d, Y G:i");
    writeToFile($projectDirectory.'/version.txt', $v);
    writeToFile($projectDirectory.'/src/php/Config/Version.php', "<?php /* Auto-generated from create-phar.php - Do not edit */ namespace ".$namespace."Config; class Version { public static function getBuildInfo() { return '$buildString'; } public static function getVersion() { return '$v'; } }");
}

function getNewVersion($composerVersion) {

    global $argv;

    $v = null;

    if ($argv !== null && is_array($argv)) {
        foreach ($argv as $arg) {
            $p = strpos($arg, '.');
            if ($p !== false && strpos($arg, '.', $p) !== false) {
                $v = $arg;
            }
        }
    }

    if ($v !== null) {

        if (hasArgument('init')) {
            $v = '0.0.1.0';
        }
        Output::Info("Using $v as new version number\n");
    } else {

        $v = getCurrentVersion();
        if ($v === null) {
            $v = "1.0.0";
            Output::Info("Unable to determine current version. Using $v\n");
        } else {
            Output::Info("Current version is $v\n");
        }

    }

    if (substr_count($v, ".") > 2) {
        $e = explode(".",$v);
        $v = $e[0].".".$e[1].".".$e[2];
    }

    $v .= ".".time();

    $v .= "-composer".str_replace('.','_', $composerVersion);

    Output::Info("New version being set to $v\n");

    return $v;
}


function custom_copy($src, $dst) {

    global $verbose;

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
                $destination = strtolower($dst) . DIRECTORY_SEPARATOR . strtolower($file);
                //copy($src . '/' . $file, $dst . '/' . $file);
                copy($source, $destination);
                Output::Verbose('Copied '.$destination, $verbose);
            }
        }
    }

    closedir($dir);
}


$doPhar = true;
$doManual = true;

$srcRoot = $projectDirectory . "/src/php";
$buildRoot = $projectDirectory . "/build";
$copyRoot = $projectDirectory . "/build";

$fullPath = $buildRoot . "/" . $project . ".phar";

Output::Heading("\nFinalizing Output:\n");
$v = getNewVersion($composerVersion);
setNewVersion($v);

// Delete from build root





// define if we under Windows
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
        chown($file, 666);
        array_map('unlink', glob($buildRoot."/*"));
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
    writeToFile($buildRoot . '/.htaccess', file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'.htaccess'));

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


        $found = false;

        if (!$found) {
            //$include = false;
            foreach ($manualCopies as $manualDir) {

//Output::Warning($manualDir);

                $manualDir = strtolower($manualDir);
                if (strPos($lcfile, $manualDir) !== false) {

                    Output::Verbose("\t$lcfile to $copyRoot/$lcfile", $verbose);

                    //copy_r($srcRoot."/".$lcfile, $copyRoot."/".$lcfile);

                    $files = scandir($srcRoot . DIRECTORY_SEPARATOR . $lcfile);
                    foreach ($files as $file) {
                        if( $file == "." || $file == ".." ) {
                            continue;
                        }
                        Output::Verbose("\t\t$file", $verbose);

                        $copyDir = $copyRoot.DIRECTORY_SEPARATOR.$lcfile;
                        if (!file_exists($copyDir)) {
                            Output::Verbose("Creating $copyDir...", $verbose);
                            mkdir($copyDir, 0777, true);
                        }


                        custom_copy($srcRoot.DIRECTORY_SEPARATOR.$lcfile.DIRECTORY_SEPARATOR.$file, strtolower($copyDir.DIRECTORY_SEPARATOR.$file));
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


Output::Heading("Create local composer project:");
$composerJsonFilePath = $composerJsonPath . 'composer.json';
if (!file_exists($composerJsonFilePath)) {
    Output::Warning("Composer JSON file does not exist for this project. Skipping this step.");
    Output::Warning($composerJsonFilePath);
} else {
    $cp = new ComposerProject($composerJsonFilePath);
    $cp_v = $cp->UpdateVersion($v);
    Output::Message("Composer project updated as version $cp_v");
}