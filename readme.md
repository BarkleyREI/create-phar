# create-phar

## Overview

This project will aid in creating PHP projects that following Barkley's typical development flow, and
will allow the creation of versioned PHAR files for projects to be easily distributed to systems 
that are detached from any code repository. 

> _Barkley's PHP development process is ever-changing, so updates and changes to this project will likely shift around frequently as well. However, backwards-compatibility will always be important as we move forward. We will intend to support the ability to disable any new features that get added._

## Setup

### Composer

> _Composer support has been introduced in v2.0.0, and is relatively fresh, so proceed with caution. This will be supported on Linux._

Install the Composer package `barkley\create-phar` ([see Packagist](https://packagist.org/packages/barkley/create-phar)).
Once installed, you should be able to run `php vendor/barkley/create-phar/build.php` from the root of your project. (_Anytime instructions below refer to the command `create-phar`, you will need to use this path instead)_

### Windows

Add the directory for the create-phar project to your system's PATH
variable. If done correctly you should see output when you type `create-phar`
in your system's terminal.

## Project Initialization

To initialize a new project, create an empty directory for it, and within it
run the command `create-phar init`.

The project will be named and namespaced based on the current directory. For 
example, if you run `create-phar init` in the directory _c:\\TestProject\\_, 
your project will have the name _Testproject_ and be in the namespace 
_rei\TestProject_.

## Project Build

To build projects after initialization, simply run the command `create-phar` within the project directory.

If you want to change the version number for the project, run the command
followed by the new version number. For example `create-phar 1.2.4`.

## Run Flags

- `-h` - Outputs help commands.
- `-v` - Runs in verbose mode, providing more output during the build process.
- `-u` - Updates Composer and dependencies. By default, running a build does not update these items.
- `-i` - Outputs version information on the current project without doing any build steps.
- `-c` - Run a Composer command against the curent project using the Composer from the create-phar build.
- `-fixpsr` - Fix PSR adjustments. Should only need to be run if prompted to do so.
