<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 19/2/23
 * Time: 16:25
 */

namespace UcikiDealsEngine\Core\Module\Registry;

use UcikiDealsEngine\Core\Module\AbstractModule;

class ModuleRegistrar implements ModuleRegistrarInterface
{

	public const MODULES_PATH = UCIKI_DEALS_PLUGIN_DIR . '/inc/Modules/';

	/**
	 * Registers all the modules in the `Modules` directory and returns an array of module class names.
	 *
	 * @return array An array of module class names.
	 */
	public function registerModules($modulesPath = self::MODULES_PATH): array
	{
		$modules_path = $modulesPath;
		$modules      = scandir($modules_path);

		$registered_modules = [];

		foreach ($modules as $module) {
			if ($module === '.' || $module === '..') {
				continue;
			}

			$module_name = pathinfo($module, PATHINFO_FILENAME);
			$class_name  = "UcikiDealsEngine\\Modules\\{$module_name}";

			if (class_exists($class_name) && is_subclass_of($class_name, AbstractModule::class)) {
				$registered_modules[] = $class_name;
			}
		}

		return $registered_modules;
	}
}
