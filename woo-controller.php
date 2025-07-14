<?php
/**
 * WPFlyLeads Woocommerce Controller
 */
defined('ABSPATH') || exit;
class WPFlyLeadsWooController
{

    function __construct()
    {

        //trigger
        add_action('woocommerce_order_status_changed', array(
            $this,
            'wpflyleads_trigger'
        ) , 10, 3);
        //ajax
        add_action('wp_ajax_trigger_wpflyleads_ajax', array(
            $this,
            'trigger_wpflyleads_ajax'
        ));
        add_action('wp_ajax_trigger_zapname_list_wpflyleads_ajax', array(
            $this,
            'trigger_zapname_list_wpflyleads_ajax'
        ));
        //metabox
        add_action('add_meta_boxes', array(
            $this,
            'wpflyleads_custom_meta_box'
        ));
        //notice
        add_action('admin_notices', [$this, 'settings_notice'], 999);
        //settings
        add_action( 'wpflyleads_settings_page', [$this,'wpflyleads_settings_page_callback'], 10 );
    }

    function settings_notice()
    {

        if (!isset($_POST['wpflyleads_submit']))
        {
            return;
        }
        $message = _x('<strong>Success:</strong> settings has been saved sucessfully.', 'admin notice', 'wpflyleads');
        printf('<div class="updated"><p>%s</p></div>', wp_kses_post($message));
    }

    function wpflyleads_custom_meta_box()
    {

        if (!class_exists('WooCommerce'))
        {

            return false;
        }
        else
        {
            add_meta_box('wpflyleads_url_connection_list', __('Connections list', 'wpflyleads') , array(
                $this,
                'wpflyleads_url_connection_list'
            ) , 'shop_order', 'side', 'high');
        }
    }

    function wpflyleads_url_connection_list()
    {
        $container = array();
        $get_connection_data = wpflyleads_get_webhook_urls();
        $wpflyleads_html = "<em>" . __('Select a connection to send this order to connection server manually.', 'wpflyleads') . "</em>";
        $wpflyleads_html .= "<p></p>";
        $wpflyleads_html .= "<div id='wpflyleads_connection_status_messages'></div>";
        $wpflyleads_html .= "<tr><td><select  style='width:70%;' current_order_id='" . get_the_ID() . "' id='wpflyleads_connection_name_select'><option value='null' disabled selected hidden >" . __('Choose a connection', 'wpflyleads') . "</option>";
        $wpflyleads_disable_manual_features = get_option('wpflyleads_disable_manual_features');
        if ($wpflyleads_disable_manual_features)
        {

            foreach ($get_connection_data as $key => $value)
            {
                $val = $value[1];
                $wpflyleads_html .= "<option value='" . $val . "'>" . esc_html($val) . "</option>";
            }
        }
        $wpflyleads_html .= "</select></td><td>
  <button style='height: 38px;' class='button button-small' id='wpflyleads_connection_list_trigger_btn' >" . __('Test', 'wpflyleads') . "</button></td></tr>";

        echo $wpflyleads_html;
    }

