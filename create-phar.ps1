$scriptDir = Split-Path -Path $MyInvocation.MyCommand.Definition -Parent
Invoke-Expression "php $scriptDir\build.php $args"