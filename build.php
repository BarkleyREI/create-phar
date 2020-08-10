<?php

use rei\CreatePhar\Output;

// Settings
$_createPharVersion = '1.3.1';
$verbose = false;
$showColors = true;

/**
 * Version 1.3.1
 *      - Added creation of index file
 *      - Will attempt to update Composer
 *      - Automated version will now include Composer version as well
 * Version 1.3.0
 *      - Moving to self-contained project (refactoring included)
 *      - Additional build output
 *      - 'init' argument will now build out an initial, blank project
 * Version 1.2.2
 *      - Created version file will now use namespace defined in for PSR-4 autloading in composer.json
 * Version 1.2.1
 * 		- Updated output to flow better
 * 		- Will verify configuration settings for autoloading are set, and fail otherwise.
 * Version 1.2.0
 *      - Moved to specifying composer excludes, instead of includes.
 *      - More visible versioning
 *      - Runs composer upgrade + install prior to full build
 *      - Cleaned output
 * Version 1.1.1
 *      - Verify that project config file exists.
 *      - If version.txt file do not exist, create it
 *      - Modification/cleanup of error messages
 * Version 1.1.0
 *      - Modifications done with Feed Manager 1.3.0
 * Version 1.0.3
 *      - Composer will dump autoloads for this project
 * Version 1.0.2
 *      - Also builds with a "ext" extension, so OmniUpdate can upload it properly as a binary (see http://support.omniupdate.com/oucampus10/interface/content/binary-management.html )
 *      - Now properly excludes directories in all scenarios for build
 * Version 1.0.1 - Added ability to use manual_copy_files
 * Version 1.0.0 - Initial version
 */

/*--- Includes --*/
require_once(__DIR__.'/Output.php');

/*--- Variable setup --*/
$projectDirectory = getcwd(); // directory that the project is running from
$configIniPath = $projectDirectory.'/src/php/config.ini';   // project config file

/*--- Initial Output ---*/
//Output::printLightGreen("\n");
//Output::printLightGreen("Running create-phar version $_createPharVersion.\n");
Output::PrintHeading('Running create-phar version '.$_createPharVersion);
//Output::PrintLine();
//Output::PrintLine('Build: If running from PATH, pass in updated version number as \'create-phar 1.2.4\'');
//Output::PrintLine('Initialize: To create a new project, run \'create-phar init\' from a new directory.');
//Output::PrintLine();
//echo "Pass in the version to update it to a specific version. Example: php .\create-phar.php 1.2.4\n\n";

if (count($argv) >= 2 && $argv[1] == 'init') {
    include(__DIR__.'/Initialize.php');
    $init = new \rei\CreatePhar\Initialize($projectDirectory);
    $init->Execute();
}

/*--- Config settings ---*/
Output::printLightGreen("Configuration settings:\n");
if (!file_exists($configIniPath)) {
    Output::Warning('This project may not have initialized. To setup a project at this location, run the command \'create-phar init\'');
    Output::Error('Exiting... cannot find configuration file at path '.$configIniPath);
}
Output::PrintLine('Project directory: '.$projectDirectory);
Output::PrintLine('Project configuration: '.$configIniPath);
Output::PrintLine('Build script directory: '.__DIR__);



$ini = parse_ini_file($configIniPath,true);
$project = $ini['project']['name'];
$excludeDirectories = explode(",", $ini['project']['exclude_directories']);
$manualCopies = explode(",", $ini['project']['manual_copies']);
$manualCopyFiles = explode(",", $ini['project']['manual_copy_files']);

$useDeprecatedVendors = false;
if (array_key_exists('vendor_includes', $ini['project'])) {
    Output::printLightRed("Your configuration file is using vendor_includes, which has been deprecated in version 1.2.0 and will be removed moving forward. Please move to using vendor_excludes instead.\n\n");
    $useDeprecatedVendors = true;
}

$vendorIncludes = array();
$vendorExcludes = array();

