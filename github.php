<?php

function awGithubRegisterSettings(){
	register_setting( 'awGithub', 'awGitHubHandler' );
	//register_setting( 'awGithub', 'awGithubTimestamp' );
}

function awGithubSyncer(){
	require_once('simple_html_dom.php');
        $url = "https://github.com/".get_option('awGitHubHandler').".atom";
        //$object = json_decode(getSslPage($url));
        $html = file_get_html($url);
        
        $linksCat = wp_create_category('Links');
	$category = wp_create_category('GitHub', $linksCat);
        $posts = array();
       	foreach($html->find('content') as $element){
       		$post = array();
       		//echo "<div class='content'>";
       		$inhtml = str_get_html(html_entity_decode($element->innertext));
       		foreach($inhtml->find('a') as $inelement){
       			$inelement->href = "https://github.com". $inelement->href;
       			$inelement->target = "_blank";
       		}
       		$inhtmlNew = "<p>" . $inhtml->find('div.time', 0)->innertext . "</p>";
       		$time = $inhtml->find('div.time time', 0)->datetime;
       		$inhtmlNew .= "<p>".$inhtml->find('div.title', 0)->innertext;
       		$postTitle = $inhtml->find('div.title', 0)->plaintext;
       		
       		if($inhtml->find('div.details', 0)){
       			foreach($inhtml->find('div.details div.commits') as $commits){
       				foreach($commits->find('ul li') as $lielem){
	       				$lielem->find('code a', 0)->href = "https://github.com".$lielem->find('code a', 0)->href;
       					$inhtmlNew .= "; - ".$lielem->find('code', 0)->innertext. " - ". $lielem->find('div.message blockquote', 0)->innertext ;
       				}
       			}
       		}
       		$inhtmlNew .=" </p>";
       		$post['post_content'] = $inhtmlNew;
       		$post['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($time));
		$calcTime = strtotime($time) + strtotime(current_time('timestamp')) -strtotime(current_time('timestamp'), 1) ;
		$post['post_date'] = date("Y-m-d H:i:s", $calcTime); 
		$post['post_name'] = md5($time);
		$post['post_title'] = $postTitle;
		$post['post_type'] = 'post';
		$post['tags_input'] = array('GitHub');
		$post['post_status'] = 'publish';
		$posts[] = $post;
       		//echo $inhtmlNew;
       		//echo "</div>";
       	}
       	
       	$allposts = get_posts(array(
			'category' => $category,
			'posts_per_page' => -1								
		));
	$postnames = array();
	foreach($allposts as $post){
		$postnames[] = $post->post_name;
	
	}
	
	$uid = get_current_user_id();
	foreach($posts as $post){
		if(!in_array($post['post_name'], $postnames)){
			$post['post_author'] = $uid;
			$postid = wp_insert_post($post, true);
			//var_dump($postid);
			wp_set_post_terms($postid, array($category), 'category');
			wp_set_post_terms($postid, $post['tags_input'], 'post_tag', true);
		}
	}
        
        

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
   <label>Handler</label><input type="text" id="awGitHubHandler" name="awGitHubHandler" placeholder="arawind" value="<?php echo get_option('awGitHubHandler') ?>" />
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
