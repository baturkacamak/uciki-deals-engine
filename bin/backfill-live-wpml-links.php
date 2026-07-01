<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$wpml = new \UcikiDealsEngine\Core\Integration\WpmlSupport();
if (!$wpml->isAvailable()) {
	exit("WPML is not active.\n");
}

global $wpdb;

$defaultMarketKey = 'tr-tr';
$today = current_time('Y-m-d');
$postMetaTable = $wpdb->postmeta;
$postTable = $wpdb->posts;
$generatedPostsTable = $wpdb->prefix . UCIKI_DEALS_TABLE_GENERATED_POSTS;

$sourceDigestId = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT p.ID
		FROM {$postTable} p
		INNER JOIN {$postMetaTable} market_meta ON market_meta.post_id = p.ID AND market_meta.meta_key = %s
		INNER JOIN {$postMetaTable} kind_meta ON kind_meta.post_id = p.ID AND kind_meta.meta_key = %s
		WHERE p.post_type = %s
			AND market_meta.meta_value = %s
			AND kind_meta.meta_value = %s
			AND DATE(p.post_date) = %s
		ORDER BY p.ID DESC
		LIMIT 1",
		UCIKI_DEALS_META_MARKET_KEY,
		UCIKI_DEALS_META_CONTENT_KIND,
		UCIKI_DEALS_POST_TYPE_DIGEST,
		$defaultMarketKey,
		UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST,
		$today
	)
);

if ($sourceDigestId > 0) {
	$digests = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, market_meta.meta_value AS market_key
			FROM {$postTable} p
			INNER JOIN {$postMetaTable} market_meta ON market_meta.post_id = p.ID AND market_meta.meta_key = %s
			INNER JOIN {$postMetaTable} kind_meta ON kind_meta.post_id = p.ID AND kind_meta.meta_key = %s
			WHERE p.post_type = %s
				AND kind_meta.meta_value = %s
				AND DATE(p.post_date) = %s",
			UCIKI_DEALS_META_MARKET_KEY,
			UCIKI_DEALS_META_CONTENT_KIND,
			UCIKI_DEALS_POST_TYPE_DIGEST,
			UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST,
			$today
		),
		ARRAY_A
	);

	foreach ($digests as $digest) {
		$postId = (int) ($digest['ID'] ?? 0);
		$marketKey = (string) ($digest['market_key'] ?? '');
		if ($postId <= 0 || $marketKey === '' || $marketKey === $defaultMarketKey) {
			continue;
		}

		$wpml->linkPostTranslation($sourceDigestId, $postId, UCIKI_DEALS_POST_TYPE_DIGEST, $marketKey);
		echo "Linked digest {$postId} to {$sourceDigestId} for {$marketKey}\n";
	}
}

$sourceFreeRows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT gp.game_id, gp.wp_post_id
		FROM {$generatedPostsTable} gp
		INNER JOIN {$postMetaTable} market_meta ON market_meta.post_id = gp.wp_post_id AND market_meta.meta_key = %s
		WHERE gp.content_kind = %s
			AND market_meta.meta_value = %s",
		UCIKI_DEALS_META_MARKET_KEY,
		UCIKI_DEALS_CONTENT_KIND_FREE_GAME,
		$defaultMarketKey
	),
	ARRAY_A
);

$sourceFreeByGame = [];
	foreach ($sourceFreeRows as $row) {
		$gameId = (int) ($row['game_id'] ?? 0);
		$postId = (int) ($row['wp_post_id'] ?? 0);
		if ($gameId > 0 && $postId > 0) {
			$sourceFreeByGame[$gameId] = $postId;
		}
	}

$translatedFreeRows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT gp.game_id, gp.wp_post_id, market_meta.meta_value AS market_key
		FROM {$generatedPostsTable} gp
		INNER JOIN {$postMetaTable} market_meta ON market_meta.post_id = gp.wp_post_id AND market_meta.meta_key = %s
		WHERE gp.content_kind = %s
			AND market_meta.meta_value <> %s",
		UCIKI_DEALS_META_MARKET_KEY,
		UCIKI_DEALS_CONTENT_KIND_FREE_GAME,
		$defaultMarketKey
	),
	ARRAY_A
);

foreach ($translatedFreeRows as $row) {
	$gameId = (int) ($row['game_id'] ?? 0);
	$postId = (int) ($row['wp_post_id'] ?? 0);
	$marketKey = (string) ($row['market_key'] ?? '');
	$sourcePostId = (int) ($sourceFreeByGame[$gameId] ?? 0);

	if ($gameId <= 0 || $postId <= 0 || $marketKey === '' || $sourcePostId <= 0 || $sourcePostId === $postId) {
		continue;
	}

	$wpml->linkPostTranslation($sourcePostId, $postId, 'post', $marketKey);
	echo "Linked free game {$postId} to {$sourcePostId} for {$marketKey}\n";
}
