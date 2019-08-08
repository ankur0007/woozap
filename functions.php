<?php


function woozap_sortDates( $a, $b ) {
    return strtotime($a["date"]) - strtotime($b["date"]);
}



function woozap_curl($action,$parameters=array(),$url=''){

$parameters = http_build_query($parameters);

if(empty($url)){
return false;
}
//$url = $url.'?'.$parameters;
/*$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);

curl_close ($ch);*/
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
/*echo '<pre>';
print_r($server_output_body);
die;*/
if(substr($server_output, 0, 5) == "<?xml") {
    $server_output = simplexml_load_string($server_output);
}else{
    $server_output = json_decode($server_output);
}

return $server_output;
}


function woozap_messages($log){
	$list='';
	if(!empty($log)){
		
		if(!empty($log['success'])){
		
		foreach ($log['success'] as $msg) {
			$list.='<p style="text-align:center;color:green">'.$msg.'</p><br />';
		}
	}
	if(!empty($log['error'])){
		
		foreach ($log['error'] as $msg) {
			$list.='<p style="text-align:center;color:red">'.$msg.'</p><br />';
		}
	}
	
}
return $list;
}


function woozap_view($file,$attr_arr=array()){


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