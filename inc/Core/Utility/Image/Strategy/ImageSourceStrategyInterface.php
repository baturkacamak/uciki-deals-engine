<?php

namespace UcikiDealsEngine\Core\Utility\Image\Strategy;

interface ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string;
}
