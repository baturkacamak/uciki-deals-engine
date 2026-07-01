<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 23:33
 */

use PHPUnit\Framework\TestCase;

class UcikiDealsGetTest extends TestCase
{
	public function testUcikiDealsGetReturnsExpectedValueForExistingKey()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c' => 'test_value',
				],
			],
		];

		// Call uciki_deals_get with an existing key
		$output = uciki_deals_get('a/b/c', $input_array);

		// Assert that the function returns the expected output
		$this->assertEquals('test_value', $output);
	}

	public function testUcikiDealsGetReturnsDefaultValueForNonexistentKey()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c' => 'test_value',
				],
			],
		];

		// Call uciki_deals_get with a nonexistent key
		$output = uciki_deals_get('a/b/d', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testUcikiDealsGetReturnsDefaultValueForEmptyArray()
	{
		// Define an empty input array
		$input_array = [];

		// Call uciki_deals_get with a key
		$output = uciki_deals_get('a/b/c', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testUcikiDealsGetReturnsExpectedValueForNumericIndex()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c',
					'd',
					'e',
				],
			],
		];

		// Call uciki_deals_get with a numeric index
		$output = uciki_deals_get('a/b/0', $input_array);

		// Assert that the function returns the expected output
		$this->assertEquals('c', $output);
	}

	public function testUcikiDealsGetReturnsExpectedValueForEmptyPath()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => 'test_value',
			],
		];

		// Call uciki_deals_get with an empty path
		$output = uciki_deals_get('', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testUcikiDealsGetReturnsExpectedValueForNullDefault()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => 'test_value',
			],
		];

		// Call uciki_deals_get with a nonexistent key and a null default value
		$output = uciki_deals_get('a/b/c', $input_array, null);

		// Assert that the function returns null
		$this->assertNull($output);
	}

}
