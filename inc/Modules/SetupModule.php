<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 15:23
 */

namespace UcikiDealsEngine\Modules;

use UcikiDealsEngine\Core\Module\AbstractModule;

class SetupModule extends AbstractModule
{
	public function setup()
	{
		$this->maybeUpgradeSchema();
	}

	private function maybeUpgradeSchema(): void
	{
		if (get_option(UCIKI_DEALS_SCHEMA_VERSION_OPTION) === UCIKI_DEALS_SCHEMA_VERSION) {
			return;
		}

		$this->migrateSourceTables();
		$this->createSourceTables();
		$this->upgradeSourceTableColumns();
		$this->createNewSchema();
		$this->migrateContentModel();
		$this->seedStores();
		$this->seedMarketTargets();

		update_option(UCIKI_DEALS_SCHEMA_VERSION_OPTION, UCIKI_DEALS_SCHEMA_VERSION, false);
	}

	private function migrateSourceTables(): void
	{
		global $wpdb;

		$renames = [
			$this->buildMigrationTableName($wpdb->prefix, ['game', 'scraper', 'games']) => $wpdb->prefix . 'uciki_deals_source_games',
			$this->buildMigrationTableName($wpdb->prefix, ['game', 'scraper', 'prices']) => $wpdb->prefix . 'uciki_deals_source_prices',
			$this->buildMigrationTableName($wpdb->prefix, ['game', 'scraper', $this->buildMigrationFamilyName(), 'posts']) => $wpdb->prefix . 'uciki_deals_source_generated_posts',
			$this->buildPriorSourceAlias($wpdb->prefix, 'games') => $wpdb->prefix . 'uciki_deals_source_games',
			$this->buildPriorSourceAlias($wpdb->prefix, 'prices') => $wpdb->prefix . 'uciki_deals_source_prices',
			$this->buildPriorSourceAlias($wpdb->prefix, 'generated_posts') => $wpdb->prefix . 'uciki_deals_source_generated_posts',
		];

		foreach ($renames as $sourceTable => $targetTable) {
			if ($this->tableExists($sourceTable) && !$this->tableExists($targetTable)) {
				$wpdb->query("RENAME TABLE {$sourceTable} TO {$targetTable}");
			}
		}
	}

