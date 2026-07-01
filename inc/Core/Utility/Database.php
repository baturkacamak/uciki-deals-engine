<?php

namespace UcikiDealsEngine\Core\Utility;

use Exception;
use Medoo\Medoo;

/**
 * Class Database
 *
 * This class provides a convenient interface for the Medoo database library.
 *
 * @package         UcikiDealsEngine\Core\Utility
 */
class Database extends Medoo
{
	/**
	 * @var Database A static instance of the Database class.
	 */
	private static Database $instance;

	/**
	 * Database constructor.
	 *
	 * Creates a new instance of the Medoo library using the WordPress database configuration.
	 *
	 * @throws Exception If an error occurs while connecting to the database.
	 */
	private function __construct()
	{
		global $wpdb;

		$data = [
			'database_type' => 'mysql',
			'database_name' => DB_NAME,
			'server'        => DB_HOST,
			'username'      => DB_USER,
			'password'      => DB_PASSWORD,
			'charset'       => 'utf8mb4',
			'collation'     => 'utf8mb4_unicode_ci',
			'prefix'        => $wpdb->prefix . 'uciki_deals_source_',
			'command'       => [
				'SET SQL_MODE=ANSI_QUOTES',
			],
		];

		parent::__construct($data);
	}

	/**
	 * Returns the single instance of the Database class.
	 *
	 * @return Database
	 * @throws Exception
	 */
	public static function getInstance(): self
	{
		static $database = null; // cache

		if (null === $database) {
			$database = new self();
		}

		return $database;
	}

	/**
	 * Inserts a new record if it does not exist, otherwise returns the existing record.
	 *
	 * @param string $tableName The name of the table to insert the data into.
	 * @param array $data The data to insert into the table.
	 *
	 * @return array The inserted or existing record.
	 * @throws Exception
	 */
	public function insertOrGet(string $tableName, array $data): array
	{
		$existing_record = self::getInstance()->get($tableName, '*', $data);
		if (!$existing_record) {
			self::getInstance()->insert($tableName, $data);
			$existing_record = array_merge([$this->resolvePrimaryKeyName($tableName) => self::getInstance()->id()], $data);
		}

		return $existing_record;
	}

	/**
	 * Inserts a new record or updates an existing one.
	 *
	 * @param string $tableName The name of the table to insert or update the data in.
	 * @param array $data The data to insert or update in the table.
	 * @param array $where The condition for updating an existing record.
	 *
	 * @return bool|int The ID of the inserted record, or false if an existing record was updated.
	 */
	public function insertOrUpdate(string $tableName, array $data, array $where)
	{
		$where['LIMIT']  = 1;
		$existing_record = self::getInstance()->get($tableName, '*', $where);
		if ($existing_record) {
			$primaryKey = $this->resolvePrimaryKeyName($tableName);
			$recordId = (int) ($existing_record[$primaryKey] ?? $existing_record['id'] ?? $existing_record['ID'] ?? 0);
			if ($recordId > 0) {
				self::getInstance()->update($tableName, $data, [$primaryKey => $recordId]);
			}

			return false;
		}

		self::getInstance()->insert($tableName, $data);

		return self::getInstance()->id();
	}

	private function resolvePrimaryKeyName(string $tableName): string
	{
		return match ($tableName) {
			'games' => 'source_game_id',
			'prices' => 'source_price_row_id',
			'generated_posts' => 'source_generated_post_id',
			default => 'id',
		};
	}
}
