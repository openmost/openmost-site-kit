<?php

function msk_get_author_details($author_slug)
{
    $author_id = get_the_author_meta('ID');
    $author = get_userdata($author_id);

    return array(
        'id' => get_the_author_meta('ID'),
        'nickname' => get_the_author_meta('nickname'),
        'display_name' => get_the_author_meta('display_name'),
        'first_name' => get_the_author_meta('first_name'),
        'last_name' => get_the_author_meta('last_name'),
        'description' => get_the_author_meta('description'),
    );
}