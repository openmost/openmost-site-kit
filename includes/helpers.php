<?php


function omsk_get_matomo_host()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field($options['omsk-matomo-host-field']) ?? '';
}

function omsk_get_matomo_idsite()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field($options['omsk-matomo-idsite-field']) ?? '';
}

function omsk_get_matomo_idcontainer()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field($options['omsk-matomo-idcontainer-field']) ?? '';
}

function omsk_get_matomo_token_auth()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field($options['omsk-matomo-token-auth-field']) ?? '';
}

function omsk_get_matomo_period()
{
    return isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'day';
}

function omsk_get_matomo_date()
{
    return isset($_GET['date']) ? sanitize_text_field($_GET['date']) : 'last7';
}

function omsk_get_base_fetch_url($params)
{
    $host = omsk_get_matomo_host();
    $idsite = omsk_get_matomo_idsite();
    $token_auth = omsk_get_matomo_token_auth();

    return "$host/index.php?module=API&format=JSON&idSite=$idsite&token_auth=$token_auth$params";
}

function omsk_fetch_matomo_api($url)
{

    $base_url = omsk_get_base_fetch_url($url);

    $response = wp_remote_get(sanitize_url("$base_url"));
    $body = wp_remote_retrieve_body($response);

    return (array)json_decode($body);
}

function omsk_get_matomo_plan()
{
    $host = omsk_get_matomo_host();
    $plan = 'on_premise';

    if (str_contains($host, '.matomo.cloud')) {
        $plan = 'cloud';
    }

    return $plan;
}

function omsk_get_matomo_cdn_host()
{
    $cdn = omsk_get_matomo_host();
    $id_container = omsk_get_matomo_host();
    $plan = omsk_get_matomo_plan();

    if($plan === 'cloud'){
        $cdn = str_replace('https://', 'https://cdn.matomo.cloud/', $cdn);
    }

    return $cdn;
}


add_action('wp_ajax_omsk_handle_fetch_matomo_api', 'omsk_handle_fetch_matomo_api');
add_action('wp_ajax_nopriv_omsk_handle_fetch_matomo_api', 'omsk_handle_fetch_matomo_api');

function omsk_handle_fetch_matomo_api()
{

    $params = '';

    foreach ($_POST as $index => $param) {
        $params = $params . "&$index=$param";
    }

    $data = omsk_fetch_matomo_api($params);

    wp_send_json_success($data);
}