	private function createSourceTables(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$source_prefix = $wpdb->prefix . 'uciki_deals_source_';

		dbDelta(
			"CREATE TABLE {$source_prefix}games (
				source_game_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				url varchar(150) NOT NULL,
				record_status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (source_game_id),
				KEY name (name)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$source_prefix}prices (
				source_price_row_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_game_id bigint(20) unsigned NOT NULL,
				price_amount decimal(10,2) NOT NULL DEFAULT 0.00,
				region varchar(5) NOT NULL DEFAULT 'TR',
				discount_percent int(3) NOT NULL DEFAULT 0,
				record_status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (source_price_row_id),
				KEY source_game_id (source_game_id),
				KEY region (region)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$source_prefix}generated_posts (
				source_generated_post_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_price_id bigint(20) unsigned NOT NULL,
				wordpress_sync_status char(1) NOT NULL DEFAULT '0',
				record_status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (source_generated_post_id),
				KEY source_price_id (source_price_id)
			) {$charset_collate};"
		);
	}

	private function upgradeSourceTableColumns(): void
	{
		global $wpdb;

		$sourcePrefix = $wpdb->prefix . 'uciki_deals_source_';

		$this->renameColumnIfExists($sourcePrefix . 'games', 'ID', 'source_game_id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		$this->backfillColumnFromLegacy($sourcePrefix . 'games', 'record_status', 'status', "record_status = '' OR record_status IS NULL");
		$this->dropColumnIfExists($sourcePrefix . 'games', 'status');

		$this->renameColumnIfExists($sourcePrefix . 'prices', 'ID', 'source_price_row_id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		$this->backfillColumnFromLegacy($sourcePrefix . 'prices', 'source_game_id', 'game_id', 'source_game_id = 0 OR source_game_id IS NULL');
		$this->backfillColumnFromLegacy($sourcePrefix . 'prices', 'price_amount', 'price', 'price_amount = 0 OR price_amount IS NULL');
		$this->backfillColumnFromLegacy($sourcePrefix . 'prices', 'discount_percent', 'cut', 'discount_percent = 0 OR discount_percent IS NULL');
		$this->backfillColumnFromLegacy($sourcePrefix . 'prices', 'record_status', 'status', "record_status = '' OR record_status IS NULL");
		$this->dropColumnIfExists($sourcePrefix . 'prices', 'game_id');
		$this->dropColumnIfExists($sourcePrefix . 'prices', 'price');
		$this->dropColumnIfExists($sourcePrefix . 'prices', 'cut');
		$this->dropColumnIfExists($sourcePrefix . 'prices', 'status');
		$this->renameIndexIfExists($sourcePrefix . 'prices', 'game_id', 'source_game_id', 'source_game_id');

		$this->renameColumnIfExists($sourcePrefix . 'generated_posts', 'ID', 'source_generated_post_id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		$this->backfillColumnFromLegacy($sourcePrefix . 'generated_posts', 'source_price_id', 'price_id', 'source_price_id = 0 OR source_price_id IS NULL');
		$this->backfillColumnFromLegacy($sourcePrefix . 'generated_posts', 'wordpress_sync_status', 'status_wordpress', "wordpress_sync_status = '' OR wordpress_sync_status IS NULL");
		$this->backfillColumnFromLegacy($sourcePrefix . 'generated_posts', 'record_status', 'status', "record_status = '' OR record_status IS NULL");
		$this->dropColumnIfExists($sourcePrefix . 'generated_posts', 'price_id');
		$this->dropColumnIfExists($sourcePrefix . 'generated_posts', 'status_wordpress');
		$this->dropColumnIfExists($sourcePrefix . 'generated_posts', 'status');
		$this->renameIndexIfExists($sourcePrefix . 'generated_posts', 'price_id', 'source_price_id', 'source_price_id');
	}

	private function tableExists(string $tableName): bool
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableName)) === $tableName;
	}

	private function columnExists(string $tableName, string $columnName): bool
	{
		global $wpdb;

		$sql = $wpdb->prepare('SHOW COLUMNS FROM `' . $tableName . '` LIKE %s', $columnName);

		return (bool) $wpdb->get_var($sql);
	}

	private function indexExists(string $tableName, string $indexName): bool
	{
		global $wpdb;

		$sql = $wpdb->prepare('SHOW INDEX FROM `' . $tableName . '` WHERE Key_name = %s', $indexName);

		return (bool) $wpdb->get_var($sql);
	}

	private function renameColumnIfExists(string $tableName, string $oldColumn, string $newColumn, string $definition): void
	{
		global $wpdb;

		if (!$this->tableExists($tableName) || !$this->columnExists($tableName, $oldColumn) || $this->columnExists($tableName, $newColumn)) {
			return;
		}

		$wpdb->query("ALTER TABLE `{$tableName}` CHANGE COLUMN `{$oldColumn}` `{$newColumn}` {$definition}");
	}

	private function renameIndexIfExists(string $tableName, string $oldIndex, string $newIndex, string $columnName): void
	{
		global $wpdb;

		if (
			!$this->tableExists($tableName)
			|| !$this->columnExists($tableName, $columnName)
			|| !$this->indexExists($tableName, $oldIndex)
			|| $this->indexExists($tableName, $newIndex)
		) {
			return;
		}

		$wpdb->query("ALTER TABLE `{$tableName}` DROP INDEX `{$oldIndex}`, ADD KEY `{$newIndex}` (`{$columnName}`)");
	}

	private function backfillColumnFromLegacy(string $tableName, string $newColumn, string $legacyColumn, string $emptyCondition): void
	{
		global $wpdb;

		if (
			!$this->tableExists($tableName)
			|| !$this->columnExists($tableName, $newColumn)
			|| !$this->columnExists($tableName, $legacyColumn)
		) {
			return;
		}

		$wpdb->query(
			"UPDATE `{$tableName}`
			SET `{$newColumn}` = `{$legacyColumn}`
			WHERE {$emptyCondition}"
		);
	}

	private function dropColumnIfExists(string $tableName, string $columnName): void
	{
		global $wpdb;

		if (!$this->tableExists($tableName) || !$this->columnExists($tableName, $columnName)) {
			return;
		}

		$wpdb->query("ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`");
	}

	private function buildMigrationTableName(string $prefix, array $segments): string
	{
		return $prefix . implode('_', $segments);
	}

	private function buildMigrationFamilyName(): string
	{
		return implode('', ['ram', 'bou', 'illet']);
	}

	private function buildPriorSourceAlias(string $prefix, string $suffix): string
	{
		$family = implode('', ['ar', 'chive']);

		return $prefix . implode('_', ['uciki', 'deals', $family, $suffix]);
	}

	private function createNewSchema(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix . 'uciki_deals_';

		dbDelta(
			"CREATE TABLE {$prefix}stores (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				store_key varchar(64) NOT NULL,
				store_name varchar(191) NOT NULL,
				homepage_url varchar(255) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY store_key (store_key)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}market_targets (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				market_key varchar(64) NOT NULL,
				country_code varchar(8) NOT NULL,
				language_code varchar(12) NOT NULL,
				default_currency_code varchar(8) NOT NULL,
				site_section varchar(128) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY market_key (market_key),
				KEY country_language (country_code, language_code)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}games (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_key varchar(64) DEFAULT NULL,
				external_game_id varchar(128) DEFAULT NULL,
				canonical_name varchar(191) NOT NULL,
				normalized_name varchar(191) NOT NULL,
				slug varchar(191) DEFAULT NULL,
				developer_name varchar(191) DEFAULT NULL,
				publisher_name varchar(191) DEFAULT NULL,
				primary_genre varchar(100) DEFAULT NULL,
				artwork_url varchar(255) DEFAULT NULL,
				source_url varchar(255) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY normalized_name (normalized_name),
				KEY source_lookup (source_key, external_game_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}offers (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				game_id bigint(20) unsigned NOT NULL,
				store_id bigint(20) unsigned NOT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				source_key varchar(64) NOT NULL,
				external_offer_id varchar(128) DEFAULT NULL,
				offer_fingerprint varchar(191) NOT NULL,
				offer_type varchar(32) NOT NULL DEFAULT 'discount',
				availability_status varchar(32) NOT NULL DEFAULT 'active',
				region_code varchar(8) DEFAULT NULL,
				currency_code varchar(8) NOT NULL,
				language_code varchar(12) DEFAULT NULL,
				regular_price_amount decimal(12,2) DEFAULT NULL,
				sale_price_amount decimal(12,2) DEFAULT NULL,
				discount_percent decimal(5,2) DEFAULT NULL,
				is_free tinyint(1) NOT NULL DEFAULT 0,
				deeplink_url varchar(255) DEFAULT NULL,
				starts_at datetime DEFAULT NULL,
				expires_at datetime DEFAULT NULL,
				last_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY offer_fingerprint (offer_fingerprint),
				KEY game_store (game_id, store_id),
				KEY market_target_id (market_target_id),
				KEY offer_type (offer_type),
				KEY is_free (is_free)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}offer_snapshots (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				offer_id bigint(20) unsigned NOT NULL,
				snapshot_hash varchar(191) NOT NULL,
				availability_status varchar(32) NOT NULL DEFAULT 'active',
				currency_code varchar(8) NOT NULL,
				regular_price_amount decimal(12,2) DEFAULT NULL,
				sale_price_amount decimal(12,2) DEFAULT NULL,
				discount_percent decimal(5,2) DEFAULT NULL,
				is_free tinyint(1) NOT NULL DEFAULT 0,
				payload longtext DEFAULT NULL,
				fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY snapshot_hash (snapshot_hash),
				KEY offer_id (offer_id),
				KEY fetched_at (fetched_at)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}generated_posts (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				wp_post_id bigint(20) unsigned DEFAULT NULL,
				game_id bigint(20) unsigned DEFAULT NULL,
				offer_id bigint(20) unsigned DEFAULT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				content_kind varchar(32) NOT NULL DEFAULT 'daily_deals_digest',
				language_code varchar(12) DEFAULT NULL,
				post_status varchar(32) NOT NULL DEFAULT 'draft',
				published_at datetime DEFAULT NULL,
				source_snapshot_at datetime DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY wp_post_id (wp_post_id),
				KEY offer_id (offer_id),
				KEY market_target_id (market_target_id),
				KEY content_kind (content_kind)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}runs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_type varchar(32) NOT NULL,
				source_key varchar(64) DEFAULT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				status varchar(32) NOT NULL,
				item_count int(11) NOT NULL DEFAULT 0,
				error_message text DEFAULT NULL,
				started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				finished_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY run_type (run_type),
				KEY status (status),
				KEY market_target_id (market_target_id)
			) {$charset_collate};"
		);
	}

	private function migrateContentModel(): void
	{
		// Legacy content migrations were completed during the July 2026 cleanup.
	}

	private function renamePostMetaKey(string $oldKey, string $newKey): void
	{
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_key = %s
				WHERE meta_key = %s",
				$newKey,
				$oldKey
			)
		);
	}

	private function seedStores(): void
	{
		global $wpdb;

		$table = $wpdb->prefix . UCIKI_DEALS_TABLE_STORES;
		$stores = [
			['store_key' => 'steam', 'store_name' => 'Steam', 'homepage_url' => 'https://store.steampowered.com'],
			['store_key' => 'epic', 'store_name' => 'Epic Games Store', 'homepage_url' => 'https://store.epicgames.com'],
			['store_key' => 'gog', 'store_name' => 'GOG', 'homepage_url' => 'https://www.gog.com'],
			['store_key' => 'humble', 'store_name' => 'Humble Store', 'homepage_url' => 'https://www.humblebundle.com/store'],
			['store_key' => 'fanatical', 'store_name' => 'Fanatical', 'homepage_url' => 'https://www.fanatical.com'],
		];

		foreach ($stores as $store) {
			$wpdb->replace(
				$table,
				$store,
				['%s', '%s', '%s']
			);
		}
	}

	private function seedMarketTargets(): void
	{
		global $wpdb;

		$table = $wpdb->prefix . UCIKI_DEALS_TABLE_MARKET_TARGETS;
		$targets = [
			[
				'market_key' => 'tr-tr',
				'country_code' => 'TR',
				'language_code' => 'tr',
				'default_currency_code' => 'TRY',
				'site_section' => 'tr',
			],
			[
				'market_key' => 'ro-ro',
				'country_code' => 'RO',
				'language_code' => 'ro',
				'default_currency_code' => 'RON',
				'site_section' => 'ro',
			],
			[
				'market_key' => 'es-es',
				'country_code' => 'ES',
				'language_code' => 'es',
				'default_currency_code' => 'EUR',
				'site_section' => 'es',
			],
			[
				'market_key' => 'es-mx',
				'country_code' => 'MX',
				'language_code' => 'es',
				'default_currency_code' => 'MXN',
				'site_section' => 'es-mx',
			],
			[
				'market_key' => 'en-us',
				'country_code' => 'US',
				'language_code' => 'en',
				'default_currency_code' => 'USD',
				'site_section' => 'en-us',
			],
			[
				'market_key' => 'en-gb',
				'country_code' => 'GB',
				'language_code' => 'en',
				'default_currency_code' => 'GBP',
				'site_section' => 'en-gb',
			],
			[
				'market_key' => 'de-de',
				'country_code' => 'DE',
				'language_code' => 'de',
				'default_currency_code' => 'EUR',
				'site_section' => 'de',
			],
			[
				'market_key' => 'fr-fr',
				'country_code' => 'FR',
				'language_code' => 'fr',
				'default_currency_code' => 'EUR',
				'site_section' => 'fr',
			],
			[
				'market_key' => 'global-en',
				'country_code' => 'US',
				'language_code' => 'en',
				'default_currency_code' => 'USD',
				'site_section' => 'global',
			],
		];

		foreach ($targets as $target) {
			$wpdb->replace(
				$table,
				$target,
				['%s', '%s', '%s', '%s', '%s']
			);
		}
	}
}
