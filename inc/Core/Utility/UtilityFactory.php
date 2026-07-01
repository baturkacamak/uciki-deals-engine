<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 17:47
 */

namespace UcikiDealsEngine\Core\Utility;

use UcikiDealsEngine\UcikiDealsEngine;
use UcikiDealsEngine\Core\Utility\Image\EpicCatalogImageLookup;
use UcikiDealsEngine\Core\Utility\Image\ImageUrlNormalizer;
use UcikiDealsEngine\Core\Utility\Image\RemoteMetaImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\EpicFreeGamesCatalogImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\EpicStoreCdnImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\ExistingStoreCdnImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\FanaticalImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\GogImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\HumbleAgeCheckImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\HumbleStoreCdnImageStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\ItadAssetFallbackStrategy;
use UcikiDealsEngine\Core\Utility\Image\Strategy\SteamImageStrategy;

/**
 * Class UtilityFactory
 *
 * This class is responsible for creating instances of utility classes.
 *
 * @package         UcikiDealsEngine\Core\Utility
 */
class UtilityFactory
{
	/**
	 * Creates an instance of the ImageRetriever class.
	 *
	 * @return ImageRetriever
	 */
	public function createImageRetriever(): ImageRetriever
	{
		return new ImageRetriever(
			new WebClient([
				'timeout' => 3,
				'connect_timeout' => 2,
				'http_errors' => false,
				'allow_redirects' => [
					'max' => 5,
				],
			]),
			new DOMHandler()
		);
	}

	public function createGameReviewLookup(): GameReviewLookup
	{
		return new GameReviewLookup(
			new WebClient([
				'timeout' => 4,
				'connect_timeout' => 2,
				'http_errors' => false,
			])
		);
	}

	public function createOfferImageResolver(): OfferImageResolver
	{
		$image_retriever = $this->createImageRetriever();
		$image_url_normalizer = new ImageUrlNormalizer();
		$settings = UcikiDealsEngine::getInstance()->settings ?? [];
		$bootstrap = function_exists('get_transient') ? get_transient('uciki_deals_itad_session_bootstrap') : [];
		$country_code = 'US';
		if (is_array($bootstrap) && !empty($bootstrap['itad_country_code'])) {
			$country_code = (string) $bootstrap['itad_country_code'];
		} elseif (!empty($settings['source']['itad_country_code'])) {
			$country_code = (string) $settings['source']['itad_country_code'];
		}

		$lookupClient = new WebClient([
			'timeout' => 4,
			'connect_timeout' => 2,
			'http_errors' => false,
		]);
		$epicLookups = [
			new EpicCatalogImageLookup($lookupClient, $image_url_normalizer, $country_code, 'en-US'),
		];
		if (strtoupper($country_code) !== 'US') {
			$epicLookups[] = new EpicCatalogImageLookup($lookupClient, $image_url_normalizer, 'US', 'en-US');
		}

		return new OfferImageResolver([
			new ExistingStoreCdnImageStrategy(),
			new SteamImageStrategy(),
			new GogImageStrategy($image_url_normalizer),
			new FanaticalImageStrategy($image_url_normalizer),
			new HumbleStoreCdnImageStrategy($image_url_normalizer),
			new HumbleAgeCheckImageStrategy(),
			new EpicFreeGamesCatalogImageStrategy($epicLookups),
			new EpicStoreCdnImageStrategy($image_url_normalizer),
			new RemoteMetaImageStrategy($image_retriever),
			new ItadAssetFallbackStrategy(),
		]);
	}
}
