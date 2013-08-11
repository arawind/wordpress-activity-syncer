<?php
/*
Plugin Name: Syncer
Plugin URI: http://arawind.com
Version: 0.01
Author: Aravind Pedapudi
Description: Syncs your activity on youtube and github to your wordpress server
*/
?><?php

function awSyncGithub() {
      require_once('github.php');
      awGithubSyncer();
}

add_action('admin_post_syncGithub', 'awSyncGithub');

function awRefreshChannel() {
	require_once(plugin_dir_path( __FILE__ ).'youtube.php');	
	awYoutubeSyncer('r');
}

add_action('admin_post_refreshChannel', 'awRefreshChannel');

function awoauther(){
	require_once(plugin_dir_path( __FILE__ ).'youtube.php');	
	awYoutubeSyncer($_GET['case']);
}

add_action('admin_post_oauther', 'awoauther');


function awSyncYoutube() {
	require_once('youtube.php');
	awYoutubeSyncer('s');
	
}

add_action('admin_post_syncYoutube', 'awSyncYoutube');

function aw_createTables(){
   global $wpdb;

   $table_name = $wpdb->prefix . "awsyncerTable";
   $table_github = $wpdb->prefix . "awGithub";
   $table_youtube = $wpdb->prefix . "awYoutube";
   $sql = "CREATE TABLE $table_name (
     id int NOT NULL, 
     timestmp int NOT NULL,
     site text,
     terms text,
     datas text,
     hidden tinyint(1) NOT NULL DEFAULT 0,
     PRIMARY KEY  (id)
   );";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'aw_createTables');

add_action( 'admin_menu', 'awMenu' );

function register_settings() {
    //add_settings_section ( 'awGithub', 'Main Settings', 'plugin_section_text', 'aw-syncer-github' );
    //add_settings_field( 'awGithubPage', 'Page to retrieve', 'displayField', 'aw-syncer-github', 'default', array( 'name' => 'awGithubPage' ) );
    require_once('github.php');
    require_once('youtube.php');
    awGithubRegisterSettings();
    awYoutubeRegisterSettings();
}

add_action( 'admin_init', 'register_settings' );

function awMenu() {
    add_menu_page( 'Syncer Options', 'Syncer', 'manage_options', 'aw-syncer', 'awSyncerOptions' );
    add_submenu_page('aw-syncer', 'Syncer Options' , 'General Options', 'manage_options', 'aw-syncer', 'awSyncerOptions');
    add_submenu_page('aw-syncer', 'Youtube Options' , 'Youtube', 'manage_options', 'aw-syncer-youtube', 'awSyncerYoutube');
    add_submenu_page('aw-syncer', 'GitHub Options' , 'Github', 'manage_options', 'aw-syncer-github', 'awSyncerGithub');
}

function getSslPage($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_REFERER, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function awSyncerOptions() {
        if ( !current_user_can( 'manage_options' ) )  {
           wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
    require_once('general.php');
}
function awSyncerYoutube() {
        if ( !current_user_can( 'manage_options' ) )  {
           wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
    require_once('youtube.php');
    awechoYoutube();
}
function awSyncerGithub() {
        if ( !current_user_can( 'manage_options' ) )  {
           wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

    require_once('github.php');
    awechoGithub();
}

/*
	Hide posts from home page
*/
function aw_hidePostsFromFront($query){
//	if($query->is_home() && $query->is_main_query() || $query->is_day() || $query->is_date() || $query->is_month()){
	if($query->is_home() || $query->is_page()  ){
		$linksCat = get_cat_ID('Links');
		$category = get_cat_ID('Playlists');
		$query->set('cat', '-'.$linksCat.',-'.$category);
	}
}
add_action('pre_get_posts', 'aw_hidePostsFromFront');
?>
