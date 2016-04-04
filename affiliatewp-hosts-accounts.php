<?php
/**
 * Plugin Name: AffiliateWP - Hosts Accounts
 * Plugin URI: http://webcodesigner.com
 * Description: Hosts accounts with customers accounts linked on commission basis
 * Author: Cristian Ionel
 * Author URI: http://webcodesigner.com
 * Version: 1.0
 * Text Domain: affiliatewp-hosts-accounts
 * Domain Path: languages
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// affwp_is_affiliate
// affwp_is_active_affiliate
// affwp_get_affiliate_id



class AffiliateWP_Hosts_Accounts {


	public function __construct() {

		$affiliate_wp = function_exists( 'affiliate_wp' ) ? affiliate_wp() : '';
		add_action('init', array($this,'set_affiliate_id_cookie'));

		add_action( 'wp_login', array($this,'affwpcf_login_redirect'), 10, 2 );
		add_filter( 'logout_url', array($this,'affwpcf_logout_redirect'), 10, 2 );
		add_filter( 'affwp_register_required_fields', array($this,'affwp_custom_make_url_not_required') );
		add_filter( 'affwp_template_paths', function( $file_paths) {
			$file_paths[90] = plugin_dir_path( __FILE__ ) . '/templates';
			return $file_paths; 
		} );

		remove_shortcode( 'affiliate_area', array( $affiliate_wp, array($this,'affiliate_area') ) );
		add_shortcode( 'affiliate_area', array($this, 'affwp_custom_move_login_above_register') );

		add_action( 'user_register', array($this,'add_affiliate_id_as_user_meta'), 10, 1 );
		add_action( 'wp_enqueue_scripts', array($this, 'affiliatewp_hosts_accounts_styles') );	

		add_filter( 'manage_edit-shop_order_columns' , array($this, 'affwp_custom_shop_order_column') );
		add_action( 'manage_shop_order_posts_custom_column' , array($this, 'affwp_custom_shop_affiliate_column'), 10, 2 );	


	}


	public function affiliatewp_hosts_accounts_styles() {
	    wp_enqueue_style( 'affiliatewp_hosts_accounts_styles', plugin_dir_url( __FILE__ ) . '/style.css' );
	}
	public function add_affiliate_id_as_user_meta( $user_id ) {
		$affiliate_id = affwp_get_affiliate_id();
	    if ( $affiliate_id ) {
	        update_user_meta($user_id, 'affiliate_id', $affiliate_id);
	    }

	}

	public function set_affiliate_id_cookie() {

		$user = wp_get_current_user();
		$key = '_affiliate_host_id';
		$affiliate_id = get_user_meta( $user->ID, $key, true );

	    if( $affiliate_id && affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id ) ){
	    	affiliate_wp()->tracking->set_affiliate_id( $affiliate_id );
	    }
	}

	public function affwp_custom_shop_order_column($columns)
	{
		// global $woocommerce;
		$new_columns = array();

		foreach($columns as $key => $title) {
			if ($key=='billing_address') {
	   			// in front of the Billing column
		    	$new_columns['affiliate']  = __( 'Affiliate', 'woocommerce' );
		   	}
			$new_columns[$key] = $title;
		}

	    return $new_columns ;
	}

	public function affwp_custom_shop_affiliate_column( $column ) {
		global $post, $woocommerce, $the_order;

		switch ( $column ) {

		    case 'affiliate' :
		    	$affiliate = affiliate_wp()->referrals->get_by( 'reference', $the_order->id, 'woocommerce' );
		    	if( is_object($affiliate) ){
		    		$affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $affiliate->affiliate_id );
		    		echo $affiliate_name;
		    	} else {
		    		echo "None";
		    	}
		    	
				break;

		}
	}
	
	/**
	 * Redirect users when logging in via wp-login.php (aka wp-admin)
	 * This also includes /account or /account/affiliates
	 */
	public function affwpcf_login_redirect( $user_login, $user ) {
		$user_id = $user->ID;
		// skip admins
		if ( in_array( 'administrator', $user->roles ) ) {
			return;
		}
		// skip EDD pages or if we came from the checkout
		if ( function_exists( 'edd_is_checkout' ) ){
			if ( ( edd_is_checkout() || edd_is_success_page() ) || wp_get_referer() == edd_get_checkout_uri() ) {
				return;
			}
		}
		
		// Affiliates should go to affiliate area
		if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate( $user_id ) ) {
			$redirect = affiliate_wp()->login->get_login_url();
		}
		// Customers should go to account page
		else {
			$redirect = site_url( '/' );
		}
		wp_redirect( $redirect ); exit;
	}

	/**
	 * Redirect affiliates and customers when they log out of WordPress
	 * By default, a user is sent to the wp-login.php?loggedout=true page
	 *
	 * Affiliates are logged out to the affiliate dashboard login screen
	 * Customers (subscribers) are logged out and redirected to the account login page
	 */
	public function affwpcf_logout_redirect( $logout_url, $redirect ) {
		if ( current_user_can( 'manage_options' ) ) {
			// skip admins
			return $logout_url;
		}
		if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate() ) {
			// redirect affiliates to affiliate login page
			$redirect = affiliate_wp()->login->get_login_url();
		} else {
			// Customers should go to account login page
			$redirect = site_url( '/' );
		}
		$args = array( 'action' => 'logout' );
		if ( ! empty( $redirect ) ) {
			$args['redirect_to'] = urlencode( $redirect );
		}
	    return add_query_arg( $args, $logout_url );
	}

	public function affwp_custom_make_url_not_required( $required_fields ) {
		unset( $required_fields['affwp_user_url'] );
		return $required_fields;
	}

	public function affwp_custom_move_login_above_register() {
		ob_start();
		if ( is_user_logged_in() && affwp_is_affiliate() ) {
			affiliate_wp()->templates->get_template_part( 'dashboard' );
		} elseif( is_user_logged_in() && affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
			affiliate_wp()->templates->get_template_part( 'register' );
		} else {
			if ( ! is_user_logged_in() ) {
				affiliate_wp()->templates->get_template_part( 'login' );
			}
			if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
				affiliate_wp()->templates->get_template_part( 'register' );
			} else {
				affiliate_wp()->templates->get_template_part( 'no', 'access' );
			}
		}
		return ob_get_clean();
	}
}
if ( function_exists( 'affwp_is_affiliate' ) ){
	new AffiliateWP_Hosts_Accounts;
}