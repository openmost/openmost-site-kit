<?php

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}

// Features
require_once plugin_dir_path( __FILE__ ) . 'inc/classic-code.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/tag-manager-code.php';

