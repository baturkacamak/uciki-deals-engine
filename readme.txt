=== Uciki Deals Engine ===
Contributors: baturkacamak
Tags: deals, games, automation, content, wpml
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Automates market-aware game deal publishing for uciki.com.

== Description ==

Uciki Deals Engine is a custom WordPress plugin used by uciki.com to:

* fetch and normalize game deal data
* store market, store, offer, and run metadata
* publish localized digest content
* integrate with WPML-aware routing and taxonomy rules
* support scheduled and manual publishing workflows

== Installation ==

1. Place the plugin in `/wp-content/plugins/uciki-deals-engine/`
1. Run `composer install` inside the plugin directory
1. Activate `Uciki Deals Engine` in WordPress
1. Confirm the plugin basename resolves to `uciki-deals-engine/uciki-deals-engine.php`

== Operational Notes ==

* Runtime scripts are stored under `bin/`
* The plugin depends on Composer packages in `vendor/`
* Shared naming uses the `UCIKI_DEALS_*` constant family and `uciki_deals_*` storage keys

== Changelog ==

= 1.1.0 =
* Renamed the plugin identity to Uciki Deals Engine
* Standardized constants, helpers, post types, tables, and operational scripts
* Aligned theme integration with the new slug and digest template names
