# create-phar

## Overview

This project's intent is to create the structure needed for new PHP projects,
as well as building those projects into a self-contained PHAR file.

## Setup

### Windows

Add the directory for the create-phar project to your system's PATH
variable. If done correctly you should see output when you type _create-phar_
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

- `-v` - Runs in verbose mode, providing more output during the build process.
- `-u` - Updates Composer and dependencies. By default, running a build does not update these items.