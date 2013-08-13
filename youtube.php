<?php
function awYoutubeRegisterSettings(){
	register_setting( 'awYoutube', 'awYoutubeChannelID' );
	register_setting( 'awYoutube', 'awYoutubePlaylistIDs' );
	register_setting( 'awYoutube', 'awYoutubePlaylistTitles' );
}

function truncate($text, $limit) {
	if (str_word_count($text, 0) > $limit) {
		$words = str_word_count($text, 2);
		$pos = array_keys($words);
		$text = substr($text, 0, $pos[$limit]) . '...';
	}
	return $text;
}

function awYoutubeSyncer($case){

	session_start();
	
	/* You can acquire an OAuth 2 ID/secret pair from the API Access tab on the Google APIs Console
	 <http://code.google.com/apis/console#access>
	For more information about using OAuth2 to access Google APIs, please visit:
	<https://developers.google.com/accounts/docs/OAuth2>
	Please ensure that you have enabled the YouTube Data API for your project. */
	require_once(plugin_dir_path( __FILE__ ).'src/Google_Client.php');
	require_once(plugin_dir_path( __FILE__ ).'src/contrib/Google_YouTubeService.php');
	
	$OAUTH2_CLIENT_ID = 'id here';
	$OAUTH2_CLIENT_SECRET = 'secret here';
	
	
	$client = new Google_Client();
	$client->setClientId($OAUTH2_CLIENT_ID);
	$client->setClientSecret($OAUTH2_CLIENT_SECRET);
	$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?action=oauther&case=' . $case,
			FILTER_SANITIZE_URL);
	$client->setRedirectUri($redirect);
	
	$youtube = new Google_YoutubeService($client);
	
	
	if (isset($_GET['code'])) {
		if (strval($_SESSION['state']) !== strval($_GET['state'])) {
			die('The session state did not match.');
		}
	
		$client->authenticate();
		$_SESSION['token'] = $client->getAccessToken();
		header('Location: ' . $redirect);
	}
	
	if (isset($_SESSION['token'])) {
		$client->setAccessToken($_SESSION['token']);
	}
	
	
	if ($client->getAccessToken()) {
		try {
			switch ($case){
				case 'r':
					$channel = $_POST['awYoutubeChannelID'];
					if($channel == NULL)
						return;
					$playlistsResponse = $youtube->playlists->listPlaylists('id,snippet,status', array(
							'channelId' => $channel,
							'maxResults'=> 50
					));
					$htmlBody = '';
					$playlistIDs = array();
					$playlistTitles = array();
					foreach ($playlistsResponse['items'] as $playlist) {
						if($playlist['status']['privacyStatus'] == "public"){
							$playlistId = $playlist['id'];
							$playlistIDs[] = $playlistId;
							$playlistTitle = $playlist['snippet']['title'];
							$playlistTitles[] = $playlistTitle;
					
							//$htmlBody.="<p>$playlistTitle - $playlistId</p>";
						}
					}
					$favList = substr_replace($channel, 'FL', 0, 2);
					$playlistsResponse = $youtube->playlists->listPlaylists('snippet', array(
							'id' => $favList
					));
					if($playlistsResponse['items'][0]['snippet']['title']){
						//$htmlBody.="<p>".$playlistsResponse['items'][0]['snippet']['title']." - $favList</p>";
						$playlistIDs[] = $favList;
						$playlistTitles[] = $playlistsResponse['items'][0]['snippet']['title'];
					}
					$pIds = ($playlistIDs);
					$pTitles = ($playlistTitles);
					update_option('awYoutubePlaylistIDs', $pIds);
					update_option('awYoutubePlaylistTitles', $pTitles);
					update_option('awYoutubeChannelID', $_POST['awYoutubeChannelID']);
					break;
				case 's':
					$pIDs = $_POST['awYoutubeSelected'];
					$nextpage = NULL;
					$linksCat = wp_create_category('Links');
					$category = wp_create_category('Playlists', $linksCat);
					$plistIDs = get_option('awYoutubePlaylistIDs');
					$plistTitles = get_option('awYoutubePlaylistTitles');
					$plist = array_combine($plistIDs, $plistTitles);
					
					foreach($pIDs as $playlistID){
						/*$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
								'playlistId' => $playlistID,
								'maxResults' => 50
						));*/
						/*foreach ($playlistItemsResponse['items'] as $playlistItem) {
							$htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
									$playlistItem['snippet']['resourceId']['videoId']);
						}
						$i=0;*/
						$posts = array();
						set_time_limit(30);
						
						do{
							$post = array();
							$query = array(
									'playlistId' => $playlistID,
									'maxResults' => 50
							);
							if($nextpage!='')
								$query['pageToken'] = $nextpage;
							$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('id,snippet', $query);
							//var_dump($nextpage);
							$nextpage = $playlistItemsResponse['nextPageToken'];

							foreach ($playlistItemsResponse['items'] as $playlistItem) {
								
								$post['post_content']= sprintf('<p><a href="%s" target="_blank"> <img src = "%s" /> </a></p> ', "https://www.youtube.com/watch?v=".$playlistItem['snippet']['resourceId']['videoId']."&list=".$playlistID,
										$playlistItem['snippet']['thumbnails']['default']['url']);
								$post['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($playlistItem['snippet']['publishedAt']));
								$calcTime = strtotime($playlistItem['snippet']['publishedAt']) + strtotime(current_time('timestamp')) -strtotime(current_time('timestamp'), 1) ;
								$post['post_date'] = date("Y-m-d H:i:s", $calcTime); 
								$post['post_name'] = strtolower($playlistItem['id']);
								$post['post_title'] = truncate($playlistItem['snippet']['title'], 7);
								$post['post_type'] = 'post';
								$post['post_status'] = 'publish';
								$post['tags_input'] = array('YouTube', $plist[$playlistID]);
								$posts[] = $post;
								
							}
							set_time_limit(30);
							
							
						}while($nextpage!=NULL);
						
						//write posts to db
						
						$allposts = get_posts(array(
							'category' => $category,
							'posts_per_page' => -1								
						));
						$postnames = array();
						foreach($allposts as $post){
							$postnames[] = $post->post_name;
							
						}
						//var_dump($postnames);
						$uid = get_current_user_id();
						foreach($posts as $post){
							
							//insert into db
							//echo $post['post_name'];
							//var_dump(in_array($post['post_name'], $postnames));
							//echo '<br>';
							if(!in_array($post['post_name'], $postnames)){
								$post['post_author'] = $uid;
								$postid = wp_insert_post($post, true);
								//var_dump($postid);
								wp_set_post_terms($postid, array($category), 'category');
								wp_set_post_terms($postid, $post['tags_input'], 'post_tag', true);
							}
						}
						
						//echo 'ending';
						
						
					}
					break;
			}
			
	
			
		} catch (Google_ServiceException $e) {
			$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
					htmlspecialchars($e->getMessage()));
		} catch (Google_Exception $e) {
			$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
					htmlspecialchars($e->getMessage()));
		}
	
		$_SESSION['token'] = $client->getAccessToken();
		
	
	
	}
	
	else {
		$state = mt_rand();
		$client->setState($state);
		$_SESSION['state'] = $state;
	
		$authUrl = $client->createAuthUrl();
	
		$htmlBody = "<h3>Authorization Required</h3>
		<p>You need to <a href=".$authUrl.">authorize access</a> before proceeding.<p>
		";
	
	}
	if($htmlBody)
		echo $htmlBody;
	else
		wp_redirect($_SERVER['HTTP_REFERER']);
}

