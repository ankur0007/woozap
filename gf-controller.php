<?php
/**
 * WPFlyLeads Gravity Forms Controller
 */
defined('ABSPATH') || exit;
class WPFlyLeadsGFController
{

    function __construct()
    {
        add_action( 'gform_form_settings_menu', [$this,'menus'], 10 );
        add_filter( 'gform_form_settings_page_wpflyleads_settings', [$this, 'wpflyleads_settings_callback'], 10 );
        add_action( 'gform_after_submission', [$this,'send_to_connection_server'], 10, 2);
    }

    function menus($menus){

    $menus[] = array( 
        'name' => 'wpflyleads_settings', 
        'label' =>__('WP Fly Leads Settings'), 
        'icon' =>  'dashicons dashicons-social' 
    );

    return $menus;

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

    function post( $post ) {

        if ( 
        isset( $post['wpflyleads_settings_nonce_field'] ) 
        &&  
        wp_verify_nonce( $post['wpflyleads_settings_nonce_field'], 'wpflyleads_settings_action' )
        )
        {

            $settings = self::sanitize_array($post);

            $post_data = [
                'url' => $settings['wpflyleads_webhook_url'],
                'log' => (isset($settings['wpflyleads_log_gf']) ? true : false),
                'server' => $settings['wpflyleads_servers'],
            ];

            update_option( 'wpflyleads_gf_settings_'.$_GET['id'], $post_data );
            
            ?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e( 'Saved Successfully!', '' ); ?></p>
				</div>
			<?php
        }
    }
    
    function send_to_connection_server($entry, $form){

        $form_id = $form['id'];
        $log = get_option('wpflyleads_log_gf',[]);
        $submission = $entry;
        $fields = $form['fields'];
        $parsed_data = self::sanitize_array($submission);
        $get_settings = get_option('wpflyleads_gf_settings_'.$form_id);
        $server = (isset($get_settings['server']) ? $get_settings['server'] : []);
        $url = (isset($get_settings['url']) ? $get_settings['url'] : []);
        $is_log_enable = (isset($get_settings['log']) ? (bool)$get_settings['log'] : 0);
        
        if(!empty($fields)){
            foreach($fields as $field){
                $value = rgar($parsed_data, $field->id);
                $entries_with_labels[$field->label] = $value;  
            }
            $parsed_data = array_merge($parsed_data, $entries_with_labels);
        }
        
        if(empty($server) || empty($url) || empty($parsed_data)){
            return;
        }

        
        
        
        $parsed_data = apply_filters( 'wpflyleads_gf_parsed_data', $parsed_data );
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
            update_option('wpflyleads_log_gf',$log);
        }
       // echo '<pre>';  print_r($this_track_log);  die;
        
    }
    

    


    function wpflyleads_settings_callback( ){

        $this->post($_POST);

        $data=[];
        $form_id = $_GET['id'];
        $current_url = admin_url( '?page=gf_edit_forms&view=settings&subview=wpflyleads_settings&id='.$form_id );
        $post_data = get_option('wpflyleads_gf_settings_'.$form_id);
        $logview = get_option('wpflyleads_log_gf');
        $logview = $logview ? $logview : [];
        
        GFFormSettings::page_header();
        ?>
        <form method="post" action="">
        <?php 
             wp_nonce_field( 'wpflyleads_settings_action', 'wpflyleads_settings_nonce_field' ); 
        ?>
        <div class="wpflyleads_settings gf">
            <table>
                <tr>
                    <th style="width:25%"><?php _e('Connection Webhook URL','wpflyleads');?></th>
                    <td style="width:25%">
                        <div>
                            <label><?php _e('Server','wpflyleads');?></label>
                            <select name="wpflyleads_servers" id="wpflyleads_servers" data-config-field="wpflyleads.servers">
                                <?php echo wpflyleads_get_servers(isset($post_data['server']) ?: '');?>
                            </select>
                        </div>
                    </td>
                    <td style="width:49%">
                        <div>
                            <label><?php _e('URL','wpflyleads');?></label>
                            <input type="text" name="wpflyleads_webhook_url" id="wpflyleads_webhook_url" data-config-field="wpflyleads.url" value="<?php echo (isset($post_data['url']) ?: '');?>">
                        </div>
                    </td>
                </tr>

                <tr>
                    <th style="width:20%"><?php _e('Debug Log','wpflyleads');?></th>
                     <td class=""></td>
                    <td style="width:20%">
                        <div>
                            <label></label>
                            <input style="width:2%" type="checkbox" name="wpflyleads_log_gf" id="wpflyleads_log_gf" data-config-field="wpflyleads.log" <?php echo (!empty($post_data['log']) ? 'checked': '');?>>
                        </div>
                    </td>
                   
                </tr>
                <tr>
                    <th style="width:20%"><?php _e('Debug Log View','wpflyleads');?></th>
                    <td class=""></td>
                    <td style="width:60%">
                        <div>
                            <label></label>
                            <textarea cols="80" rows="20" name="wpflyleads_log_gf_view" id="wpflyleads_log_gf_view" data-config-field="wpflyleads.log_view"><?php print_r (isset($post_data['log']) && !empty($logview) ? $logview[$form_id]: '');?></textarea>
                        </div>
                        <a class="button button-secondary" href="<?php echo $current_url;?>&wpflyleads_action=delete_log"><?php _e('Delete Log','');?></a><br>
                        <i class=""><?php _e('If debug log is enable, logs will be display here.','wpflyleads');?></i>
                    </td>
                    
                </tr>
                <tr>
                    <td><input type="submit" class="button button-primary" value="Save"></td>
                </tr>
            </table>
            
        </div>
        </form>
        <?php
        GFFormSettings::page_footer();
        
        
    }
    
} //WPFlyLeadsGFController
new WPFlyLeadsGFController;

