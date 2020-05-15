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
run the command _create-phar init_.

The project will be named and namespaced based on the current directory. For 
example, if you run _create-phar init_ in the directory _c:\\TestProject\\_, 
your project will have the name _estproject_ and be in the namespace 
_rei\TestProject_.

## Project Build

To build projects after initialization, simply run the command _create-phar_.

If you want to change the version number for the project, run the command
followed by the new version number. For example _create-phar 1.2.4_