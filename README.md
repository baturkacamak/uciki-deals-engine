# Uciki Deals Engine

`uciki-deals-engine` is a custom WordPress plugin for sourcing game deals and free games, storing normalized offer data, and publishing market-aware content for `uciki.com`.

The plugin currently focuses on:

- IsThereAnyDeal-powered deal discovery
- daily deals digests
- free-game posts
- multi-store offer normalization
- market/language-aware publishing
- dynamic snapshot-backed digest pages

## What Changed

This repository is no longer just a simple “scrape and publish” plugin.

It now includes:

- a normalized `uciki_deals_*` data model for stores, games, offers, snapshots, runs, and generated posts
- a native WordPress settings screen instead of ad-hoc config-only behavior
- runtime/debug state in wp-admin
- ITAD session bootstrap logic
- store URL normalization and redirect cleanup
- store/CDN image resolution strategies
- score/review enrichment for digest cards
- dynamic `uciki_deals_digest` pages backed by stored snapshots instead of `post_content`

## Architecture

High-level flow:

1. Source payloads are loaded from plugin settings.
2. ITAD data is fetched and normalized into `uciki_deals_*` tables.
3. Daily and free-game flows are selected separately.
4. Publishing creates:
   - standard posts for free games
   - `uciki_deals_digest` entries for daily deal pages
5. Daily digest pages render from `_uciki_deals_snapshot_payload` at request time.

Key components:

- `inc/Core/Settings/SettingsRepository.php`
- `inc/Core/Settings/MarketTargetRepository.php`
- `inc/Core/Settings/RuntimeStateRepository.php`
- `inc/Core/Utility/Scraper.php`
- `inc/Core/Utility/GameInformationDatabase.php`
- `inc/Core/Utility/OfferSelectionService.php`
- `inc/Core/Utility/OfferImageResolver.php`
- `inc/Core/Utility/GameReviewLookup.php`
- `inc/Modules/AdminSettingsModule.php`
- `inc/Modules/SetupModule.php`
- `inc/Modules/ScheduleModule.php`
- `inc/Modules/DigestModule.php`
- `inc/Post/Poster.php`
- `inc/Post/Strategy/DailyDigestPostStrategy.php`
- `inc/Post/Strategy/FreeGamesPostStrategy.php`
- `inc/Post/DailyDigestSnapshotRenderer.php`

## Data Model

The plugin keeps `uciki_deals_source_*` tables for imported source records and uses normalized `uciki_deals_*` tables for publishing and runtime operations.

See:

- `docs/data-model.md`

Core tables include:

- `uciki_deals_stores`
- `uciki_deals_market_targets`
- `uciki_deals_games`
- `uciki_deals_offers`
- `uciki_deals_offer_snapshots`
- `uciki_deals_generated_posts`
- `uciki_deals_runs`

## Publishing Model

### Daily digests

- stored as `uciki_deals_digest`
- URL is still a normal WordPress permalink
- visual content is rendered dynamically from snapshot data
- `post_content` is no longer the source of truth

### Free games

- published as regular WordPress posts
- triggered independently from the daily digest flow

## Admin Features

The plugin includes a native settings page under WordPress admin.

Current admin features:

- general plugin settings
- source payload management
- market target defaults
- separate posting settings for daily and free-game flows
- runtime summaries
- test actions
- manual run actions
- draft cleanup helpers

## Source Notes

The current source implementation is centered on IsThereAnyDeal.

Important behavior:

- ITAD session bootstrap is automatic
- redirects are resolved to canonical store URLs
- tracking query strings are removed
- store image resolution prefers known store/CDN patterns
- Epic uses catalog lookup where possible and falls back when required

## Local Theme Note

This repository is the plugin only.

Theme-side rendering and styling changes used by `uciki.local` live outside this repository, inside the site theme.

## Docs

- `docs/data-model.md`
- `docs/market-rollout.md`
