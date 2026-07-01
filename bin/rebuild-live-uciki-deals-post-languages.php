<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$wpml = new \UcikiDealsEngine\Core\Integration\WpmlSupport();
if (!$wpml->isAvailable()) {
	exit("WPML is not active.\n");
}

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_type, market_meta.meta_value AS market_key
	FROM {$wpdb->posts} p
	INNER JOIN {$wpdb->postmeta} market_meta ON market_meta.post_id = p.ID AND market_meta.meta_key = '" . UCIKI_DEALS_META_MARKET_KEY . "'
	INNER JOIN {$wpdb->postmeta} kind_meta ON kind_meta.post_id = p.ID AND kind_meta.meta_key = '" . UCIKI_DEALS_META_CONTENT_KIND . "'
	WHERE p.post_type IN ('" . UCIKI_DEALS_POST_TYPE_DIGEST . "', 'post')
		AND kind_meta.meta_value IN ('" . UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST . "', '" . UCIKI_DEALS_CONTENT_KIND_FREE_GAME . "')",
	ARRAY_A
);

foreach ($rows as $row) {
	$postId = (int) ($row['ID'] ?? 0);
	$postType = (string) ($row['post_type'] ?? '');
	$marketKey = (string) ($row['market_key'] ?? '');

	if ($postId <= 0 || $postType === '' || $marketKey === '') {
		continue;
	}

	$wpml->assignPostLanguage($postId, $postType, $marketKey);
	echo "Assigned {$postType} {$postId} -> {$marketKey}" . PHP_EOL;
}