if ($useDeprecatedVendors) {
    foreach (explode(',', $ini['project']['vendor_includes']) as $viItem) {
        if (!empty($viItem)) {
            $vendorIncludes[] = $viItem;
            print("Vendor directory $viItem will be included.\n");
        }
    }
    print "\n";
} else {
    $vendorExcludesString = $ini['project']['vendor_excludes'];
    foreach (explode(',', $vendorExcludesString) as $veItem) {
        if (!empty($veItem)) {
            $vendorExcludes[] = $veItem;
            print("Vendor directory $veItem will be excluded.\n");
        }
    }
}
print "\n";


$composerPath = __DIR__.'\\composer.phar';
$composerJsonPath = $projectDirectory.'\\src\\php\\';

if (!file_exists($composerPath)) {
    Output::dieMsg("\tCannot find composer.phar file at ".$composerPath);
} elseif (!file_exists($composerJsonPath.'composer.json')) {
    Output::dieMsg("\tCannot find composer.json file at ".$composerJsonPath."composer.json");
}
$composerConfig = json_decode(file_get_contents($composerJsonPath.'composer.json'),true);

Output::printLightGreen("Upgrading Composer if available:\n");
echo shell_exec('php '.$composerPath.' --working-dir='.$composerJsonPath.' self-update');

// Get composer version
$output = shell_exec('php '.$composerPath.' --working-dir='.$composerJsonPath.' -V');
$output = substr($output, strlen('Composer version '));
$composerVersion = substr($output, 0, strpos($output, ' '));
echo "Currently using version ".$composerVersion." of Composer.\n";
print "\n";

Output::printLightGreen("Upgrading and installing from Composer:\n");
echo shell_exec('php '.$composerPath.' --working-dir='.$composerJsonPath.' u');
echo shell_exec('php '.$composerPath.' --working-dir='.$composerJsonPath.' i');
print "\n";



/**
 * Setup Composer autloads for this project
 */
Output::printLightGreen("Setting up project to support autoload through Composer:\n");
if (!isset($composerConfig['autoload']['psr-4'])) {
    Output::dieMsg('You must have setup autoload/psr-4 section of your composer.json to support autoloading. Please fix before continuing.');
}
echo shell_exec('php '.$composerPath.' --working-dir='.$composerJsonPath.' dump-autoload -o');
$namespace = array_keys($composerConfig['autoload']['psr-4'])[0];




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

function writeToFile($file, $content) {
    if (!file_exists($file)) {
        Output::dieMsg('File '.$file.' does not exist. Please create an empty file there first.');
    }
    file_put_contents($file, $content);
}

function setNewVersion($v) {
    global $_createPharVersion, $namespace, $projectDirectory;
    $buildString = "Built with create-phar ($_createPharVersion) on ". date("D M d, Y G:i");
    writeToFile($projectDirectory.'/version.txt', $v);
    writeToFile($projectDirectory.'/src/php/Config/Version.php', "<?php /* Auto-generated from create-phar.php - Do not edit */ namespace ".$namespace."Config; class Version { public static function getBuildInfo() { return '$buildString'; } public static function getVersion() { return '$v'; } }");
}

function getNewVersion($argv, $composerVersion) {

    if ($argv !== null && is_array($argv) && count($argv) > 1) {
        $v = $argv[1];
        if ($v == 'init') {
            $v = '0.0.1.0';
        }
        Output::printLightCyan("Using $v as new version number\n");
    } else {

        $v = getCurrentVersion();
        if ($v === null) {
            $v = "1.0.0";
            Output::printLightCyan("Unable to determine current version. Using $v\n");
        } else {
            Output::printLightCyan("Current version is $v\n");
        }

    }

    if (substr_count($v, ".") > 2) {
        $e = explode(".",$v);
        $v = $e[0].".".$e[1].".".$e[2];
    }

    $v .= ".".time();

    $v .= "-composer".str_replace('.','_', $composerVersion);

    Output::printLightCyan("New version being set to $v\n");

    return $v;
}


function custom_copy($src, $dst) {

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
                copy($src . '/' . $file, $dst . '/' . $file);
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

Output::printLightGreen("\nFinalizing Output:\n");
$v = getNewVersion($argv, $composerVersion);
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
    echo 'file delete error';
}





