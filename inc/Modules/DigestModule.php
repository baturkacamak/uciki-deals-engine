<?php

namespace UcikiDealsEngine\Modules;

use UcikiDealsEngine\Core\Module\AbstractModule;
use UcikiDealsEngine\Core\Settings\MarketTargetRepository;
use UcikiDealsEngine\Core\Utility\UtilityFactory;
use UcikiDealsEngine\Post\DailyDigestSnapshotRenderer;

class DigestModule extends AbstractModule
{
	public function setup()
	{
		$this->wpFunctions->addHook('init', 'registerDigestPostType');
		$this->wpFunctions->addHook('the_content', 'renderDigestContent', 20);
		$this->wpFunctions->addHook('request', 'mapDigestRequest');
		$this->wpFunctions->addHook('post_type_link', 'filterDigestPermalink', 10, 2);
		$this->wpFunctions->addHook('admin_menu', 'hideDefaultPostsMenu', 999);
		$this->wpFunctions->addHook('admin_init', 'redirectDefaultPostScreens');
	}

	public function registerDigestPostType(): void
	{
		register_post_type(
			UCIKI_DEALS_POST_TYPE_DIGEST,
			[
				'labels' => [
					'name' => 'Uciki Deal Digests',
					'singular_name' => 'Uciki Deal Digest',
				],
				'public' => true,
				'show_ui' => true,
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => ['slug' => '', 'with_front' => false],
				'supports' => ['title', 'editor', 'excerpt', 'author', 'custom-fields'],
				'taxonomies' => ['category', 'post_tag'],
				'publicly_queryable' => true,
				'exclude_from_search' => false,
			]
		);

		// Support WPML-style market-prefixed digest URLs like /tr-tr/28-mart-2026-oyun-indirimleri/.
		add_rewrite_rule(
			'^([a-z]{2}-[a-z]{2})/([^/]+)/?$',
			'index.php?uciki_deals_digest=$matches[2]&post_type=uciki_deals_digest&lang=$matches[1]',
			'top'
		);

		// Legacy support for previous root-level digest URLs.
		add_rewrite_rule(
			'^([a-z]{2}(?:-[a-z]{2})?-\d{4}-\d{2}-\d{2}-game-deals)/?$',
			'index.php?uciki_deals_digest=$matches[1]',
			'top'
		);
	}

	public function renderDigestContent(string $content): string
	{
		if (is_admin() || !is_singular(UCIKI_DEALS_POST_TYPE_DIGEST)) {
			return $content;
		}

		$postId = get_the_ID();
		if (!$postId) {
			return $content;
		}

		$snapshot = get_post_meta($postId, UCIKI_DEALS_META_SNAPSHOT_PAYLOAD, true);
		if (!is_array($snapshot) || empty($snapshot['games'])) {
			return $content;
		}

		$enrichedSnapshot = $this->enrichSnapshotImages($snapshot);
		if ($enrichedSnapshot !== $snapshot) {
			update_post_meta($postId, UCIKI_DEALS_META_SNAPSHOT_PAYLOAD, $enrichedSnapshot);
			$snapshot = $enrichedSnapshot;
		}

		$marketKey = (string) get_post_meta($postId, UCIKI_DEALS_META_MARKET_KEY, true);
		$repo = new MarketTargetRepository();
		$marketTarget = $marketKey !== '' ? ($repo->findByKey($marketKey) ?: $repo->getDefaultTarget()) : $repo->getDefaultTarget();
		$copySet = $repo->getCopySet($marketTarget);

		return (new DailyDigestSnapshotRenderer())->render($snapshot, $copySet);
	}

	public function mapDigestRequest(array $queryVars): array
	{
		if (is_admin() || !empty($queryVars['post_type'])) {
			return $queryVars;
		}

		$requestedSlug = '';
		if (!empty($queryVars['name']) && is_string($queryVars['name'])) {
			$requestedSlug = (string) $queryVars['name'];
		} elseif (!empty($queryVars['pagename']) && is_string($queryVars['pagename'])) {
			$requestedSlug = trim((string) $queryVars['pagename'], '/');
		}

		if ($requestedSlug === '' || str_contains($requestedSlug, '/')) {
			return $queryVars;
		}

		global $wpdb;
		$postId = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_status IN ('publish','draft','private') LIMIT 1",
				$requestedSlug,
				UCIKI_DEALS_POST_TYPE_DIGEST
			)
		);

		if ($postId <= 0) {
			return $queryVars;
		}

		unset($queryVars['name']);
		unset($queryVars['pagename']);
		$queryVars['name'] = get_post_field('post_name', $postId);
		$queryVars[UCIKI_DEALS_POST_TYPE_DIGEST] = get_post_field('post_name', $postId);
		$queryVars['post_type'] = UCIKI_DEALS_POST_TYPE_DIGEST;
		$queryVars['page'] = '';
		$queryVars['attachment'] = '';
		$marketKey = (string) get_post_meta($postId, UCIKI_DEALS_META_MARKET_KEY, true);
		if ($marketKey !== '') {
			$queryVars['lang'] = $marketKey;
			do_action('wpml_switch_language', $marketKey);
		}

		return $queryVars;
	}

	public function filterDigestPermalink(string $postLink, $post): string
	{
		if (!is_object($post) || ($post->post_type ?? '') !== UCIKI_DEALS_POST_TYPE_DIGEST) {
			return $postLink;
		}

		$marketKey = (string) get_post_meta($post->ID, UCIKI_DEALS_META_MARKET_KEY, true);
		if ($marketKey === '') {
			$marketKey = 'tr-tr';
		}

		return home_url(user_trailingslashit($marketKey . '/' . $post->post_name));
	}

	public function hideDefaultPostsMenu(): void
	{
		remove_menu_page('edit.php');
	}

	public function redirectDefaultPostScreens(): void
	{
		if (!is_admin() || wp_doing_ajax()) {
			return;
		}

		global $pagenow;

		$isPostList = $pagenow === 'edit.php' && (!isset($_GET['post_type']) || sanitize_key((string) wp_unslash($_GET['post_type'])) === 'post');
		$isPostNew = $pagenow === 'post-new.php' && (!isset($_GET['post_type']) || sanitize_key((string) wp_unslash($_GET['post_type'])) === 'post');

		if ($isPostList || $isPostNew) {
			wp_safe_redirect(admin_url('edit.php?post_type=' . UCIKI_DEALS_POST_TYPE_DIGEST));
			exit;
		}
	}

	private function enrichSnapshotImages(array $snapshot): array
	{
		$games = $snapshot['games'] ?? null;
		if (!is_array($games) || $games === []) {
			return $snapshot;
		}

		$resolver = (new UtilityFactory())->createOfferImageResolver();
		$changed = false;

		foreach ($games as $index => $game) {
			if (!is_array($game)) {
				continue;
			}

			if (!empty($game['resolved_image_url'])) {
				continue;
			}

			$imageUrl = $resolver->resolve([
				'url' => (string) ($game['url'] ?? ''),
				'store_key' => (string) ($game['store_key'] ?? ''),
				'thumbnail_url' => (string) ($game['raw_thumbnail_url'] ?? ''),
			]);

			if (!is_string($imageUrl) || $imageUrl === '') {
				continue;
			}

			$games[$index]['resolved_image_url'] = $imageUrl;
			$changed = true;
		}

		if (!$changed) {
			return $snapshot;
		}

		$snapshot['games'] = $games;

		return $snapshot;
	}

}
