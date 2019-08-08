<?php
/**
 * WooZap Controller
 */
class WooZapController 
{
	
	function __construct()
	{	
		add_filter( 'woocommerce_general_settings', array($this,'woozap_settings'));
		//add_action('woocommerce_thankyou', array($this,'woozap_trigger'), 10, 1);
        add_action('woocommerce_order_status_changed',array($this,'woozap_trigger'), 10, 3);
        //ajax
        add_action('wp_ajax_trigger_woozap_ajax',array($this,'trigger_woozap_ajax'));

        //order table new column
        //add_filter( 'manage_edit-shop_order_columns', array($this,'woozap_new_order_column'));
        //add_filter( 'manage_shop_order_posts_custom_column', array($this,'woozap_new_order_column_summary'), 20 );
        
    }


    //New Order Column
    function woozap_new_order_column_summary($column){
        global $post;
        
        if ( 'woozap_triggered_summary' === $column ) {

            $fields_count = get_option('woozap_zapier_url_count'); 
            if(empty($fields_count)){
                $fields_count = 1;
            }

            for ($i=1; $i <= (int)$fields_count; $i++) { 
                
                $woozap_triggered_summary = get_post_meta($post->ID,'woozap_triggered_summary_'.$i,true);
                $woozap_triggered = get_post_meta($post->ID,'woozap_triggered_'.$i,true);
                if(!empty($woozap_triggered_summary)){
                    if(!empty($woozap_triggered) && $woozap_triggered != 'false'){
                        echo '<strong>Triggered Url '.$i.'</strong> : '.$woozap_triggered.'<br>';
                    }
                    else{
                        echo '<strong>Triggered</strong> : false';
                    }
                    
                    echo '<div><span id="woozap_sp" class="woozap_sp" style="display:none;">';
                    echo '<strong>ID</strong> : '.$woozap_triggered_summary->id.'<br>';
                    //echo '<strong>Attempt</strong> : '.$woozap_triggered_summary->attempt.'<br>';
                    //echo '<strong>Request ID</strong> : '.$woozap_triggered_summary->request_id.'<br>';
                    echo '</span>';
                    echo '<a href="#" class="woozap_sp_click button button-small">Read More</a><br><div>';
                    
                }    
            } 
            
            
        }   
    }

    function woozap_new_order_column( $columns ) {
    $columns['woozap_triggered_summary'] = 'Woozap Trigger Summary';
    return $columns;

    }

