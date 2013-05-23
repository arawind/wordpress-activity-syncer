<?php
/*
Plugin Name: Syncer
Plugin URI: http://arawind.com
Version: 0.01
Author: Aravind Pedapudi
Description: Syncs your activity on youtube and github to your wordpress server
*/
?>

<?php

function aw_createTables(){
   global $wpdb;

   $table_name = $wpdb->prefix . "awsyncerTable";
   $table_github = $wpdb->prefix . "awGithub";
   $table_youtube = $wpdb->prefix . "awYoutube";
   $sql = "CREATE TABLE $table_name (
     id int NOT NULL AUTO_INCREMENT,
     timestmp int NOT NULL,
     site varchar(50) NOT NULL,
     indexes text,
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
    add_settings_section ( 'awGithub', 'Main Settings', 'plugin_section_text', 'aw-syncer-github' );
    add_settings_field( 'awGithubPage', 'Page to retrieve', 'displayField', 'aw-syncer-github', 'default', array( 'name' => 'awGithubPage' ) );
    register_setting( 'awGithub', 'awGithubPage' );
}

add_action( 'admin_init', 'register_settings' );
function plugin_section_text() {
    echo 'abc';
    do_settings_fields( 'aw-syncer-github', 'awGithub' );
}

function awMenu() {
    add_menu_page( 'Syncer Options', 'Syncer', 'manage_options', 'aw-syncer', 'awSyncerOptions' );
    add_submenu_page('aw-syncer', 'Syncer Options' , 'General Options', 'manage_options', 'aw-syncer', 'awSyncerOptions');
    add_submenu_page('aw-syncer', 'Youtube Options' , 'Youtube', 'manage_options', 'aw-syncer-youtube', 'awSyncerYoutube');
    add_submenu_page('aw-syncer', 'GitHub Options' , 'Github', 'manage_options', 'aw-syncer-github', 'awSyncerGithub');
}

function displayField($input){
    echo "abc";
    echo "<input type='text' value='".get_option($input['name'])."' name='".$input['name']."'/>";
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
}
function awSyncerGithub() {
        if ( !current_user_can( 'manage_options' ) )  {
           wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

    require_once('github.php');
}
?>
