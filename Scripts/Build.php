<?php

namespace Barkley\Scripts;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends Command {

    public function Execute(InputInterface $input, OutputInterface $output): int {
        include(__DIR__.'/../build.php');
        return 0;
    }

}