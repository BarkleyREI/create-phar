<?php

//require_once __DIR__.'/../vendor/autoload.php';
class Repository {

    private \Github\Client $_client;

    public function __construct() {
        $this->_client = new \Github\Client();
    }

    public function GetLatestReleaseVersion() : string {
        $latest = $this->_client->api('repo')->releases()->latest('BarkleyREI','create-phar');
        return $latest['name'];
    }

    public function GetReleasesUrl() : string {
        return 'https://github.com/BarkleyREI/create-phar/releases';
    }

}
