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

		// Redirect ze staré URL na novou SEO-friendly URL
		// Stará URL: /post/show?id=123 → Nová URL: /123-nazev-postu
		$router->addRoute('post/show', [
			'presenter' => 'Post',
			'action' => 'oldUrl',
		]);

		// Nová SEO-friendly routa pro posty - MUSÍ BÝT PŘED obecnou routou!
		// Formát: /123-nazev-postu → Post:show
		$router->addRoute('<id [0-9]+>-<slug>', [
			'presenter' => 'Post',
			'action' => 'show',
		]);

		// Routa pro archiv koncertů
		// Formát: /archiv-koncertu nebo /archiv-koncertu/2 (stránka 2)
		$router->addRoute('archiv-koncertu[/<page [0-9]+>]', [
			'presenter' => 'Home',
			'action' => 'archiv',
			'page' => 1,
		]);

		// Default route that maps to the Dashboard
		$router->addRoute('<presenter>/<action>', 'Home:default');

		return $router;
	}
}
