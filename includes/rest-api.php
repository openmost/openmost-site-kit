<?php
/**
 * REST API Endpoints for Matomo Site Kit
 *
 * All Matomo API calls are proxied through WordPress REST API
 * to avoid CORS issues and keep credentials secure.
 */

// Register REST API routes
add_action('rest_api_init', 'omsk_register_rest_routes');

function omsk_register_rest_routes() {
    // Settings endpoint
    register_rest_route('openmost-site-kit/v1', '/settings', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'omsk_rest_get_settings',
            'permission_callback' => 'omsk_rest_permission_check',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'omsk_rest_update_settings',
            'permission_callback' => 'omsk_rest_permission_check',
            'args'                => array(
                'host' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                    'validate_callback' => 'omsk_validate_url',
                ),
                'idSite' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'idContainer' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'tokenAuth' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'enableClassicTracking' => array(
                    'type'              => 'boolean',
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
                'enableMtmTracking' => array(
                    'type'              => 'boolean',
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ),
    ));

    // Matomo API proxy endpoint
    register_rest_route('openmost-site-kit/v1', '/matomo/(?P<method>[a-zA-Z.]+)', array(
        'methods'             => 'POST',
        'callback'            => 'omsk_rest_matomo_proxy',
        'permission_callback' => 'omsk_rest_permission_check',
        'args'                => array(
            'method' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'period' => array(
                'type'     => 'string',
                'default'  => 'day',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date' => array(
                'type'     => 'string',
                'default'  => 'last7',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Test connection endpoint
    register_rest_route('openmost-site-kit/v1', '/test-connection', array(
        'methods'             => 'POST',
        'callback'            => 'omsk_rest_test_connection',
        'permission_callback' => 'omsk_rest_permission_check',
        'args'                => array(
            'host' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'tokenAuth' => array(
                'required' => false,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'idSite' => array(
                'required' => true,
                'type'     => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // Post-specific analytics endpoint
    register_rest_route('openmost-site-kit/v1', '/post-stats/(?P<post_id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'omsk_rest_get_post_stats',
        'permission_callback' => 'omsk_rest_permission_check',
        'args'                => array(
            'post_id' => array(
                'required' => true,
                'type'     => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'period' => array(
                'type'     => 'string',
                'default'  => 'day',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date' => array(
                'type'     => 'string',
                'default'  => 'last7',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}

/**
 * Permission check for REST API endpoints
 */
function omsk_rest_permission_check() {
    return current_user_can('manage_options');
}

/**
 * Validate URL
 */
function omsk_validate_url($value) {
    return filter_var($value, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get current settings
 */
function omsk_rest_get_settings() {
    $options = get_option('omsk-settings', array());

    return rest_ensure_response(array(
        'host'                   => isset($options['omsk-matomo-host-field']) ? $options['omsk-matomo-host-field'] : '',
        'idSite'                 => isset($options['omsk-matomo-idsite-field']) ? absint($options['omsk-matomo-idsite-field']) : '',
        'idContainer'            => isset($options['omsk-matomo-idcontainer-field']) ? $options['omsk-matomo-idcontainer-field'] : '',
        'tokenAuth'              => isset($options['omsk-matomo-token-auth-field']) ? $options['omsk-matomo-token-auth-field'] : '',
        'enableClassicTracking'  => isset($options['omsk-matomo-enable-classic-tracking-code-field']) ? (bool) $options['omsk-matomo-enable-classic-tracking-code-field'] : false,
        'enableMtmTracking'      => isset($options['omsk-matomo-enable-mtm-tracking-code-field']) ? (bool) $options['omsk-matomo-enable-mtm-tracking-code-field'] : false,
        'plan'                   => omsk_get_matomo_plan(),
    ));
}

/**
 * Update settings
 */
function omsk_rest_update_settings($request) {
    $options = array(
        'omsk-matomo-host-field'                        => $request->get_param('host'),
        'omsk-matomo-idsite-field'                      => $request->get_param('idSite'),
        'omsk-matomo-idcontainer-field'                 => $request->get_param('idContainer'),
        'omsk-matomo-token-auth-field'                  => $request->get_param('tokenAuth'),
        'omsk-matomo-enable-classic-tracking-code-field' => $request->get_param('enableClassicTracking') ? 1 : 0,
        'omsk-matomo-enable-mtm-tracking-code-field'     => $request->get_param('enableMtmTracking') ? 1 : 0,
    );

    update_option('omsk-settings', $options);

    return rest_ensure_response(array(
        'success' => true,
        'message' => __('Settings saved successfully', 'openmost-site-kit'),
    ));
}

/**
 * Proxy requests to Matomo API
 */
function omsk_rest_matomo_proxy($request) {
    $method = $request->get_param('method');
    $period = $request->get_param('period');
    $date = $request->get_param('date');

    // Get additional parameters from request body
    $params = $request->get_json_params();
    if (!$params) {
        $params = array();
    }

    // Build parameter string
    $param_string = "&method=$method&period=$period&date=$date";

    foreach ($params as $key => $value) {
        if (!in_array($key, array('method', 'period', 'date'))) {
            $param_string .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }

    $data = omsk_fetch_matomo_api($param_string);

    if (is_wp_error($data)) {
        return new WP_Error(
            'matomo_api_error',
            __('Failed to fetch data from Matomo', 'openmost-site-kit'),
            array('status' => 500)
        );
    }

    return rest_ensure_response($data);
}

/**
 * Test Matomo connection
 */
function omsk_rest_test_connection($request) {
    $host = $request->get_param('host');
    $token_auth = $request->get_param('tokenAuth');
    $id_site = $request->get_param('idSite');

    // Test API call using POST with all parameters in body
    $url = "$host/index.php";

    $body_params = array(
        'module' => 'API',
        'format' => 'JSON',
        'method' => 'API.getMatomoVersion',
        'idSite' => $id_site,
    );

    if ($token_auth) {
        $body_params['token_auth'] = $token_auth;
    }

    $headers = array(
        'Content-Type' => 'application/x-www-form-urlencoded',
    );

    // Add Bearer token if available
    if ($token_auth) {
        $headers['Authorization'] = 'Bearer ' . $token_auth;
    }

    $response = wp_remote_post($url, array(
        'timeout' => 10,
        'headers' => $headers,
        'body' => $body_params,
    ));

    if (is_wp_error($response)) {
        return new WP_Error(
            'connection_failed',
            __('Failed to connect to Matomo', 'openmost-site-kit'),
            array('status' => 500)
        );
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (isset($data->result) && $data->result === 'error') {
        return new WP_Error(
            'matomo_error',
            isset($data->message) ? $data->message : __('Matomo returned an error', 'openmost-site-kit'),
            array('status' => 400)
        );
    }

    return rest_ensure_response(array(
        'success' => true,
        'version' => $data->value ?? null,
        'message' => __('Successfully connected to Matomo', 'openmost-site-kit'),
    ));
}

/**
 * Get analytics stats for a specific post/page
 */
function omsk_rest_get_post_stats($request) {
    $post_id = $request->get_param('post_id');
    $period = $request->get_param('period');
    $date = $request->get_param('date');

    // Get post URL
    $post_url = get_permalink($post_id);

    if (!$post_url) {
        return new WP_Error(
            'invalid_post',
            __('Invalid post ID', 'openmost-site-kit'),
            array('status' => 404)
        );
    }

    // Build segment for this specific page
    $segment = 'pageUrl==' . urlencode($post_url);

    // Fetch stats from Matomo
    $param_string = "&method=API.get&period=$period&date=$date&segment=$segment";
    $param_string .= "&showColumns=nb_visits,nb_uniq_visitors,nb_pageviews,nb_actions,bounce_rate,avg_time_on_page";

    $data = omsk_fetch_matomo_api($param_string);

    if (is_wp_error($data)) {
        return new WP_Error(
            'matomo_api_error',
            __('Failed to fetch data from Matomo', 'openmost-site-kit'),
            array('status' => 500)
        );
    }

    // Also fetch trend data for comparison
    $comparison_date = omsk_get_comparison_date($date);
    $param_string_comparison = "&method=API.get&period=$period&date=$comparison_date&segment=$segment";
    $param_string_comparison .= "&showColumns=nb_visits,nb_uniq_visitors,nb_pageviews,nb_actions";

    $comparison_data = omsk_fetch_matomo_api($param_string_comparison);

    return rest_ensure_response(array(
        'current' => $data,
        'comparison' => is_wp_error($comparison_data) ? null : $comparison_data,
        'post_url' => $post_url,
    ));
}

/**
 * Get comparison date range for trend calculation
 */
function omsk_get_comparison_date($date) {
    // Simple date comparison - previous period
    $mapping = array(
        'last7' => 'previous7',
        'last14' => 'previous14',
        'last28' => 'previous28',
        'last90' => 'previous90',
    );

    return isset($mapping[$date]) ? $mapping[$date] : 'previous7';
}
