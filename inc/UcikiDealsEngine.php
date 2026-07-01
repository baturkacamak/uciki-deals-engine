<?php

namespace UcikiDealsEngine;

use UcikiDealsEngine\Core\Module\Initializer\ModuleInitializer;
use UcikiDealsEngine\Core\Module\Registry\ModuleRegistrar;
use UcikiDealsEngine\Core\ModulesManager;
use UcikiDealsEngine\Core\Settings\SettingsRepository;
use GuzzleHttp\Client;

if (!class_exists('UcikiDealsEngine\UcikiDealsEngine')) {
	/**
	 * Class UcikiDealsEngine
	 *
	 * @package         UcikiDealsEngine
	 */
	class UcikiDealsEngine
	{

		/**
		 * The one true instance.
		 */
		private static $instance;
		/**
		 * @var Client
		 */
		public $guzzle;
		/**
		 * @var array Settings
		 */
		public $settings;
		/**
		 * @var
		 */
		private $pluginSettings;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->settings = (new SettingsRepository())->getAll();

			return self::$instance = $this;
		}

		/**
		 * Get singleton instance.
		 *
		 * @since 1.5
		 */
		public static function getInstance()
		{
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 *
		 */
		public function init()
		{
			$module_registrar = new ModuleRegistrar();
			$module_initializer = new ModuleInitializer();
			new ModulesManager($module_registrar, $module_initializer);
		}
	}
}
