# Uciki Deals Data Model

The plugin keeps `uciki_deals_source_*` tables for imported source records and introduces a publishing schema for multi-region, multi-language, multi-store delivery.

## Source tables

These store imported source rows:

- `{$wpdb->prefix}uciki_deals_source_games`
- `{$wpdb->prefix}uciki_deals_source_prices`
- `{$wpdb->prefix}uciki_deals_source_generated_posts`

They exist only for source compatibility and fallback bookkeeping.

Current source-table naming:

- `source_games`
  - `source_game_id`
  - `name`
  - `url`
  - `record_status`
  - `created_at`
- `source_prices`
  - `source_price_row_id`
  - `source_game_id`
  - `price_amount`
  - `region`
  - `discount_percent`
  - `record_status`
  - `created_at`
- `source_generated_posts`
  - `source_generated_post_id`
  - `source_price_id`
  - `wordpress_sync_status`
  - `record_status`
  - `created_at`

## New operational tables

### `{$wpdb->prefix}uciki_deals_stores`

Defines the commercial source:

- `steam`
- `epic`
- `gog`
- `humble`
- `fanatical`

### `{$wpdb->prefix}uciki_deals_market_targets`

Defines the audience and publishing target:

- country
- language
- default currency
- site section / SEO segment

Examples:

- `tr-tr`
- `ro-ro`
- `es-es`
- `de-de`
- `fr-fr`
- `us-en`
- `gb-en`
- `global-en`

### `{$wpdb->prefix}uciki_deals_games`

Canonical game record, store-independent:

- canonical name
- normalized name
- slug
- source IDs
- developer / publisher / artwork

### `{$wpdb->prefix}uciki_deals_offers`

Current active offer per store / market / currency:

- discount offers
- free-game offers
- region code
- currency code
- language code
- current regular/sale price
- discount percent
- deeplink
- availability

`offer_type` distinguishes cases such as:

- `discount`
- `free_game`

### `{$wpdb->prefix}uciki_deals_offer_snapshots`

Historical price/availability snapshots for an offer:

- useful for change tracking
- useful for SEO freshness and editorial logic
- keeps raw payload if needed

### `{$wpdb->prefix}uciki_deals_generated_posts`

Maps offers and games to generated WordPress posts by market/language/content kind.

### `{$wpdb->prefix}uciki_deals_runs`

Tracks scraper/import runs:

- hourly
- daily
- manual

This is the right place for diagnostics and future reporting.

## Why this model

This structure supports:

- multiple stores in one region
- multiple currencies in one region
- expansion beyond Turkey
- localized publishing targets
- discount and free-game flows in the same system
- cleaner SEO-oriented content generation
