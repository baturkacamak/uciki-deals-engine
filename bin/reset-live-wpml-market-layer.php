<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

global $wpdb;

$marketCodes = ['tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'];
$displayCodes = ['tr', 'en', 'es', 'tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'];
$postTypes = ['post_' . UCIKI_DEALS_POST_TYPE_DIGEST, 'post_post'];

$marketPostIds = $wpdb->get_col(
	"SELECT DISTINCT post_id
	FROM {$wpdb->postmeta}
	WHERE meta_key = '" . UCIKI_DEALS_META_MARKET_KEY . "'"
);

$marketPostIds = array_values(array_filter(array_map('intval', is_array($marketPostIds) ? $marketPostIds : [])));

if ($marketPostIds !== []) {
	$postPlaceholders = implode(', ', array_fill(0, count($marketPostIds), '%d'));
	$typePlaceholders = implode(', ', array_fill(0, count($postTypes), '%s'));
	$params = array_merge($postTypes, $marketPostIds);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}icl_translations
			WHERE element_type IN ({$typePlaceholders})
				AND element_id IN ({$postPlaceholders})",
			$params
		)
	);
}

$langPlaceholders = implode(', ', array_fill(0, count($marketCodes), '%s'));
$displayPlaceholders = implode(', ', array_fill(0, count($displayCodes), '%s'));

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}icl_languages_translations
		WHERE language_code IN ({$langPlaceholders})
			OR display_language_code IN ({$displayPlaceholders})",
		array_merge($marketCodes, $displayCodes)
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}icl_locale_map
		WHERE code IN ({$langPlaceholders})",
		$marketCodes
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}icl_flags
		WHERE lang_code IN ({$langPlaceholders})",
		$marketCodes
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}icl_languages
		WHERE code IN ({$langPlaceholders})",
		$marketCodes
	)
);

delete_option('icl_sitepress_settings');

echo 'Reset market WPML layer for Uciki Deals posts and custom market languages.' . PHP_EOL;
