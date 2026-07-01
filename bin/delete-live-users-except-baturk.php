<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

require_once ABSPATH . 'wp-admin/includes/user.php';

$keepLogin = 'baturk';
$keepUser = get_user_by('login', $keepLogin);

if (!$keepUser || empty($keepUser->ID)) {
	exit("Could not find {$keepLogin}.\n");
}

$keepUserId = (int) $keepUser->ID;
$users = get_users([
	'fields' => ['ID', 'user_login'],
]);

$deleted = [];

foreach ($users as $user) {
	$userId = (int) ($user->ID ?? 0);
	$userLogin = (string) ($user->user_login ?? '');

	if ($userId <= 0 || $userId === $keepUserId) {
		continue;
	}

	wp_delete_user($userId, $keepUserId);
	$deleted[] = $userLogin;
}

echo 'Kept user: ' . $keepLogin . ' (' . $keepUserId . ')' . PHP_EOL;
echo 'Deleted users: ' . count($deleted) . PHP_EOL;

if ($deleted !== []) {
	echo implode(', ', $deleted) . PHP_EOL;
}
