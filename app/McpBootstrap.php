<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

/**
 * Bootstrap for MCP Inspector with local development settings.
 */
class McpBootstrap
{
	public static function boot(): Configurator
	{
		$rootDir = dirname(__DIR__);
		$configurator = new Configurator;
		
		// Set temp directory
		$configurator->setTempDirectory($rootDir . '/temp');
		
		// Disable debug mode for MCP
		$configurator->setDebugMode(false);
		
		// Enable Tracy
		$configurator->enableTracy($rootDir . '/log');
		
		// Load configuration
		$configDir = $rootDir . '/config';
		$configurator->addConfig($configDir . '/common.neon');
		$configurator->addConfig($configDir . '/services.neon');
		
		// Load local config if exists (for local development)
		$localConfig = $configDir . '/local.neon';
		if (file_exists($localConfig)) {
			$configurator->addConfig($localConfig);
		}
		
		return $configurator;
	}
}