if ($doPhar) {

    echo "Building PHAR file...\n";

    $phar = new Phar(
        $fullPath,
        0,
        $project . ".phar"
    );


    // Iterate through and create

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS)
    );

    $filterIterator = new CallbackFilterIterator($iterator, function ($file) {

        global $excludeDirectories, $vendorIncludes, $vendorExcludes, $useDeprecatedVendors, $verbose;

        $lcFile = strtolower($file);
        $dispFile = $lcFile;
        $maxDispLength = 40;
        if (strlen($dispFile) > ($maxDispLength + 3)) {
            $dispFile = "..." . substr($dispFile, -$maxDispLength);
        }

        foreach ($excludeDirectories as $exDir) {

            $exDir = strtolower($exDir);

            if (strPos($lcFile, "/$exDir/") !== false) {
                if ($verbose) {
                    echo "Exclusion: Match on /$exDir/ to $dispFile\n";
                }
                return false;
            } elseif (strPos($lcFile, "\\$exDir\\") !== false) {
                if ($verbose) {
                    echo "Exclusion: Match on \\$exDir\\ to $dispFile\n";
                }
                return false;
            }
        }

        if (strpos($lcFile, 'vendor') !== false) {

            $inc = !$useDeprecatedVendors;
            $output = array();

            if ($useDeprecatedVendors) {
                foreach ($vendorIncludes as $vendorInclude) {
                    if (strpos($lcFile, 'vendor\\' . $vendorInclude) !== false || strpos($lcFile, 'vendor\\autoload.php') !== false) {
                        $inc = true;
                        break;
                    }
                }
            } else {
                foreach ($vendorExcludes as $vendorExclude) {
                    if (strpos($lcFile, 'vendor\\' . $vendorExclude) !== false) {
                        $inc = false;
                        break;
                    }
                }
            }

            if (!$inc) {
                if ($verbose) {
                    echo 'Vendor exclusion: ' . $dispFile . "\n";
                }
                return false;
            }

        }


        //echo "Including: $dispFile\n";
        return true;

    });

    $phar->buildFromIterator($filterIterator, $srcRoot);




    $phar->setStub($phar->createDefaultStub("index.php"));

    copy($srcRoot . "/config.ini", $buildRoot . "/" . $project . ".config.ini");

    Output::printLightCyan("PHAR file created as " . $buildRoot . "/" . $project . ".phar");
    echo "\n";

    copy($buildRoot . "/" . $project . ".phar", $buildRoot . "/" . $project . ".ext");
    Output::printLightCyan("PHAR file copied as .ext");

    echo "\n";

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

    echo "Copying specified files over manually:\n";

    $dir = new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS);

    foreach ($dir as $fileinfo) {
        $lcfile = strtolower($fileinfo->getFilename());


        $found = false;

        if (!$found) {
            //$include = false;
            foreach ($manualCopies as $manualDir) {

                $manualDir = strtolower($manualDir);
                if (strPos($lcfile, $manualDir) !== false) {
                    echo "\t$lcfile to $copyRoot/$lcfile\n";

                    //copy_r($srcRoot."/".$lcfile, $copyRoot."/".$lcfile);

                    $files = scandir($srcRoot . DIRECTORY_SEPARATOR . $lcfile);
                    foreach ($files as $file) {
                        if( $file == "." || $file == ".." ) {
                            continue;
                        }
                        if ($verbose) {
                            echo "\t\t$file\n";
                        }
                        //echo "\t\t\t".$srcRoot."/".$file, $copyRoot."/".$lcfile."\n";

                        $copyDir = $copyRoot.DIRECTORY_SEPARATOR.$lcfile;
                        if (!file_exists($copyDir)) {
                            echo "Creating $copyDir...\n";
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

    // Copy version file in
    copy($projectDirectory."/version.txt", $copyRoot.DIRECTORY_SEPARATOR."version.txt");

}

echo "\n";
Output::printLightGreen("PHAR creation process completed!\n\n");