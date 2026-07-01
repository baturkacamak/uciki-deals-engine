<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$activePlugins = get_option('active_plugins', []);
if (!is_array($activePlugins)) {
	$activePlugins = [];
}

$normalized = [];
foreach ($activePlugins as $pluginFile) {
	$pluginFile = (string) $pluginFile;
	if ($pluginFile === '') {
		continue;
	}

	$pluginDir = dirname($pluginFile);
	$pluginBasename = basename($pluginFile);

	if ($pluginBasename === 'uciki-deals-engine.php' && $pluginDir !== 'uciki-deals-engine') {
		continue;
	}

	$normalized[] = $pluginFile;
}

$normalized[] = 'uciki-deals-engine/uciki-deals-engine.php';
$normalized = array_values(array_unique($normalized));

update_option('active_plugins', $normalized, false);

echo "Normalized active_plugins.\n";
