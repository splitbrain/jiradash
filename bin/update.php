#!/usr/bin/php
<?php
require __DIR__ . '/../vendor/autoload.php';

use splitbrain\JiraDash\CLI\Update;


$cli = new Update();
$cli->run();