	//WooZap Trigger
	function woozap_trigger($order_id,$old_status,$new_status){
		$order_info=array();
		if ( ! $order_id )
        return;

        if($old_status == $new_status){
            return;
        }
        //$fields = WC()->checkout()->checkout_fields;
       
    	$countries = WC()->countries->get_countries();
    	$states = WC()->countries->get_states();
    	
    	$order = wc_get_order( $order_id );
    	$order_data = $order->get_data();

        

    	$order_info['id'] = $order_data['id'];
    	$order_info['status'] = $order_data['status'];
    	$order_info['currency'] = $order_data['currency'];
    	$order_info['date_created'] = $order->get_date_created()->date_i18n("Y-m-d H:i:a");
    	$order_info['discount_total'] = $order_data['discount_total'];
    	$order_info['discount_tax'] = $order_data['discount_tax'];
    	$order_info['shipping_total'] = $order_data['shipping_total'];
    	$order_info['shipping_tax'] = $order_data['shipping_tax'];
    	$order_info['order_total'] = $order_data['total'];
    	$order_info['order_total_tax'] = $order_data['total_tax'];
    	$order_info['customer_id'] = $order_data['customer_id'];
    	$order_info['order_key'] = $order_data['order_key'];
    	$order_info['billing_firstname'] = $order_data['billing']['first_name'];
    	$order_info['billing_lastname'] = $order_data['billing']['last_name'];
    	$order_info['billing_company'] = $order_data['billing']['company'];
    	$order_info['billing_address_1'] = $order_data['billing']['address_1'];
    	$order_info['billing_address_2'] = $order_data['billing']['address_2'];
    	$order_info['billing_city'] = $order_data['billing']['city'];
    	$order_info['billing_state'] = $states[$order_data['billing']['country']][$order_data['billing']['state']];
    	$order_info['billing_postcode'] = $order_data['billing']['postcode'];
    	$order_info['billing_country'] = $countries[ $order_data['billing']['country'] ];
    	$order_info['billing_email'] = $order_data['billing']['email'];
    	$order_info['billing_phone'] = $order_data['billing']['phone'];
    	$order_info['shipping_firstname'] = $order_data['shipping']['first_name'];
		$order_info['shipping_lastname'] = $order_data['shipping']['last_name'];
		$order_info['shipping_company'] = $order_data['shipping']['company'];
		$order_info['shipping_address_1'] = $order_data['shipping']['address_1'];
		$order_info['shipping_address_2'] = $order_data['shipping']['address_2'];
		$order_info['shipping_city'] = $order_data['shipping']['city'];
		$order_info['shipping_state'] = $states[$order_data['shipping']['country']][$order_data['shipping']['state']];
		$order_info['shipping_postcode'] = $order_data['shipping']['postcode'];
		$order_info['shipping_country'] = $countries[$order_data['shipping']['country']];
		$order_info['payment_method'] = $order_data['payment_method'];
		$order_info['payment_method_title'] = $order_data['payment_method_title'];
		$order_info['transaction_id'] = $order_data['transaction_id'];
		$order_info['customer_ip_address'] = $order_data['customer_ip_address'];
		$order_info['customer_user_agent'] = $order_data['customer_user_agent'];
		$order_info['customer_note'] = $order_data['customer_note'];
		$order_info['date_completed'] = $order_data['date_completed'];
		$order_info['date_paid'] = $order_data['date_paid'];

        if(!empty($order_data['meta_data'])){
            foreach ($order_data['meta_data'] as $index => $custom_field) {
                $order_info[$custom_field->key] = $custom_field->value; 
                
            }
        }

		$o=1;
		foreach ($order->get_items() as $key => $item) {
			$item = $item->get_data();
			$order_info['item_'.$o] = array(
						'ID' => $item['product_id'],
						'product_name' => $item['name'],
						'qty' => $item['quantity'],
						'type' => (empty($item['variation_id']) || ($item['variation_id'] <= 0) ? 'simple' : 'variable'),
						'price' => $item['total'],
						'currency' => get_woocommerce_currency_symbol(),
						'thumbnail' => get_the_post_thumbnail_url( $item['product_id'], 'full' ),

					);
            
            

            if(!empty($item['meta_data'])){
                foreach ($item['meta_data'] as $index => $custom_field) {
                    $order_info['item_'.$o][$custom_field->key] = $custom_field->value; 
                    
                }
            }

			$o++;
		}

        $coupons_used=array();
        if(!empty($order->get_used_coupons())){
            foreach( $order->get_used_coupons() as $coupon_name ){

            // Retrieving the coupon ID
            $coupon_post_obj = get_page_by_title($coupon_name, OBJECT, 'shop_coupon');
            $coupon_id = $coupon_post_obj->ID;

            // Get an instance of WC_Coupon object in an array(necesary to use WC_Coupon methods)
            $coupons_obj = new WC_Coupon($coupon_id);

            // Now you can get type in your condition
            $coupon_type = $coupons_obj->get_discount_type();
            $coupon_amount = $coupons_obj->get_amount();
            $coupons_used[] = array('name'=>$coupon_name,'type'=>$coupon_type,'amount'=>$coupon_amount);

            
            } //coupons
        }
        
        $order_info['coupons'] = $coupons_used;

        
		$fields_count = get_option('woozap_zapier_url_count'); 
        if(empty($fields_count)){
            $fields_count = 1;
        }

        for ($i=1; $i <= (int)$fields_count; $i++) { 

            $url = get_option('woozap_zapier_url'.$i);
            if($i==1 && empty($url)){
                $url = get_option('woozap_zapier_url');
            }
            if(empty($url)){
                continue;
            }
            $zap_info = woozap_curl('new_order',$order_info,$url);
            if($zap_info->status=='success'){
            $note = __(sprintf("Zapier url %d triggered with status : %s and order status changed from %s to %s",$i,$zap_info->status,$old_status,$new_status));
            $order->add_order_note( $note );
            $order->save();
            }
            
            /*$woozap_status = get_post_meta($order_id,'woozap_triggered_'.$i,true);
            if(empty($woozap_status) || $woozap_status == 'false'){
            $zap_info = woozap_curl('new_order',$order_info,$url);
            $order->update_meta_data( 'woozap_triggered_summary_'.$i, $zap_info);
            $order->update_meta_data( 'woozap_triggered_'.$i, $zap_info->status );
            $order->save();
            } */   
        } //for
		
    	
    	/*echo '<pre>';
    	//print_r($order_data->);
    	print_r($order_info);
    	die;*/
	}

