<?php

defined( 'ABSPATH' ) || exit;

function wpflyleads_sortDates( $a, $b ) {
    return strtotime($a["date"]) - strtotime($b["date"]);
}

function wpflyleads_get_servers($selected=''){
	$servers='';
	$servers_selector=[
		''	=> 'Select a Server',
		'integromat' => 'Integromat',
		'zapier'    => 'Zapier',
		'pabbly'    => 'Pabbly'
	];

	if(!empty($servers_selector)){
		foreach ($servers_selector as $value => $name) {
			$servers.='<option '.($selected == $value ? 'selected' : '').' value="'.$value.'">'.$name.'</option>';
		}
	}
	return $servers;
}

function wpflyleads_update_options_prefix($from,$to){
global $wpdb;
$db_version = get_option( 'wpflyleads_version' );
if(!empty($db_version)){
return;
}

$old_options = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` LIKE '%$from%' ORDER BY `option_id` DESC");

if(!empty($old_options)){
	foreach ($old_options as $option) {
		$name = str_replace($from, $to, $option->option_name);
		update_option($name,$option->option_value);
		delete_option($option->option_name);
	}
}
}

function wpflyleads_get_webhook_urls(){
	global $wpflyleads;
	$data=[];
	$db_version = get_option( 'wpflyleads_version' );
	$get_all_urls= maybe_unserialize( get_option('wpflyleads_connection_url') );


	$current_version = $wpflyleads->wpflyleads_version;

	if(empty($db_version) || !is_array($get_all_urls)){ //old version 2.2 or less

		$fields_count = get_option('wpflyleads_connection_url_count');
		if(empty($fields_count)){
            $fields_count = 1;
        }
		for ($i=1; $i <= (int)$fields_count; $i++) { 

            $url = get_option('wpflyleads_connection_url'.$i);
            if($i==1 && empty($url)){
                $url = get_option('wpflyleads_connection_url');
            }
            $name="connection #".rand(1000,10000);
            $slug=sanitize_title($name);
            $data[$i][]= $url;
            $data[$i][]= $name;
            $data[$i][]= $slug;
        } 
	}else{
		$data = $get_all_urls;
	}

	return $data;
}

function wpflyleads_curl($action,$parameters=array(),$url='',$server=''){

$parameters = http_build_query($parameters);

if(empty($url)){
return false;
}

$args = array(
    'body' => $parameters,
    'timeout' => '5',
    'redirection' => '5',
    'httpversion' => '1.0',
    'blocking' => true,
    'headers' => array(),
    'cookies' => array()
);
$server_output_all = wp_remote_post( $url, $args );
$server_output = wp_remote_retrieve_body( $server_output_all );



if($server == 'integromat'){	
$server_output = (object) ['status' => (strtolower($server_output) == 'accepted' ? 'success' : 'failure')];
}
elseif ($server == 'zapier') {
	if(substr($server_output, 0, 5) == "<?xml") {
		$server_output = simplexml_load_string($server_output);
	}elseif(json_decode($server_output)){
		$server_output = json_decode($server_output);
	}
}elseif ($server == 'pabbly') {
	$server_output = (array)json_decode($server_output);
	unset($server_output['message']);
	$server_output = (object)$server_output;
	
}
 
//file_put_contents(ABSPATH.'/wpflyleadslog.txt', json_encode( $server_output ));
return $server_output;
}


function wpflyleads_messages($log){
	$list='';
	if(!empty($log)){
		
		if(!empty($log['success'])){
		
		foreach ($log['success'] as $msg) {
			$list.='<p style="text-align:center;color:green">'.esc_html( $msg ).'</p><br />';
		}
	}
	if(!empty($log['error'])){
		
		foreach ($log['error'] as $msg) {
			$list.='<p style="text-align:center;color:red">'.esc_html( $msg ).'</p><br />';
		}
	}
	
}
return $list;
}


function wpflyleads_view($file,$attr_arr=array()){


if(empty($file)){
return false;
}

if(!empty($attr_arr) && is_array($attr_arr)){
extract($attr_arr);
}

$extension='.php';
$twem_dirpath = plugin_dir_path(__FILE__).'view/';

if(file_exists($twem_dirpath.$file.$extension)){
include $twem_dirpath.$file.$extension;
}
}
?>