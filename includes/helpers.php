<?php

function omsk_get_value(&$value)
{
    return isset($value) && !empty($value) && $value ? $value : false;
}


function omsk_get_matomo_host()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field(omsk_get_value($options['omsk-matomo-host-field'])) ?? '';
}

function omsk_get_matomo_idsite()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field(omsk_get_value($options['omsk-matomo-idsite-field'])) ?? '';
}

function omsk_get_matomo_idcontainer()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field(omsk_get_value($options['omsk-matomo-idcontainer-field'])) ?? '';
}

function omsk_get_matomo_token_auth()
{
    $options = get_option('omsk-settings');

    return sanitize_text_field(omsk_get_value($options['omsk-matomo-token-auth-field'])) ?? '';
}


function omsk_fetch_matomo_api($param_string)
{
    $host = omsk_get_matomo_host();
    $idsite = omsk_get_matomo_idsite();
    $token_auth = omsk_get_matomo_token_auth();

    // Parse parameter string into array
    $params = array();
    parse_str(ltrim($param_string, '&'), $params);

    // Build POST body with all parameters
    $body_params = array_merge(array(
        'module' => 'API',
        'format' => 'JSON',
        'idSite' => $idsite,
        'token_auth' => $token_auth,
    ), $params);

    // Make POST request with token in body AND as Bearer token
    $response = wp_remote_post("$host/index.php", array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $token_auth,
        ),
        'body' => $body_params,
    ));

    if (is_wp_error($response)) {
        return $response;
    }

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
    $plan = omsk_get_matomo_plan();

    if($plan === 'cloud'){
        $cdn = str_replace('https://', 'https://cdn.matomo.cloud/', $cdn);
    }

    return $cdn;
}