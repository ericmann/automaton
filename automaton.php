#!/usr/bin/env php
<?php
namespace EAMann\Automaton;

require __DIR__.'/vendor/autoload.php';

use EAMann\Automaton\Slack\StatusCommand;
use Symfony\Component\Console\Application;

$application = new Application('Automaton', '1.0.0');
$application->add(new StatusCommand());

$application->run();