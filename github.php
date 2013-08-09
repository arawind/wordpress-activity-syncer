<?php

function awGithubRegisterSettings(){
	register_setting( 'awGithub', 'awGithubPage' );
	register_setting( 'awGithub', 'awGithubTimestamp' );
}

function awGithubSyncer(){
        $url = get_option('awGithubPage');
        $object = json_decode(getSslPage($url));
        global $wpdb;
        $tableName = $wpdb->prefix . "awsyncerTable";
        $wpdb->insert($tableName, array( 'id'       => NULL,
                                         'hidden'   => 1,
                                         'timestmp' => time(),
                                         'site'     => $object[0]->repo->url,
                                         'datas'    => json_encode($object[0]),
                                         'terms'    => $object[0]->repo->id
                                         ), array( '%d', '%d', '%d', '%s', '%s', '%s'));

        wp_redirect($_POST['_wp_http_referer'].'&updated=true&object='.$object[0]->id);
}
    ?>
<?php 
function awechoGithub(){
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>GitHub General Options</h2>
<form method="post" action="options.php">
   <?php 
        settings_fields('awGithub');
        //do_settings_sections('aw-sync-github');
   ?>
   <label>Page</label>     <input type="text" id="awGithubPage" name="awGithubPage" value="<?php echo get_option('awGithubPage') ?>" />
   <br/>
   <input type="checkbox" id="awGithubTimestamp" name="awGithubTimestamp" value="1" 
   		<?php $thisnew= (get_option('awGithubTimestamp')=="1") ? 'checked' : ''; echo $thisnew  ?> /> 
   		Use server timestamps instead of the GitHub timestamps
   <?php
   submit_button();
   ?>
</form>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="syncGithub" />
    <?php wp_nonce_field(); ?>
    <?php submit_button('Sync Github') ?>
</form>

</div>

<?php }?>
