<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 17/2/23
 * Time: 22:29
 */

use UcikiDealsEngine\Core\Utility\Date;
use UcikiDealsEngine\Core\Utility\GameReviewLookup;
use UcikiDealsEngine\Core\Utility\OfferImageResolver;
use UcikiDealsEngine\Core\Utility\UtilityFactory;
use UcikiDealsEngine\Core\WordPress\WordPressFunctions;
use UcikiDealsEngine\Core\WordPress\WordPressFunctionsInterface;
use UcikiDealsEngine\Post\Strategy\DailyDigestPostStrategy;
use PHPUnit\Framework\TestCase;

class DailyDigestPostStrategyTest extends TestCase
{
	public function testShouldCreatePostReturnsTrueWhenGameDataNotEmptyAndPostDoesNotExist()
	{
		// Arrange
		$gameData          = [['name' => 'Test Game']];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyDigestPostStrategy($gameData, $wpFunctionsMock, $dateMock, $this->createUtilityFactoryMock());
		$postTitle         = date('d') . '  ' . date('Y') . ' Steam İndirimleri';
		$wpFunctionsMock->expects($this->once())
		                ->method('postExists')
		                ->with($postTitle)
		                ->willReturn(0);

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertTrue($shouldCreatePost);
	}

	public function testShouldCreatePostReturnsFalseWhenGameDataIsEmpty()
	{
		// Arrange
		$gameData          = [];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyDigestPostStrategy($gameData, $wpFunctionsMock, $dateMock, $this->createUtilityFactoryMock());

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertFalse($shouldCreatePost);
	}

	public function testShouldCreatePostReturnsFalseWhenPostExists()
	{
		// Arrange
		$gameData          = [['name' => 'Test Game']];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyDigestPostStrategy($gameData, $wpFunctionsMock, $dateMock, $this->createUtilityFactoryMock());
		$postTitle         = date('d') . '  ' . date('Y') . ' Steam İndirimleri';
		$wpFunctionsMock->expects($this->once())
		                ->method('postExists')
		                ->with($postTitle)
		                ->willReturn(1);

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertFalse($shouldCreatePost);
	}

	public function testGetGameDataReturnsGameData()
	{
		// Arrange
		$gameData      = [['name' => 'Test Game']];
		$mockFunctions = $this->createMock(WordPressFunctionsInterface::class);
		$dateMock      = $this->createMock(Date::class);

		$dailyPostStrategy = new DailyDigestPostStrategy($gameData, $mockFunctions, $dateMock, $this->createUtilityFactoryMock());

		// Act
		$returnedGameData = $dailyPostStrategy->getGameData([]);

		// Assert
		$this->assertEquals($gameData, $returnedGameData);
	}

	public function testGetPostContentReturnsString()
	{
		// Arrange
		$gameData = [
			[
				'name'  => 'Test Game',
				'url'   => 'https://store.steampowered.com/app/123456',
				'price' => '$9.99',
				'cut'   => '50%',
			],
		];

		// Create a mock WordPressFunctionsInterface instance
		$wpFunctionsMock = $this->createMock(WordPressFunctionsInterface::class);
		$wpFunctionsMock->method('postExists')->willReturn(false);
		$dateMock = $this->createMock(Date::class);

		$dailyPostStrategy = new DailyDigestPostStrategy($gameData, $wpFunctionsMock, $dateMock, $this->createUtilityFactoryMock());

		// Act
		$postContent = $dailyPostStrategy->getPostContent([]);

		// Assert
		$this->assertIsString($postContent);
		$this->assertEmpty($postContent);
	}

	private function createUtilityFactoryMock(): UtilityFactory
	{
		$utilityFactory = $this->createMock(UtilityFactory::class);
		$offerImageResolver = $this->createMock(OfferImageResolver::class);
		$gameReviewLookup = $this->createMock(GameReviewLookup::class);

		$offerImageResolver->method('resolve')->willReturn('');
		$gameReviewLookup->method('lookupBySlug')->willReturn([]);

		$utilityFactory->method('createOfferImageResolver')->willReturn($offerImageResolver);
		$utilityFactory->method('createGameReviewLookup')->willReturn($gameReviewLookup);

		return $utilityFactory;
	}
}
