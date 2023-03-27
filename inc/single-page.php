<?php

function msk_get_single_page_details(){
    $object = get_queried_object();

    return array(
        'id' => get_the_ID(),
        'url' => get_the_permalink(),
        'post_name' => $object->post_name,
        'post_title' => $object->post_title,
        'post_excerpt' => $object->post_excerpt,
        'post_status' => $object->post_status,
        'comment_status' => $object->comment_status,
        'post_date' => $object->post_date,
        'post_date_gmt' => $object->post_date_gmt,
        'post_modified' => $object->post_modified,
        'post_modified_gmt' => $object->post_modified_gmt,
        'comment_count' => $object->comment_count,

        'post_type' => msk_get_post_type_details(get_post_type()),
        'author' => msk_get_author_details(get_the_author()),
        'taxonomies' => msk_get_terms_per_tax()
    );
}