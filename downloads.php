<?php
/*
Plugin name: Downloads
Plugin URI: http://soundwela.net
Description: This plugin forces file download. It enables you manage file downloads. 
Author: Samuel Chukwu 
Version: 1.1.0
License: GPL2
Text Domain: fd_downloads
Author URI: https://github.com/veltany 
GitHub Plugin URI: https://github.com/veltany/downloads
GitHub Branch: main
Requires at least: 6.6
Requires PHP: 8.2
*/

if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
} 

// Plugin Constants
define('FD_DOWNLOADS_DIR', plugin_dir_path(__FILE__));
define('FD_DOWNLOADS_URL', plugin_dir_url(__FILE__));

//-------------------------------------
// PLUGIN UPDATES
require FD_DOWNLOADS_DIR.'plugin-update/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/veltany/downloads',
	 FD_DOWNLOADS_DIR.'downloads.php', //Full path to the main plugin file or functions.php.,
	'fd_downloads'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');
//------------------------------------

//Force File Download
add_action('wp', 'fd_forcedownload', 0);
function fd_forcedownload(){
    global $post;
   
    $query_string = $_GET['download']; 
   
    if (isset($query_string) && is_singular( 'post' ) ) {

      //update post view
      update_post_meta($post->ID, '_last_viewed_time', current_time('mysql')); 
      upd_log("Download Occured. Last View Updated for Post ID:". $post->ID); 
      
      $file = get_attached_file($query_string);
   
        if( file_exists($file)) { 
             fd_send_download_headers($file);
             exit;
        }
    
     
    }
    
}

//-------------------------------
function fd_send_download_headers($fullpath){

    $size   = filesize($fullpath) ; 
    $filename = basename($fullpath);  
    
   
       header('Content-Type: application/octet-stream');
       header('Content-Disposition: attachment; filename="'.$filename.'"');
       header( "Content-length: " . $size );
   
    
       ob_clean();
       flush(); 
       readfile($fullpath);
}

//-----------------------------------------------


// Schedule the cron job if not already scheduled
function upd_schedule_cron() {
    if (!wp_next_scheduled('upd_update_post_dates')) {
        upd_log("UPD Cron Job Scheduled");
        wp_schedule_event(time(), 'every_five_minutes', 'upd_update_post_dates');
    }
}
register_activation_hook(__FILE__, 'upd_schedule_cron');

//------------------------------------------


// Clear the cron job on plugin deactivation
function upd_clear_cron() {
    wp_clear_scheduled_hook('upd_update_post_dates');
    upd_log("UPD Scheduled  Cron Job Cleared");
}
register_deactivation_hook(__FILE__, 'upd_clear_cron');

//-----------------------------------------------------------------


// Add custom interval of 5 minutes
function upd_custom_cron_intervals($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 600, // 10 minutes
        'display'  => __('Every 10 minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'upd_custom_cron_intervals');

//-------------------------------------------------------------


// Function to update post dates
function upd_update_post_dates() {
    $args = array(
        'meta_key'     => '_last_viewed_time',
        'orderby'      => 'meta_value',
        'order'        => 'DESC',
        'posts_per_page' => 10, // Limit updates to 10 posts per run for efficiency
        'post_type'    => 'post',
        'post_status'  => 'publish'
    );

    $query = new WP_Query($args);
    while ($query->have_posts()) {
        $query->the_post();
        $last_viewed = get_post_meta(get_the_ID(), '_last_viewed_time', true);

        if ($last_viewed) {
            $updated_post = array(
                'ID'            => get_the_ID(),
                'post_date'     => $last_viewed,
                'post_date_gmt' => get_gmt_from_date($last_viewed)
            );
            wp_update_post($updated_post);
            upd_log("Cron updating post publish date. Post ID: ". get_the_ID());
            delete_post_meta(get_the_ID(), '_last_viewed_time'); // Clean up meta after update
        }
    }
    wp_reset_postdata();
}
add_action('upd_update_post_dates', 'upd_update_post_dates');


//----------------------------------------------------------------------


//Temporary logging
function upd_log($message)
{
$pluginlog = plugin_dir_path(__FILE__).'debug.log';  
$message.= "\n";
//error_log(current_time('mysql').": $message", 3, $pluginlog);
}
//----—----—-------——-----——------------
