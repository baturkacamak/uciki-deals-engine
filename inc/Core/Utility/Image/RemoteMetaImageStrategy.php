<?php

namespace UcikiDealsEngine\Core\Utility\Image;

use UcikiDealsEngine\Core\Utility\Image\Strategy\ImageSourceStrategyInterface;
use UcikiDealsEngine\Core\Utility\ImageRetriever;

class RemoteMetaImageStrategy implements ImageSourceStrategyInterface
{
	private ImageRetriever $imageRetriever;

	public function __construct(ImageRetriever $imageRetriever)
	{
		$this->imageRetriever = $imageRetriever;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') === 'epic') {
			return null;
		}

		$url = (string) ($context['url'] ?? '');
		if ($url === '') {
			return null;
		}

		$fallback = (string) ($context['thumbnail_url'] ?? '');
		$image_url = $this->imageRetriever->retrieve($url, $fallback);

		return $image_url !== '' ? $image_url : null;
	}
}
