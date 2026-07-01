<?php
/**
 * Core constants for the Uciki deals engine.
 */

const UCIKI_DEALS_VERSION = '0.1.0';
define('UCIKI_DEALS_PLUGIN_DIR', dirname(__DIR__));
const UCIKI_DEALS_PLUGIN_FILE = UCIKI_DEALS_PLUGIN_DIR . '/uciki-deals-engine.php';

// Option keys
const UCIKI_DEALS_SETTINGS_OPTION = 'uciki_deals_settings';
const UCIKI_DEALS_RUNTIME_STATE_OPTION = 'uciki_deals_runtime_state';
const UCIKI_DEALS_SCHEMA_VERSION = '2026-07-01-06';
const UCIKI_DEALS_SCHEMA_VERSION_OPTION = 'uciki_deals_schema_version';
const UCIKI_DEALS_SETTINGS_FILE = UCIKI_DEALS_PLUGIN_DIR . '/settings.json';

// Content model
const UCIKI_DEALS_POST_TYPE_DIGEST = 'uciki_deals_digest';
const UCIKI_DEALS_CONTENT_KIND_DAILY_DIGEST = 'daily_deals_digest';
const UCIKI_DEALS_CONTENT_KIND_FREE_GAME = 'free_game';
const UCIKI_DEALS_META_MARKET_KEY = '_uciki_deals_market_key';
const UCIKI_DEALS_META_LANGUAGE_CODE = '_uciki_deals_language_code';
const UCIKI_DEALS_META_SITE_SECTION = '_uciki_deals_site_section';
const UCIKI_DEALS_META_CONTENT_KIND = '_uciki_deals_content_kind';
const UCIKI_DEALS_META_SNAPSHOT_PAYLOAD = '_uciki_deals_snapshot_payload';

// Table suffixes
const UCIKI_DEALS_TABLE_STORES = 'uciki_deals_stores';
const UCIKI_DEALS_TABLE_MARKET_TARGETS = 'uciki_deals_market_targets';
const UCIKI_DEALS_TABLE_GAMES = 'uciki_deals_games';
const UCIKI_DEALS_TABLE_OFFERS = 'uciki_deals_offers';
const UCIKI_DEALS_TABLE_OFFER_SNAPSHOTS = 'uciki_deals_offer_snapshots';
const UCIKI_DEALS_TABLE_GENERATED_POSTS = 'uciki_deals_generated_posts';
const UCIKI_DEALS_TABLE_RUNS = 'uciki_deals_runs';

// Cron and cache keys
const UCIKI_DEALS_HOOK_HOURLY_SCHEDULER = 'uciki_deals_schedule_hourly';
const UCIKI_DEALS_HOOK_DAILY_SCHEDULER = 'uciki_deals_schedule_daily';
const UCIKI_DEALS_HOOK_DAILY_MARKET = 'uciki_deals_run_daily_market';
const UCIKI_DEALS_HOOK_HOURLY_MARKET = 'uciki_deals_run_hourly_market';
const UCIKI_DEALS_TRANSIENT_ITAD_SESSION = 'uciki_deals_itad_session_bootstrap';
const UCIKI_DEALS_CACHE_ITAD_REVIEWS_PREFIX = 'uciki_deals_itad_reviews_';
const UCIKI_DEALS_CACHE_STORE_IMAGE_PREFIX = 'uciki_deals_store_image_';
const UCIKI_DEALS_CACHE_STORE_IMAGE_MISS = '__uciki_deals_store_image_miss__';
const UCIKI_DEALS_CACHE_EPIC_FREE_CATALOG_PREFIX = 'uciki_deals_epic_free_catalog_';
const UCIKI_DEALS_OPTION_EPIC_IMAGE_MISSES = 'uciki_deals_epic_image_misses';

if (function_exists('plugin_dir_url')) {
	define('UCIKI_DEALS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

defined('UCIKI_DEALS_BASE_FILE') or define(
	'UCIKI_DEALS_BASE_FILE',
	str_replace(dirname(UCIKI_DEALS_PLUGIN_FILE, 2) . '/', '', UCIKI_DEALS_PLUGIN_FILE)
);
