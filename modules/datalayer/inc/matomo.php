<?php

function omsk_get_matomo_details() {
	return array(
		'site_id'      => omsk_get_matomo_idsite() ?? '',
		'container_id' => omsk_get_matomo_idsite() ?? '',
		'host'         => omsk_get_matomo_host() ?? '',
		'cdn'          => omsk_get_matomo_host() ? omsk_get_matomo_cdn_host() : null,
	);
}