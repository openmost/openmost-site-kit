<?php
$options    = get_option( 'osk-settings' );
$host       = isset( $options['osk-matomo-host-field'] ) ? $options['osk-matomo-host-field'] : '';
$id_site    = isset( $options['osk-matomo-idsite-field'] ) ? $options['osk-matomo-idsite-field'] : '';
$token_auth = isset( $options['osk-matomo-token-auth-field'] ) ? $options['osk-matomo-token-auth-field'] : '';
?>
<div id="osk-dashboard"
     data-host="<?php echo $host; ?>"
     data-id-site="<?php echo $id_site; ?>"
     data-token-auth="<?php echo $token_auth; ?>">
    <Dashboard/>
</div>