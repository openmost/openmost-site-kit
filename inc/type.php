<?php

function msk_get_post_type_details($post_type)
{
    $type = get_post_type_object($post_type);

    return array(
        'name' => $type->name,
        'label' => $type->label,
        'label_singular' => $type->labels->singular_name,
        'label_plural' => $type->labels->name,
        'description' => $type->description,
    );
}