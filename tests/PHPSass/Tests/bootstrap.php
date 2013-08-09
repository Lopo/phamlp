<?php
namespace PHPSass\Tests;

// take care of autoloading
// require class loader
/** @var $loader \Composer\Autoload\ClassLoader $loader */
if (file_exists(__DIR__.'/../../../vendor/autoload.php')) {
	// dependencies were installed via composer - this is the main project
	$loader=require __DIR__.'/../../../vendor/autoload.php';
	}
elseif (file_exists(__DIR__.'/../../../../../autoload.php')) {
	// installed as a dependency in `vendor`
	$loader=require __DIR__.'/../../../../../autoload.php';
	}
else {
	throw new \Exception('Can\'t find autoload.php. Did you install dependencies via composer?');
	}

$loader->add('PHPSass\\Tests', __DIR__);

unset($loader); // cleanup