	//WooZap Settings
	function woozap_settings( $settings ) {

  	$updated_settings = array();

  foreach ( $settings as $section ) {

    // at the bottom of the General Options section
    if ( isset( $section['id'] ) && 'general_options' == $section['id'] &&
       isset( $section['type'] ) && 'sectionend' == $section['type'] ) {

      $updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url Count', 'woozap' ),
        'desc_tip' => __( 'Enter the number how many woozap zapier url fields you need.', 'woozap' ),
        'id'       => 'woozap_zapier_url_count',
        'type'     => 'number',
        'css'      => 'min-width:500px;',
        'default'  => '2',  // WC >= 2.0
        'class'    => 'woozap_zapier_url_count',
        'desc'     => __( '<br>Enter the number how many woozap zapier url fields you need.', 'woozap' ),
        'custom_attributes' => array(
                        'min'  => 1,
                        'step' => 1,
                    ),
      );
      
      $fields_count = get_option('woozap_zapier_url_count');  
      if(empty($fields_count)){
        $fields_count = 1;
      }

      for ($i=1; $i <= (int)$fields_count ; $i++) { 
        
      if($i==1){
        $updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url '.$i, 'woozap' ),
        'desc_tip' => __( 'Enter the Zapier Url here that generated while creating woocommerce zap in zapier.', 'woozap' ),
        'id'       => 'woozap_zapier_url',
        'type'     => 'url',
        'css'      => 'min-width:500px;',
        'std'      => '',  // WC < 2.0
        'default'  => '',  // WC >= 2.0,
        'class'    => 'woozap_zapier_url_cls',
        'desc'     => __( '<br>Enter Zapier URL here similar to https://hooks.zapier.com/hooks/catch/2466381/cmpesa/', 'woozap' ),
      );
      }else{
        $updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url '.$i, 'woozap' ),
        'desc_tip' => __( 'Enter the Zapier Url here that generated while creating woocommerce zap in zapier.', 'woozap' ),
        'id'       => 'woozap_zapier_url'.$i,
        'type'     => 'url',
        'css'      => 'min-width:500px;',
        'std'      => '',  // WC < 2.0
        'default'  => '',  // WC >= 2.0,
        'class'    => 'woozap_zapier_url_cls',
        'desc'     => __( '<br>Enter Zapier URL here similar to https://hooks.zapier.com/hooks/catch/2466381/cmpesa/', 'woozap' ),
      );
      } 
      }
      

