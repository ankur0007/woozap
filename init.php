<?php
/**
 * @package WOOZAP (Woocommerce + Zapier Integration)
 * @version 2.2
 */
/*
Plugin Name: WOOZAP (Woocommerce + Zapier Integration)
Description: WooZap (woocommerce + zapier integration) allows admin to integrate woocommerce with zapier api and fetch all the orders info in zapier and transfer to thousands of another zaps.
Author: Ankur Vishwakarma
Author URI: https://avcodelab.com
Version: 2.2
Author Email : ankurvishwakarma54@yahoo.com 
Domain : woozap
*/
if (!class_exists('WooZap') && class_exists('woocommerce')) {
class WooZap {

var $woozap_dirpath;
var $woozap_urlpath;


function __construct(){

$this->woozap_urlpath=plugin_dir_url( __FILE__ );
$this->woozap_dirpath=plugin_dir_path(__FILE__);
register_activation_hook( __FILE__, array($this,'woozap_setup'));
add_action( 'admin_enqueue_scripts', array($this,'load_backend_js_css_files'),10,2);
$this->includes();
}


//Include files
function includes(){
	
	require_once $this->woozap_dirpath.'functions.php';
	require_once $this->woozap_dirpath.'controller.php';
}

// Load JS

function load_backend_js_css_files($hook){
	$current_screen = get_current_screen();
	$post_type = $current_screen->post_type;
	$filters=array(
		'woocommerce_page_wc-settings',
	);
	if(!in_array($hook,$filters) && $post_type != 'shop_order' ){
		return false;
	}

wp_enqueue_style( 'dashicons' );	

wp_enqueue_style( 'woozap-css', $this->woozap_urlpath . 'assets/admin/css/woozap.css'  );
wp_enqueue_script( 'woozap-js', $this->woozap_urlpath .  'assets/admin/js/woozap.js' , array('jquery'));
wp_localize_script( 'woozap-js', 'woozap_ajax',array( 
	'ajaxurl' => admin_url( 'admin-ajax.php' )
	) );

}


function woozap_setup(){
	if (session_status() == PHP_SESSION_NONE) {
    session_start();
	}
	$this->woozap_dependency_checker();
	$this->woozap_capabilities();
	//$this->woozap_tables();
}

function woozap_dependency_checker(){
	if (!is_plugin_active('woocommerce/woocommerce.php') )
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function woozap_capabilities(){

	$role = get_role( 'administrator' );
	$role->add_cap( 'woozap_admin');
	
}


} //class
global $woozap;
$woozap=new WooZap;
} //if