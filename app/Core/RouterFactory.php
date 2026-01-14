<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * Creates the main application router with defined routes.
	 */
	public static function createRouter(): RouteList
	{
		$router = new RouteList;
        // Přidáme pravidlo pro /nastaveni → Sign:in
        $router->addRoute('nastaveni', 'Sign:in');
		// Default route that maps to the Dashboard
		$router->addRoute('<presenter>/<action>', 'Home:default');

		return $router;
	}
}
