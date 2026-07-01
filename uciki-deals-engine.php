<?php

/**
 * Plugin Name:     Uciki Deals Engine
 * Plugin URI:      https://github.com/baturkacamak/uciki-deals-engine
 * Description:     A WordPress plugin that allows you to create game discount posts automatically by scraping data from isthereanydeal.com. The plugin fetches the latest game deals and creates posts on your WordPress site, making it easier to keep your site updated with the latest game discounts.
 * Author:          Batur Kacamak
 * Author URI: 		https://batur.info
 * Text Domain:     uciki-deals-engine
 * Domain Path:     /languages
 * Version:	       	1.1.0
 * License:         GPL-3.0+
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package         UcikiDealsEngine
 */

use UcikiDealsEngine\UcikiDealsEngine;

include_once __DIR__ . '/globals/constants.php';
include_once __DIR__ . '/globals/functions.php';

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'UcikiDealsEngine\\';
		if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
			return;
		}

		$relative_class = substr($class, strlen($prefix));
		$relative_path = str_replace('\\', '/', $relative_class) . '.php';
		$file = UCIKI_DEALS_PLUGIN_DIR . '/inc/' . $relative_path;

		if (file_exists($file)) {
			require_once $file;
		}
	}
);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if (!function_exists('uciki_deals_php_version_notice')) {
	/**
	 * Print admin notice regarding having an old version of PHP.
	 *
	 * @since 0.2
	 */
	function uciki_deals_php_version_notice()
	{
		ob_start();
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
				/* translators: %s: required PHP version */
					esc_html__(
						'The Uciki Deals Engine plugin requires PHP %s. Please contact your host to update your PHP version.',
						'uciki-deals-engine'
					),
					'5.6+'
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	if (version_compare(phpversion(), '5.6', '<')) {
		add_action('admin_notices', 'uciki_deals_php_version_notice');

		return;
	}
}

if (!function_exists('uciki_deals_incorrect_slug_notice')) {
	/**
	 * Print admin notice if plugin installed with incorrect slug (which impacts WordPress's auto-update system).
	 *
	 * @since 0.2
	 */
	function uciki_deals_incorrect_slug_notice()
	{
		$actual_slug = basename(UCIKI_DEALS_PLUGIN_DIR);
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s is the current directory name, and %2$s is the required directory name */
						__(
							'You appear to have installed the Uciki Deals Engine plugin incorrectly.' .
							' It is currently installed in the <code>%1$s</code> directory,' .
							' but it needs to be placed in a directory named <code>%2$s</code>.' .
							' Please rename the directory.' .
							' This is important for WordPress plugin auto-updates.',
							'uciki-deals-engine'
						),
						$actual_slug,
						'uciki-deals-engine'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	if ('uciki-deals-engine' !== basename(UCIKI_DEALS_PLUGIN_DIR)) {
		add_action('admin_notices', 'uciki_deals_incorrect_slug_notice');
	}
}

if (!function_exists('uciki_deals_missing_dependencies_notice')) {
	function uciki_deals_missing_dependencies_notice()
	{
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e('Uciki Deals Engine is active, but its Composer dependencies are missing. Run composer install in the plugin directory before enabling its automation tasks.', 'uciki-deals-engine'); ?>
			</p>
		</div>
		<?php
	}
}

if (!class_exists('Medoo\\Medoo') || !class_exists('GuzzleHttp\\Client')) {
	add_action('admin_notices', 'uciki_deals_missing_dependencies_notice');

	return;
}

UcikiDealsEngine::getInstance()->init();
