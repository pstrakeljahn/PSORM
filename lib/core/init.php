<?php

use Dotenv\Dotenv;

include 'autoloader/autoload.php';
include 'autoloader/autoloadEntity.php';
include 'autoloader/autoloadRdwObject.php';

$composerAutoloaderPath = Config::BASE_PATH . 'lib/core/vendor/autoload.php';
if (file_exists($composerAutoloaderPath)) {
    require_once $composerAutoloaderPath;
    $dotenv = Dotenv::createImmutable(Config::BASE_PATH);
    $dotenv->load();
}
