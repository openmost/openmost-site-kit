<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
	<form method="post" action="options.php" novalidate="novalidate">
		<?php settings_fields('osk-settings-group'); ?>
		<?php do_settings_sections('osk-settings'); ?>
		<?php submit_button(); ?>
	</form>
</div>