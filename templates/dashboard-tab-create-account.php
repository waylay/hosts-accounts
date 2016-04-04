<?php

	$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
	$user_login = '';
	$user_email = '';
	$display_errors = '';
	$display_message = '';
	if ( $http_post ) {

		$user_login = isset( $_POST['user_login'] ) ? $_POST['user_login'] : '';
		$user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
		$errors = register_new_user($user_login, $user_email);
		
		if ( !empty( $errors ) ) {
			if ( is_wp_error($errors) ) {
				foreach ( $errors as $error ) {
					foreach ( $error as $error_message ) {
						$display_errors .= '<p class="affwp-error">' . $error_message[0] . "</p>";
					}
				}
			} else {

				$new_user = new WP_User($errors);
				$new_user->set_role('customer');
				$display_message = '<p class="affwp-notice">Congratulations, a new affiliated customer has been created. He will receive an email to complete the registration process.</p><br class="clear" />';
				$user_login = '';
				$user_email = '';

				add_user_meta( $new_user->ID, '_affiliate_host_id', $_POST['_affiliate_host_id'] );
			}
		}
	}

?>

<div id="affwp-affiliate-dashboard-create-account" class="affwp-tab-content">
<h4>Register a New Affiliated Customer</h4>

<?php 
	if ( ! empty( $display_errors ) ) {
		echo '<div class="affwp-errors">' . apply_filters( 'login_errors', $display_errors ) . "</div>\n";
	}
	if ( ! empty( $display_message ) ) {
		echo apply_filters( 'login_message', $display_message );

	}
 ?>
	<form name="registerform" id="registerform" action="" method="post" novalidate="novalidate">
		<p>
			<label for="user_login"><?php _e('Username') ?><br />
			<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr(wp_unslash($user_login)); ?>" size="20" required /></label>
		</p>
		<p>
			<label for="user_email"><?php _e('Email') ?><br />
			<input type="email" name="user_email" id="user_email" class="input" value="<?php echo esc_attr( wp_unslash( $user_email ) ); ?>" size="25" required /></label>
		</p>
		<input type="hidden" name="_affiliate_host_id" value="<?php echo affwp_get_affiliate_id(); ?>"></input>
		<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Create a new Customer Account'); ?>" /></p>
	</form>
</div> 
