<?php

include 'autoload.php';
include 'autoloadEntity.php';

$composerAutoloaderPath = Config::BASE_PATH . "lib/core/vendor/autoload.php";
if (file_exists($composerAutoloaderPath)) {
	require $composerAutoloaderPath;
}