    //WPFlyLeads Trigger
    function wpflyleads_trigger($order_id, $old_status, $new_status)
    {

        $order_info = array();
        $to_status_container = array();
        $from_status_container = array();
        if (!$order_id) return;

        if ($old_status == $new_status)
        {
            return;
        }

        $get_all_status_array = maybe_unserialize(get_option('wpflyleads_all_status'));
        $get_date = get_option('wpflyleads_date');
        $wpflyleads_disable_checkbox = get_option('wpflyleads_disable_checkbox');
        if (empty($wpflyleads_disable_checkbox))
        {
            return;
        }

        if (empty($get_date))
        {
            $get_date = "0000-00-00";
        }
        if (!empty($get_all_status_array))
        {

            foreach ($get_all_status_array as $key => $value)
            {

                if ($value[0] == "wc-" . $old_status && $value[1] == "wc-" . $new_status)
                {

                    $countries = WC()->countries->get_countries();
                    $states = WC()->countries->get_states();

                    $order = wc_get_order($order_id);
                    $order_data = $order->get_data();
                    $current_order_date = $order->get_date_created()->date_i18n("Y-m-d");

                    if (strtotime($current_order_date) >= strtotime($get_date))
                    {

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
                        $order_info['billing_country'] = $countries[$order_data['billing']['country']];
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

                        if (!empty($order_data['meta_data']))
                        {
                            foreach ($order_data['meta_data'] as $index => $custom_field)
                            {
                                $order_info[$custom_field->key] = $custom_field->value;

                            }
                        }

                        $o = 1;
                        foreach ($order->get_items() as $key => $item)
                        {

                            $item = $item->get_data();
                            $product = wc_get_product($item['product_id']);
                            $item_sku = $product->get_sku();
                            $order_info['item_' . $o] = array(
                                'ID' => $item['product_id'],
                                'product_name' => $item['name'],
                                'qty' => $item['quantity'],
                                'type' => (empty($item['variation_id']) || ($item['variation_id'] <= 0) ? 'simple' : 'variable') ,
                                'price' => $item['total'],
                                'currency' => get_woocommerce_currency_symbol() ,
                                'thumbnail' => get_the_post_thumbnail_url($item['product_id'], 'full') ,
                                'sku' => $item_sku,
                            );

                            if (!empty($item['meta_data']))
                            {
                                foreach ($item['meta_data'] as $index => $custom_field)
                                {
                                    $order_info['item_' . $o][$custom_field->key] = $custom_field->value;

                                }
                            }

                            $o++;
                        }

                        $coupons_used = array();
                        if (!empty($order->get_used_coupons()))
                        {
                            foreach ($order->get_used_coupons() as $coupon_name)
                            {

                                // Retrieving the coupon ID
                                $coupon_post_obj = get_page_by_title($coupon_name, OBJECT, 'shop_coupon');
                                $coupon_id = $coupon_post_obj->ID;

                                // Get an instance of WC_Coupon object in an array(necesary to use WC_Coupon methods)
                                $coupons_obj = new WC_Coupon($coupon_id);

                                // Now you can get type in your condition
                                $coupon_type = $coupons_obj->get_discount_type();
                                $coupon_amount = $coupons_obj->get_amount();
                                $coupons_used[] = array(
                                    'name' => $coupon_name,
                                    'type' => $coupon_type,
                                    'amount' => $coupon_amount
                                );

                            } //coupons
                            
                        }

                        $order_info['coupons'] = $coupons_used;

                        // $url=get_option('wpflyleads_connection_url');
                        $url = wpflyleads_get_webhook_urls();

                        foreach ($url as $url_key => $value)
                        {
                            $url_value = $value[0];
                            $url_name = $value[1];
                            $url_server = $value[3];
                            
                            if (!empty($url_value))
                            {

                                $connection_info = wpflyleads_curl('new_order', $order_info, $url_value,$url_server);
                                
                                if ($connection_info->status == 'success')
                                {
                                    $note = __(sprintf("%s url %s triggered with status : %s and order status changed from %s to %s",$url_server, $url_name, $connection_info->status, $old_status, $new_status));
                                    $order->add_order_note($note);
                                    $order->save();
                                }

                            }
                        } //foreach
                        
                    }
                }
            }

            //die();
            
        }
    } //function close
    

    function post($post = array())
    {

        $wz_status = array();
        $wz_all = array();
        $urls = array();
        $url_details_container = array();
        $title_container = array();
        $generate_url_name = array();

        if (isset($post['wpflyleads_submit']))
        {
            
            
            $get_urls = array_map('sanitize_url', $post['wpflyleads_connection_url']);
            $url_title = array_map('sanitize_text_field', stripslashes_deep($post['connection_url_name']));
            $url_slug = array_map('sanitize_text_field', stripslashes_deep($post['connection_url_slug']));
            $url_server = array_map('sanitize_text_field', stripslashes_deep($post['connection_url_server']));
            $get_status_from = array_map('sanitize_text_field', wp_unslash($post['wpflyleads_get_from_status']));
            $get_status_to = array_map('sanitize_text_field', wp_unslash($post['wpflyleads_get_to_status']));

            $wpflyleads_date = sanitize_text_field($post['date']);
            $wpflyleads_disable_checkbox = (isset($post['wpflyleads_disable_checkbox']) ? 1 : 0);
            $wpflyleads_disable_manual_features = (isset($post['wpflyleads_disable_manual_features']) ? 1 : 0);

            if (!empty($get_status_from))
            {

                $status_size = count($get_status_from);
                for ($i = 0;$i < $status_size;$i++)
                {
                    $wz_status[0] = $get_status_from[$i];
                    $wz_status[1] = $get_status_to[$i];
                    $wz_all[] = $wz_status;
                } //for
                
            }

            if (!empty($get_urls))
            {

                foreach ($get_urls as $key => $value)
                {
                    if (!empty($get_urls[$key]))
                    {

                        if (empty($url_title[$key]))
                        {

                            $url = "connection " . rand(1000, 10000);
                            $slug = sanitize_title($url);
                            $server = $url_server[$key];
                        }
                        if (!empty($url_title[$key]))
                        {

                            $url = $url_title[$key];
                            $slug = sanitize_title($url);
                            $server = $url_server[$key];

                        }
                        $url_details_container[$key][0] = $get_urls[$key];
                        $url_details_container[$key][1] = $url;
                        $url_details_container[$key][2] = $slug;
                        $url_details_container[$key][3] = $server;
                    }
                } //foreach
                
            }

            if (!empty($wz_status[0]) && !empty($wz_status[1]) && $wz_status[0] !== $wz_status[1])
            {
                update_option('wpflyleads_all_status', $wz_all);
            }

            if (!empty($url_details_container))
            {
                update_option('wpflyleads_connection_url', $url_details_container);
            }
            update_option('wpflyleads_disable_checkbox', $wpflyleads_disable_checkbox);
            update_option('wpflyleads_disable_manual_features', $wpflyleads_disable_manual_features);
            update_option('wpflyleads_date', $wpflyleads_date);
        }
        //}
        
    }

