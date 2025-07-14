<?php
/**
 * WPFlyLeads CF7 Controller
 */
defined('ABSPATH') || exit;
class WPFlyLeadsCF7Controller
{

    function __construct()
    {
        add_action( 'wpcf7_editor_panels', [$this,'wpcf7_settings'], 10 );
        add_action( 'wpcf7_save_contact_form', [$this,'save_contact_form_trigger'], 10, 3 );
        add_filter( 'wpcf7_contact_form_properties', [$this,'set_properties'], 10 );
        add_action( 'wpcf7_before_send_mail', [$this,'send_to_connection_server'], 10, 3);
    }

    static function sanitize_array($submission=[]){
        $result = [];
        if(!empty($submission)){
            foreach ($submission as $name => $value) {
                if(is_array($value)){
                    $value = implode(",",$value);
                }
                if(is_date( $value )){
                    $value = date('Y-m-d H:i:s',strtotime($value));
                }
                $result[$name] = sanitize_text_field( $value );
            }
        }
        return $result;
    }

    function send_to_connection_server($contact_form, $abort, $object){

        
        
        $form_id = $contact_form->id();
        $log = get_option('wpflyleads_log_cf7',[]);
        $submission = $object->get_posted_data();
        $parsed_data = self::sanitize_array($submission);
        $get_settings = get_post_meta( $form_id, '_wpflyleads_settings',true );
        
        $server = (isset($get_settings['server']) ? $get_settings['server'] : []);
       
        $url = (isset($get_settings['url']) ? $get_settings['url'] : []);
        $is_log_enable = (isset($get_settings['log']) ? (bool)$get_settings['log'] : 0);

        if(empty($server) || empty($url) || empty($parsed_data)){
            return;
        }
        $parsed_data = apply_filters( 'wpflyleads_cf7_parsed_data', $parsed_data );
        $connection_info = wpflyleads_curl('new_lead', $parsed_data, $url,$server);
     
        $this_track_log['data'] = $parsed_data;
        $this_track_log['sent_to_connection'] = 'no';
        $this_track_log['server'] = $server;
        $this_track_log['url'] = $url;
        
        if($connection_info->status == 'success'){
            $this_track_log['sent_to_connection'] = 'yes';
        }

        if($is_log_enable){
            $log[$form_id][] = $this_track_log;
            update_option('wpflyleads_log_cf7',$log);
        }
       // echo '<pre>';  print_r($this_track_log);  die;
        
    }
    
    function set_properties($properties){
        $previous_data = (isset($properties['wpflyleads_settings']) ? $properties['wpflyleads_settings'] : []);
        $properties['wpflyleads_settings'] = (!empty($previous_data) && is_array($previous_data) ? $previous_data : []);
        return $properties;
    }

    function wpcf7_settings($panels){
        $panels['wpflyleads-panel'] = array(
            'title' => __( 'WP Fly Leads Settings', 'wpflyleads' ),
            'callback' => [$this,'wpflyleads_settings_callback'],
        );
        return $panels;
    }

    function save_contact_form_trigger($contact_form, $args,$context){
       
        if(!empty($args['wpflyleads_webhook_url'])){
            $properties['wpflyleads_settings']=['server' => sanitize_text_field( !empty($args['wpflyleads_servers']) ? $args['wpflyleads_servers'] : '' ), 'url' => sanitize_text_field( !empty($args['wpflyleads_webhook_url']) ? $args['wpflyleads_webhook_url'] : '' ), 'log' => sanitize_text_field( !empty($args['wpflyleads_log_cf7']) ? $args['wpflyleads_log_cf7'] : '' ) ];
            $contact_form->set_properties($properties);
            
        }
        
    }

    function wpflyleads_settings_callback($post,$args=''){
        $data=[];
        $form_id = $post->id();
        $current_url = admin_url('?page=wpcf7&post='.$form_id.'&active-tab=4');
        $post_data = get_post_meta( $form_id, '_wpflyleads_settings', true );
        $logview = get_option('wpflyleads_log_cf7',[]);
       
        if(isset($_GET['wpflyleads_action'])){
           
            update_option('wpflyleads_log_cf7',[]);
            wp_redirect( $current_url );
        }
        ?>
        <div class="wpflyleads_settings">
            <table>
                <tr>
                    <th style="width:20%"><?php _e('Connection Webhook URL','wpflyleads');?></th>
                    <td style="width:20%">
                        <div>
                            <label><?php _e('Server','wpflyleads');?></label>
                            <select name="wpflyleads_servers" id="wpflyleads_servers" data-config-field="wpflyleads.servers">
                                <?php echo wpflyleads_get_servers($post_data['server'] ?: '');?>
                            </select>
                        </div>
                    </td>
                    <td style="width:60%">
                        <div>
                            <label><?php _e('URL','wpflyleads');?></label>
                            <input style="width:95%;" type="text" name="wpflyleads_webhook_url" id="wpflyleads_webhook_url" data-config-field="wpflyleads.url" value="<?php echo ($post_data['url'] ?: '');?>">
                        </div>
                    </td>
                </tr>

                <tr>
                    <th style="width:20%"><?php _e('Debug Log','wpflyleads');?></th>
                     <td class=""></td>
                    <td style="width:20%">
                        <div>
                            <label></label>
                            <input style="width:2%" type="checkbox" name="wpflyleads_log_cf7" id="wpflyleads_log_cf7" data-config-field="wpflyleads.log" <?php echo (!empty($post_data['log']) ? 'checked': '');?>>
                        </div>
                    </td>
                   
                </tr>
                <tr>
                    <th style="width:20%"><?php _e('Debug Log View','wpflyleads');?></th>
                    <td class=""></td>
                    <td style="width:60%">
                        <div>
                            <label></label>
                            <textarea cols="80" rows="20" name="wpflyleads_log_cf7_view" id="wpflyleads_log_cf7_view" data-config-field="wpflyleads.log_view"><?php print_r (isset($post_data['log']) && !empty($logview) ? $logview[$form_id]: '');?></textarea>
                        </div>
                        <a class="button button-secondary" href="<?php echo $current_url;?>&wpflyleads_action=delete_log"><?php _e('Delete Log','');?></a><br>
                        <i class=""><?php _e('If debug log is enable, logs will be display here.','wpflyleads');?></i>
                    </td>
                    
                </tr>
            </table>
        </div>
        <?php
         
        
        
    }
    
} //WPFlyLeadsCF7Controller
new WPFlyLeadsCF7Controller;

