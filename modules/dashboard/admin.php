<?php

function omsk_dashboard_page()
{
    add_submenu_page(
        'openmost-site-kit', // parent slug
        __('Dashboard', 'openmost-site-kit'), // page title
        __('Dashboard', 'openmost-site-kit'), // menu title
        'edit_posts', // capability - allows editors and above
        'openmost-site-kit', // menu slug
        'omsk_view_dashboard', // callback function to display the options form
        1
    );
}

add_action('admin_menu', 'omsk_dashboard_page');

function omsk_view_dashboard()
{
    echo '<div class="wrap"><div id="omsk-dashboard-root"></div></div>';
}

// Legacy dashboard notice removed - now handled in React
// See src/pages/Dashboard/index.js