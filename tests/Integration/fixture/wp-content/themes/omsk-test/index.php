<!doctype html>
<html <?php language_attributes(); ?>>
<head><?php wp_head(); ?></head>
<body><?php wp_body_open(); ?><main><?php echo is_404() ? 'not found' : 'content'; ?></main><?php wp_footer(); ?></body>
</html>