    function wpflyleads_settings_page_callback()
    {
        wpflyleads_update_options_prefix('wpzap','wpflyleads'); //for old versions
        $this->post($_POST);
        $all_status = wc_get_order_statuses();
        $get_all_status_array =  maybe_unserialize(get_option('wpflyleads_all_status'));
        //$get_all_urls=get_option('wpflyleads_connection_url');
        $get_all_urls = wpflyleads_get_webhook_urls();
        $date = get_option('wpflyleads_date');
        $wpflyleads_disable_checkbox = get_option('wpflyleads_disable_checkbox');
        $wpflyleads_disable_manual_features = get_option('wpflyleads_disable_manual_features');
        
        $wpflyleads_form_html = "<div class='wpflyleads'>
        <div>
        <h3>1. Gravity Forms Automation Settings</h3>
        <a href='".admin_url( 'admin.php?page=gf_edit_forms' ) ."'>Go to Settings (Under each form)</a>

        <h3>2. Contact Form 7 Automation Settings</h3>
        <a href='".admin_url( 'admin.php?page=wpcf7' ) ."'>Go to Settings (Under each form)</a>
        </div>
        
        <h3>3. Orders Automation Settings</h3>

        <form id='wpflyleads_setting_form' action='' method='post' >  
            <table class='wpflyleads-table wp-list-table widefat fixed striped'>";

        $wpflyleads_form_html .= "<tr><th style='width:45%'>
            <label style='margin-right:16px;'>" . __('Enable automation', 'wpflyleads') . "</label>
            <input type='checkbox' id='wpflyleads_disable_checkbox' name='wpflyleads_disable_checkbox' " . ($wpflyleads_disable_checkbox ? 'checked' : '') . "><br>

            <em style='font-size:12px;'>" . __('(If disabled, orders will not be sent to zapier if order status changes.)', 'wpflyleads') . "</em></th>";

        $wpflyleads_form_html .= "<td>

            </td>
            <td>
            </td>
            </tr>";
        $wpflyleads_form_html .= "<tr>
            <th>
            <label>" . __('Enable manual option', 'wpflyleads') . "</label>
            <input type='checkbox' id='wpflyleads_disable_manual_features' name='wpflyleads_disable_manual_features' " . ($wpflyleads_disable_manual_features ? 'checked' : '') . " ><br>
            <em style='font-size:12px;'>" . __(' (If disabled, you will no longer see the manual option in each order edit page to send order to zapier.)', 'wpflyleads') . "</em></th>
            <td></td>
            <td></td>
            </tr>";
        $wpflyleads_form_html .= "<tr><th><label for='Zapier_webhook_urls'>" . __('New connection webhook urls', 'wpflyleads') . "</label><br>
            <em style='font-size:12px'>" . __('(Enter webhook URL you get from destination platform while using webhook connection.)', 'wpflyleads') . "</em>
            </th>
            <td> <button id='wpflyleads_plus' class='button button-small' style=''>" . __('Add more connections', 'wpflyleads') . "</button>
            </td>
            <td>
            </td>
            </tr>";

        if (empty($get_all_urls))
        {

            $wpflyleads_form_html .= "<tr>
              <th><label for='Zap_url'>" . __('Connection ', 'wpflyleads') . "</label>
              <input style='' type='text' name='connection_url_name[]' id='connection_url_name' value='' placeholder= '" . __('Url title', 'wpflyleads') . "'>
              <input   style='' type='text' name='connection_url_slug[]' id='connection_url_slug' value='' placeholder='" . __('Url slug', 'wpflyleads') . "'>

              </th>
              <td><input style='min-width:500px' type='text' class='wpflyleads_connection_url_cls' name='wpflyleads_connection_url[]' id='wpflyleads_connection_url' value=''>
              </td>
              <td>

              <button style='padding: 0 10px;margin-left: 215px;height: 32px;' id='wpflyleads_trigger_btn' class='button button-small' >" . __('Trigger', 'wpflyleads') . "</button>
              </td></tr>";
        }

        elseif (!empty($get_all_urls))
        {
            $first_element_css = 'padding: 0 10px;margin-left: 212px;height: 32px;';
            $else_css = 'padding: 0 10px;margin-left: 20px;height: 32px;';
            $i = 1;

            foreach ($get_all_urls as $key => $value)
            {
                //<div class='wpflyleads-connection-label'><label  for='WPFlyLeads_Zapier_Url'>" . __('#'.$i, 'wpflyleads') . "  </label></div>
                $wpflyleads_form_html .= "<tr id='wpflyleads_custom_url_column" . $i . "'>
               <th class='wpflyleads-connection'>
               
               <div class='wpflyleads-connection-name'>
               <label>Name</label>
               <input style='' type='text' name='connection_url_name[]' id='connection_url_name" . $i . "' value='" . esc_attr( $value[1] ?: '' ) . "' placeholder='" . __('Url Title', 'wpflyleads') . "'>
               </div>
               <div class='wpflyleads-connection-slug'>
               <label>Slug</label>
               <input style='' type='text' name='connection_url_slug[]' id='connection_url_slug" . $i . "' value='" . esc_attr( $value[2] ?: '') . "' placeholder='" . __('Url Slug', 'wpflyleads') . "'>
               </div>
               <div class='wpflyleads-connection-server'>
               <label>Server</label>
               <select style='' name='connection_url_server[]' id='connection_url_server" . $i . "' placeholder='" . __('Url Server', 'wpflyleads') . "'>
               '".wpflyleads_get_servers($value[3] ?: '')."'
               </select>
               </div>
               </th>
               <td>
               <input style='min-width:500px' type='text' class='wpflyleads_connection_url_cls' name='wpflyleads_connection_url[]' id='wpflyleads_connection_url" . $i . "' value='" . esc_attr( $value[0] ) . "'>
               </td>
               <td>";
                if ($i > 1)
                {
                    $wpflyleads_form_html .= "<button style='height:27px;width:46px;margin-left: 159px;vertical-align: bottom;' id='" . $i . "' class='wz_remove_field '>x</button>";
                }
                if($value[0]){
                    $wpflyleads_form_html.="<button style='" . esc_attr($i == 1 ? $first_element_css : $else_css) . "' id='wpflyleads_trigger_btn' class='button button-small' >" . __('Test', 'wpflyleads') . "</button>";
                }
                $wpflyleads_form_html .= "</td>

              </tr>";
                $i++;
            }
        }
        $wpflyleads_form_html .= "<tr id='wpflyleads_custom_url_column'></tr>";

        if (empty($get_all_status_array))
        {

            $wpflyleads_form_html .= "<tr>
            <th><label  for='WPFlyLeads_select_status'>" . __('Choose order status', 'wpflyleads') . "<br><em style='font-size:12px;' >" . __('(Trigger zaps to send WooCommerce orders if order status move from-to)', 'wpflyleads') . "</em></label>
            </th>
            <td>
            <button id='wpflyleads_plus_status_field' class='button button-small' style=''>" . __('Add more status', 'wpflyleads') . "</button>
            </td>
            <td></td>
            </tr>

            <tr>
            <td>
            <strong>" . __('From', 'wpflyleads') . "</strong>&nbsp;&nbsp;
            <select style='width:50%;' class='wpflyleads_get_all_status' name='wpflyleads_get_from_status[]' >
            <option value='0'disabled selected hidden>" . __('Select a status', 'wpflyleads') . "</option>";

            foreach ($all_status as $key => $value)
            {

                $wpflyleads_form_html .= " <option value=" . esc_attr($key) . ">" . esc_html($value) . "</option>";
            }
            $wpflyleads_form_html .= "</select>
           </td>
           <td>
           <strong>" . __('To', 'wpflyleads') . "</strong>&nbsp;&nbsp;
           <select style='width:50%;' class='wpflyleads_get_all_status' name='wpflyleads_get_to_status[]' >
           <option value='0'disabled selected hidden>" . __('Select a status ', 'wpflyleads') . "</option>";

            foreach ($all_status as $key => $value)
            {

                $wpflyleads_form_html .= "<option value=" . esc_attr($key) . ">" . esc_html($value) . "</option>";
            }
            $wpflyleads_form_html .= "</select>
           </td>
           <td></td>
           </tr>";
        } //if
        elseif (!empty($get_all_status_array))
        {
            $count_status = count($get_all_status_array);
            $wpflyleads_form_html .= "
              <tr>
              <th><label  for='WPFlyLeads_select_status'>" . __('Choose order status', 'wpflyleads') . "<br><em style='font-size:12px;' >" . __('(Trigger zaps to send WooCommerce orders if order status move from-to)', 'wpflyleads') . "</em></label>
              </th>
              <td><button id='wpflyleads_plus_status_field' class='button button-small' style=''>" . __('Add more status', 'wpflyleads') . "</button>
              </td>
              <td>
              </td>
              </tr>";

            for ($i = 0;$i < $count_status;$i++)
            {

                $st_values = $get_all_status_array[$i];
                $first_value = $st_values[0];
                $second_value = $st_values[1];

                //echo $first_value.'//'.$second_value.'<br>';
                $wpflyleads_form_html .= "<tr id ='wpflyleads_custom_status_field" . $i . "'>

                <td><strong>" . __('From', 'wpflyleads') . "</strong>&nbsp;&nbsp; 
                <select style='width:50%;' class='wpflyleads_get_all_status' name='wpflyleads_get_from_status[]' >";
                $wpflyleads_form_html .= "<option value='" . esc_attr($first_value) . "'>" . esc_html($all_status[$first_value]) . "</option>";
                foreach ($all_status as $key => $value)
                {
                    if ($key == $first_value)
                    {
                        $key = '';
                    }
                    if (!empty($key))
                    {
                        $wpflyleads_form_html .= "<option value='" . esc_attr($key) . "'>" . esc_html($value) . "</option>";
                    }
                }
                $wpflyleads_form_html .= "
                </select>
                </td>
                <td><strong>" . __('To', 'wpflyleads') . "</strong>&nbsp;&nbsp;
                <select style='width:50%;' class='wpflyleads_get_all_status' name='wpflyleads_get_to_status[]' > ";
                $wpflyleads_form_html .= "<option value='" . esc_attr($second_value) . "'  >" . esc_html($all_status[$second_value]) . "</option>";
                foreach ($all_status as $key => $value)
                {
                    if ($key == $second_value)
                    {
                        $key = '';
                    }
                    if (!empty($key))
                    {
                        $wpflyleads_form_html .= "<option value='" . esc_attr($key) . "'>" . esc_html($value) . "</option>";
                    }
                }
                $wpflyleads_form_html .= " </select>";
                if ($i > 0)
                {
                    $wpflyleads_form_html .= "<button style='height:27px; width:46px;margin-left:28px;' id='" . $i . "' class='wpflyleads_remove_status '>x</button>";
                }

                $wpflyleads_form_html .= " </td>
                <td></td>
                </tr>";
            } //for
            
        }
        $wpflyleads_form_html .= "<tr id='wpflyleads_custom_status_field'></tr>";
        
        
        $wpflyleads_form_html .= " 
          
          <td><input type='submit' class='button button-primary button-large' id='wpflyleads_submit' name='wpflyleads_submit' value='" . __('Save Changes', 'wpflyleads') . "'></td>
          <td></td>
          <td></td>
          </tr>
          </table>
          </form></div>";
          $wpflyleads_form_html .= "
          <h4>".__('Notes:','wpflyleads')."</h4>
          <ul>
          <li>".__('Zapier: To use Zapier webhook, you should have a premium account of zapier or the webhook would return success but won\'t reach the zapier server.','wpflyleads')."</li>
          <li>".__('Any Server: Please make sure the zap/connection is ON and connected properly.','wpflyleads')."</li>
          </ul>
          ";
        echo $wpflyleads_form_html;
    } //function close
    

