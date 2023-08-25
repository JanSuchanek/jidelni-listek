<?php

declare(strict_types=1);

namespace App;

use Nette\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;

		// Enable Tracy for error visualisation & logging
		//$configurator->setDebugMode('secret@185.47.222.16'); // enable for your remote IP
		$configurator->setDebugMode(TRUE); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');

		// Enable RobotLoader - this will load all classes automatically
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		// Create Dependency Injection container from config.neon file
		$configurator->addConfig(__DIR__ . '/config/common.neon');

		return $configurator;
	}
}
