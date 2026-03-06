<?php
/**
 * Automatic Annotations Module
 *
 * Creates annotations in Matomo when posts are published.
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into post status transitions to create annotations.
add_action( 'transition_post_status', 'omsk_maybe_create_annotation', 10, 3 );

/**
 * Create annotation when a post is published.
 *
 * @since 1.0.0
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 * @return void
 */
function omsk_maybe_create_annotation( $new_status, $old_status, $post ) {
    // Only process when transitioning to 'publish' from another status.
    if ( 'publish' !== $new_status || 'publish' === $old_status ) {
        return;
    }

    $options = get_option( 'omsk-settings', array() );

    if ( empty( $options ) ) {
        return;
    }

    // Check if automatic annotations are enabled.
    $enable_auto_annotations = ! empty( $options['omsk-matomo-enable-auto-annotations-field'] );

    if ( ! $enable_auto_annotations ) {
        return;
    }

    // Get the list of post types to annotate.
    $annotation_post_types = isset( $options['omsk-matomo-annotation-post-types-field'] )
        ? (array) $options['omsk-matomo-annotation-post-types-field']
        : array();

    // Check if this post type should be annotated.
    if ( ! in_array( $post->post_type, $annotation_post_types, true ) ) {
        return;
    }

    // Get required settings.
    $host       = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site    = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $token_auth = isset( $options['omsk-matomo-token-auth-field'] ) ? $options['omsk-matomo-token-auth-field'] : '';

    // Validate required settings.
    if ( empty( $host ) || empty( $id_site ) || empty( $token_auth ) ) {
        return;
    }

    // Get annotation format.
    $format = isset( $options['omsk-matomo-annotation-format-field'] )
        ? $options['omsk-matomo-annotation-format-field']
        : 'New {post_type} published: "{title}"';

    // Get post type label.
    $post_type_obj   = get_post_type_object( $post->post_type );
    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $post->post_type );

    // Get author.
    $author = get_the_author_meta( 'display_name', $post->post_author );

    // Get post URL.
    $url = get_permalink( $post->ID );

    // Build annotation note by replacing variables.
    $note = str_replace(
        array( '{post_type}', '{title}', '{url}', '{author}' ),
        array( $post_type_label, $post->post_title, $url, $author ),
        $format
    );

    // Create the annotation.
    omsk_create_matomo_annotation( $host, $id_site, $token_auth, $note );
}

/**
 * Create an annotation in Matomo.
 *
 * @since 1.0.0
 * @param string $host       Matomo host URL.
 * @param string $id_site    Site ID.
 * @param string $token_auth Auth token.
 * @param string $note       Annotation text.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function omsk_create_matomo_annotation( $host, $id_site, $token_auth, $note ) {
    $url = trailingslashit( $host ) . 'index.php';

    $body_params = array(
        'module'     => 'API',
        'method'     => 'Annotations.add',
        'format'     => 'JSON',
        'idSite'     => absint( $id_site ),
        'date'       => gmdate( 'Y-m-d' ),
        'note'       => sanitize_text_field( $note ),
        'token_auth' => $token_auth,
    );

    $headers = array(
        'Content-Type'  => 'application/x-www-form-urlencoded',
        'Authorization' => 'Bearer ' . $token_auth,
    );

    // Non-blocking request: timeout 0.01s so publishing is not delayed.
    $response = wp_remote_post(
        $url,
        array(
            'timeout'  => 0.01,
            'blocking' => false,
            'headers'  => $headers,
            'body'     => $body_params,
        )
    );

    if ( is_wp_error( $response ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo Annotation Error: ' . $response->get_error_message() );
        }
        return $response;
    }

    return true;
}
