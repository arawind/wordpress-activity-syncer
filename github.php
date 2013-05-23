<?php
    function awGithubSyncer() {
       //curl the page
       $url = get_option('awGithubPage');

       global $wpdb;
       $tableName = $wpdb->prefix . "awSyncerTable";

       
    }

    add_action('syncGithub', 'awGithubSyncer');
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>Syncer General Options</h2>
<form method="post" action="options.php">
   <?php 
        settings_fields('awGithub');
        do_settings_sections('aw-sync-github');
   ?>
   <label>Page</label>     <input type="text" id="awGithubPage" name="awGithubPage" value="<?php echo get_option('awGithubPage') ?>" />
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
