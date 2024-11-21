<?php

namespace Barkley\CreatePhar\GitHub;
//require_once __DIR__.'/../vendor/autoload.php';
class Repository {

    private \Github\Client $_client;

	private string $_user = 'BarkleyREI';
	private string $_project = 'create-phar';

    public function __construct(?string $user = null, ?string $project = null) {

		if (!empty($user)) { $this->_user = $user; }
		if (!empty($project)) { $this->_project = $project; }

        $this->_client = new \Github\Client();
    }

    public function GetLatestReleaseVersion() : string {
        $latest = $this->_client->api('repo')->releases()->latest($this->_user, $this->_project);
        return $latest['name'];
    }

	public function GetGithubPagesUrl() : string {
		return 'https://'.strtolower($this->_user).'.github.io/'.$this->_project;
	}

    public function GetReleasesUrl() : string {
        return 'https://github.com/'.$this->_user.'/'.$this->_project.'/releases';
    }

}