    /*$updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url 2', 'woozap' ),
        'desc_tip' => __( 'Enter the Zapier Url here that generated while creating woocommerce zap in zapier.', 'woozap' ),
        'id'       => 'woozap_zapier_url2',
        'type'     => 'url',
        'css'      => 'min-width:500px;',
        'std'      => '',  // WC < 2.0
        'default'  => '',  // WC >= 2.0
        'class'    => 'woozap_zapier_url_cls',
        'desc'     => __( '<br>Enter Zapier URL here similar to https://hooks.zapier.com/hooks/catch/2466381/cmpesa/', 'woozap' ),
      );

    $updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url 3', 'woozap' ),
        'desc_tip' => __( 'Enter the Zapier Url here that generated while creating woocommerce zap in zapier.', 'woozap' ),
        'id'       => 'woozap_zapier_url3',
        'type'     => 'url',
        'css'      => 'min-width:500px;',
        'std'      => '',  // WC < 2.0
        'default'  => '',  // WC >= 2.0
        'class'    => 'woozap_zapier_url_cls',
        'desc'     => __( '<br>Enter Zapier URL here similar to https://hooks.zapier.com/hooks/catch/2466381/cmpesa/', 'woozap' ),
      );

    $updated_settings[] = array(
        'name'     => __( 'WooZap Zapier Url 4', 'woozap' ),
        'desc_tip' => __( 'Enter the Zapier Url here that generated while creating woocommerce zap in zapier.', 'woozap' ),
        'id'       => 'woozap_zapier_url4',
        'type'     => 'url',
        'css'      => 'min-width:500px;',
        'std'      => '',  // WC < 2.0
        'default'  => '',  // WC >= 2.0
        'class'    => 'woozap_zapier_url_cls',
        'desc'     => __( '<br>Enter Zapier URL here similar to https://hooks.zapier.com/hooks/catch/2466381/cmpesa/', 'woozap' ),
      );*/
    }

    $updated_settings[] = $section;
  }

  return $updated_settings;
}

//AJAX
    function trigger_woozap_ajax(){
        $url = $_POST['api_url'];

        $order_info['id'] = rand(100,100000);
        $order_info['status'] = 'processing';
        $order_info['currency'] = 'USD';
        $order_info['date_created'] = date('Y-m-d H:i:a');
        $order_info['discount_total'] = '0';
        $order_info['discount_tax'] = '0';
        $order_info['shipping_total'] = '';
        $order_info['shipping_tax'] = '0';
        $order_info['order_total'] = '110.00';
        $order_info['order_total_tax'] = '0';
        $order_info['customer_id'] = '0';
        $order_info['order_key'] = 'wc_order_5c01259a65a3a'.rand(1,100);
        $order_info['billing_firstname'] = 'Test User';
        $order_info['billing_lastname'] = 'Test User';
        $order_info['billing_company'] = '';
        $order_info['billing_address_1'] = 'Test Address';
        $order_info['billing_address_2'] = 'Test Address 2';
        $order_info['billing_city'] = 'Test City';
        $order_info['billing_state'] = 'Test State';
        $order_info['billing_postcode'] = 'Test Postcode';
        $order_info['billing_country'] = 'Test Country';
        $order_info['billing_email'] = 'testuser@test.com';
        $order_info['billing_phone'] = 'Phone';
        $order_info['shipping_firstname'] = '';
        $order_info['shipping_lastname'] = '';
        $order_info['shipping_company'] = '';
        $order_info['shipping_address_1'] = '';
        $order_info['shipping_address_2'] = '';
        $order_info['shipping_city'] = '';
        $order_info['shipping_state'] = '';
        $order_info['shipping_postcode'] = '';
        $order_info['shipping_country'] = '';
        $order_info['payment_method'] = 'cod';
        $order_info['payment_method_title'] = 'Cash on delivery';
        $order_info['transaction_id'] = '';
        $order_info['customer_ip_address'] = '';
        $order_info['customer_user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
        $order_info['customer_note'] = '';
        $order_info['date_completed'] = '';
        $order_info['date_paid'] = '';
        $order_info['item_1'] = array(
                        'ID' => rand(1,100),
                        'product_name' => 'test product - L, Red',
                        'qty' => '1',
                        'type' => 'variable',
                        'price' => '10',
                        'currency' => '$',
                        'thumbnail' => '0',

                    );
        $order_info['item_2'] = array(
                        'ID' => rand(1,100),
                        'product_name' => 'test product - L, Green',
                        'qty' => '1',
                        'type' => 'variable',
                        'price' => '20',
                        'currency' => '$',
                        'thumbnail' => '0',

                    );
        
        $zap_info = woozap_curl('new_order',$order_info,$url);
        echo $zap_info->status;
        wp_die();
    }

} //WooZapController
new WooZapController;