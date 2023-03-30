<?php

function osk_get_user_details() {

	$user = wp_get_current_user();

	return array(
		'id' => $user->ID,
		'user_login' => $user->user_login,
		'user_nicename' => $user->user_nicename,
		'user_email' => $user->user_email,
		'user_registered' => $user->user_registered,
		'display_name' => $user->display_name,
		'roles' => $user->roles,
	);
}