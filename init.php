<?php
/**
 * @package WP AVCL Automation Helper (formerly WPFlyLeads) 
 * @version 3.4
 */
/*
Plugin Name: WP AVCL Automation Helper (formerly WPFlyLeads)
Description: WP AVCL Automation Helper supports Zapier, Make (formerly Integromat) and Pabbly to send Orders from WooCommerce, submissions from the Contact Form 7 and entries from the Gravity Forms to thousands of other platforms.
Author: Ankur Vishwakarma & Ankit Vishwakarma
Author URI: https://ankurvishwakarma.com
Version: 3.4
Author Email : ankurvishwakarma54@yahoo.com
Domain : wpflyleads
*/

defined( 'ABSPATH' ) || exit;

add_filter('plugin_action_links_'.plugin_basename(__FILE__),'wpflyleads_settings_link');

if ( ! class_exists( 'woocommerce' ) ) {
	add_action( 'admin_notices', 'wpflyleads_dependency_notice');
	add_action( "after_plugin_row_".plugin_basename(__FILE__), 'wpflyleads_dependency_notice_row',9999);
	return;
}

function wpflyleads_dependency_notice() {
	$message = _x( '<strong>WPFlyLeads</strong> requires WooCommerce to be activated to work.', 'admin notice', 'wpflyleads' );
	printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $message ) );	
}

function wpflyleads_dependency_notice_row() {

	$message = __( '<strong>WPFlyLeads</strong> requires WooCommerce to be activated to work.', 'wpflyleads' );
	echo '<tr class="update-message notice inline notice-warning notice-alt"><td></td><td>'.( $message ).'</td></tr>';
}

function wpflyleads_settings_link($links){

	$settings_pages = [
		'wpflyleads-settings' => __('WooCommerce Settings','wpflyleads'),
		'gf_edit_forms'	=> __('Gravity Forms Settings (Create a form first)','wpflyleads'),
		'wpcf7'	=>  __('Contact Form 7 Settings (Create a form first)','wpflyleads')
	];
	$style = '
	<style>
	.wpflyleads-settings-link {
		width: 20px !important;
		height: 20px !important;
		padding: 0 !important;
		float: none !important;
	  }
	  
	  .wpflyleads-settings-link:before {
		background-color: unset !important;
		border: unset !important;
		box-shadow: none !important;
		font-size: 12px !important;
	  }
	</style>
	';
	foreach( $settings_pages as $link => $label ){
		$links[] = '<br><span class="dashicons dashicons-arrow-right-alt2 wpflyleads-settings-link"></span><a href="' . admin_url( 'admin.php?page='.$link ) .'">' . $label . '</a>';
	}
	echo $style;
	return $links;
}

if ( ! class_exists('WPFlyLeads') && class_exists( 'woocommerce' ) ) {
class WPFlyLeads {

var $wpflyleads_dirpath;
var $wpflyleads_urlpath;


function __construct(){
$this->wpflyleads_version='3.4';
$this->wpflyleads_urlpath=plugin_dir_url( __FILE__ );
$this->wpflyleads_dirpath=plugin_dir_path(__FILE__);
$this->includes();
add_action( 'admin_enqueue_scripts', [$this,'load_backend_js_css_files'],10,2);
add_action( 'activated_plugin', [$this,'wpflyleads_activation_redirect'] );
add_action( 'admin_menu', [$this,'wpflyleads_setting_page']);
register_activation_hook( __FILE__, [$this,'wpflyleads_setup']);
add_action( 'plugins_loaded', [$this,'wpflyleads_check_for_upgrade'] ); //incase if registration activation hook didn't trigger


}

function wpflyleads_check_for_upgrade(){
	$db_version = get_option( 'wpflyleads_version' );
	
	if(empty($db_version) || version_compare($this->wpflyleads_version, '2.4', '>')){
		$this->wpflyleads_setup();
	}
}
//Include files
function includes(){
	require_once $this->wpflyleads_dirpath.'functions.php';
	require_once $this->wpflyleads_dirpath.'woo-controller.php';
	require_once $this->wpflyleads_dirpath.'cf7-controller.php';
	require_once $this->wpflyleads_dirpath.'gf-controller.php';
}

//WP-Admin Menu
function wpflyleads_setting_page(){
   
     add_submenu_page(
		 'woocommerce',
       __('WPFlyLeads settings','wpflyleads'),
       __('WPFlyLeads settings','wpflyleads'), 
       'manage_options', 
       'wpflyleads-settings', 
       array($this,'wpflyleads_settings_callback')
     );
  
}

function wpflyleads_settings_callback(){
	do_action('wpflyleads_settings_page');
}

// Load JS

function load_backend_js_css_files($hook){

	
	
	$current_screen = get_current_screen();
	$post_type = $current_screen->post_type;
	
	$bypass_hooks = [
		'woocommerce_page_wpflyleads-settings',
		'toplevel_page_wpcf7',
		'toplevel_page_gf_edit_forms'

	];
	$bypass_post_types = [
		'shop_order'
	];

	$is_gf_settings_page = (isset($_GET['page']) && isset($_GET['subview']) && $_GET['page'] === 'gf_edit_forms' && $_GET['subview'] === 'wpflyleads_settings' ? true : false);
	
	if(
		!in_array($hook, $bypass_hooks)
		&&
		!in_array($post_type, $bypass_post_types)
		&& !$is_gf_settings_page
	 )
	{
		return;
	}

	
	$wc_get_status=(function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array());
	$get_status='';
	$get_all_status_array=get_option('wpflyleads_all_status');
	
	
	
	foreach ($wc_get_status as $key => $value) {
		 $get_status.="<option value=".$key.">".$value."</option>";   
	}

	// echo '<pre>';  print_r($get_status);  die;
	
wp_enqueue_style( 'dashicons' );	
wp_enqueue_style( 'wpflyleads-select2-css', $this->wpflyleads_urlpath . 'assets/admin/css/select2.min.css'  );
wp_enqueue_script( 'wpflyleads-select2-js', $this->wpflyleads_urlpath .  'assets/admin/js/select2.min.js' , array('jquery'));
wp_enqueue_style( 'wpflyleads-css', $this->wpflyleads_urlpath . 'assets/admin/css/wpflyleads.css'  );
wp_enqueue_script( 'wpflyleads-js', $this->wpflyleads_urlpath .  'assets/admin/js/wpflyleads.js' , array('jquery'));
wp_localize_script( 'wpflyleads-js', 'wpflyleads_ajax',array( 
	'ajaxurl' => admin_url( 'admin-ajax.php' ),
	'get_status'=>$get_status,
	'get_all_status_array'=>$get_all_status_array,
	'get_servers' => wpflyleads_get_servers()
	 
	) );

}


function wpflyleads_setup(){
	/* if (session_status() == PHP_SESSION_NONE) { //disabled
    session_start();
	} */
	wpflyleads_update_options_prefix('woozap','wpflyleads'); //from,to
	$this->wpflyleads_capabilities();
	update_option( 'wpflyleads_version', $this->wpflyleads_version );
}

function wpflyleads_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=wpflyleads-settings' ) ) );
    }
}

function wpflyleads_capabilities(){
	$role = get_role( 'administrator' );
	$role->add_cap( 'wpflyleads_admin');
}

} //class
global $wpflyleads;
$wpflyleads=new WPFlyLeads;
} //if