?>
<?php function awechoYoutube(){
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>Youtube Options</h2>


<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="refreshChannel" />
    <?php wp_nonce_field(); ?>
	<p>
	<label for="awYoutubeChannelID">Enter youtube channel ID</label>
	<input type="text" name="awYoutubeChannelID" id="awYoutubeChannelID" value="<?php echo get_option('awYoutubeChannelID') ?>" size="30"/>
	</p>
    <?php submit_button('Refresh Channel List') ?>
</form>

<form method="post" action="<?php echo admin_url('admin-post.php') ?>">
	
	<input type="hidden" name="action" value="syncYoutube" />
	
    <?php 
    	$pIds = get_option('awYoutubePlaylistIDs');
    	$pTitles = get_option('awYoutubePlaylistTitles');
    	//if($pIds && $pTitles){
    	$playlists = array_combine($pIds, $pTitles);
    	foreach($playlists as $id => $title){
			?>
				<p><input type="checkbox" name="awYoutubeSelected[]" value="<?php echo $id ?>" /> <?php echo $title?> </p>
			<?php 
		}
    	//print_r(get_option('awYoutubePlaylistIDs'));
    	//print_r(get_option('awYoutubePlaylistTitles'));
    ?>
    <?php submit_button('Sync Playlists'); ?>
</form>


</div>

<?php } ?>
