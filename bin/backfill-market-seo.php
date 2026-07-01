<?php

use UcikiDealsEngine\Core\Integration\WpmlSupport;
use UcikiDealsEngine\Core\Settings\MarketTargetRepository;
use UcikiDealsEngine\Core\Utility\LocalizedTaxonomyResolver;

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$repo = new MarketTargetRepository();
$resolver = new LocalizedTaxonomyResolver();
$wpml = new WpmlSupport();

$targetsByKey = [];
$candidateKeys = ['tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'];
foreach ($candidateKeys as $candidateKey) {
	$target = $repo->findByKey($candidateKey);
	if (!is_array($target) || empty($target['key'])) {
		continue;
	}

	$targetsByKey[(string) $target['key']] = $target;
}

$query = new WP_Query([
	'post_type' => [UCIKI_DEALS_POST_TYPE_DIGEST, 'post'],
	'post_status' => ['publish', 'draft', 'private'],
	'posts_per_page' => -1,
	'orderby' => 'ID',
	'order' => 'ASC',
	'meta_query' => [
		'relation' => 'OR',
		[
			'key' => UCIKI_DEALS_META_CONTENT_KIND,
			'value' => UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST,
		],
		[
			'key' => UCIKI_DEALS_META_CONTENT_KIND,
			'value' => UCIKI_DEALS_CONTENT_KIND_FREE_GAME,
		],
	],
]);

$updated = 0;

foreach ($query->posts as $post) {
	if (!$post instanceof WP_Post) {
		continue;
	}

	$contentKind = (string) get_post_meta($post->ID, UCIKI_DEALS_META_CONTENT_KIND, true);
	if (!in_array($contentKind, [UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST, UCIKI_DEALS_CONTENT_KIND_FREE_GAME], true)) {
		continue;
	}

	$marketKey = (string) get_post_meta($post->ID, UCIKI_DEALS_META_MARKET_KEY, true);
	if ($marketKey === '' && $wpml->isAvailable()) {
		$elementType = apply_filters('wpml_element_type', 'post_' . $post->post_type);
		$details = apply_filters('wpml_element_language_details', null, [
			'element_id' => $post->ID,
			'element_type' => $elementType,
		]);

		if (is_object($details) && !empty($details->language_code)) {
			$marketKey = (string) $details->language_code;
			update_post_meta($post->ID, UCIKI_DEALS_META_MARKET_KEY, $marketKey);
		}
	}

	if ($marketKey === '' || empty($targetsByKey[$marketKey])) {
		$marketKey = (string) ($repo->getDefaultTarget()['key'] ?? 'tr-tr');
		update_post_meta($post->ID, UCIKI_DEALS_META_MARKET_KEY, $marketKey);
	}

	$target = $targetsByKey[$marketKey] ?? $repo->getDefaultTarget();
	$copySet = $repo->getCopySet($target);

	update_post_meta($post->ID, UCIKI_DEALS_META_LANGUAGE_CODE, (string) ($target['language_code'] ?? ''));
	update_post_meta($post->ID, UCIKI_DEALS_META_SITE_SECTION, (string) ($target['site_section'] ?? ''));

	$resolver->assignTermsToPost($post->ID, $target, $contentKind, $copySet);

	echo sprintf(
		"Updated post %d [%s] market=%s kind=%s\n",
		$post->ID,
		$post->post_type,
		$marketKey,
		$contentKind
	);
	$updated++;
}

echo sprintf("Done. Updated %d Uciki Deals posts.\n", $updated);