    //AJAX
    function trigger_wpflyleads_ajax()
    {
        $url = esc_url($_POST['api_url']);
        $server = sanitize_text_field($_POST['api_server']);

        $order_info['id'] = rand(100, 100000);
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
        $order_info['order_key'] = 'wc_order_5c01259a65a3a' . rand(1, 100);
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
        $order_info['customer_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $order_info['customer_note'] = '';
        $order_info['date_completed'] = '';
        $order_info['date_paid'] = '';
        $order_info['item_1'] = array(
            'ID' => rand(1, 100) ,
            'product_name' => 'test product - L, Red',
            'qty' => '1',
            'type' => 'variable',
            'price' => '10',
            'currency' => '$',
            'thumbnail' => '0',
            'sku' => 'sku1'

        );
        $order_info['item_2'] = array(
            'ID' => rand(1, 100) ,
            'product_name' => 'test product - L, Green',
            'qty' => '1',
            'type' => 'variable',
            'price' => '20',
            'currency' => '$',
            'thumbnail' => '0',
            'sku' => 'sku2'

        );

        $connection_info = wpflyleads_curl('new_order', $order_info, $url, $server);
        echo $connection_info->status;
        wp_die();
    }

    function trigger_zapname_list_wpflyleads_ajax()
    {

        $connection_name = sanitize_text_field($_POST['connection_name']);
        $current_order_id = (int)$_POST['current_order_id'];

        $countries = WC()->countries->get_countries();
        $states = WC()->countries->get_states();

        $order = wc_get_order($current_order_id);
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
        $order_info['billing_country'] = $countries[$order_data['billing']['country']];
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

        if (!empty($order_data['meta_data']))
        {
            foreach ($order_data['meta_data'] as $index => $custom_field)
            {
                $order_info[$custom_field->key] = $custom_field->value;

            }
        }

        $o = 1;
        foreach ($order->get_items() as $key => $item)
        {

            $item = $item->get_data();
            $product = wc_get_product($item['product_id']);
            $item_sku = $product->get_sku();
            $order_info['item_' . $o] = array(
                'ID' => $item['product_id'],
                'product_name' => $item['name'],
                'qty' => $item['quantity'],
                'type' => (empty($item['variation_id']) || ($item['variation_id'] <= 0) ? 'simple' : 'variable') ,
                'price' => $item['total'],
                'currency' => get_woocommerce_currency_symbol() ,
                'thumbnail' => get_the_post_thumbnail_url($item['product_id'], 'full') ,
                'sku' => $item_sku,
            );

            if (!empty($item['meta_data']))
            {
                foreach ($item['meta_data'] as $index => $custom_field)
                {
                    $order_info['item_' . $o][$custom_field->key] = $custom_field->value;

                }
            }
            $o++;
        }
        $coupons_used = array();
        if (!empty($order->get_used_coupons()))
        {
            foreach ($order->get_used_coupons() as $coupon_name)
            {

                // Retrieving the coupon ID
                $coupon_post_obj = get_page_by_title($coupon_name, OBJECT, 'shop_coupon');
                $coupon_id = $coupon_post_obj->ID;

                // Get an instance of WC_Coupon object in an array(necesary to use WC_Coupon methods)
                $coupons_obj = new WC_Coupon($coupon_id);

                // Now you can get type in your condition
                $coupon_type = $coupons_obj->get_discount_type();
                $coupon_amount = $coupons_obj->get_amount();
                $coupons_used[] = array(
                    'name' => $coupon_name,
                    'type' => $coupon_type,
                    'amount' => $coupon_amount
                );

            } //coupons
            
        }

        $order_info['coupons'] = $coupons_used;
        $get_url = wpflyleads_get_webhook_urls();
        foreach ($get_url as $key => $value)
        {
            if ($value[1] == $connection_name)
            {
                $url = $value[0];
                $url_name = $value[1];
                $url_server = $value[3];
            }
        } //foreach
    
        $connection_info = wpflyleads_curl('new_order', $order_info, $url,$url_server);
        
        if ($connection_info->status == "success")
        {
            $note = __(sprintf("This order sent to %s url: %s triggered with status : %s ",$url_server, $url_name, $connection_info->status));
            $order->add_order_note($note);
            $order->save();
            echo $connection_info->status;
        }
        wp_die();

    } //function close
    
} //WPFlyLeadsWooController
new WPFlyLeadsWooController;

