<?php

class YoutubePlaylist extends WP_Widget {

	public function __construct() {
		$widgOptions = array('classname'=>'YoutubePlaylist', 'description'=> 'Shows the playlists');
		$this->WP_Widget('YoutubePlaylist', 'Youtube Playlist', $widget_ops);
	}

	public function widget( $args, $instance ) {
		extract($args, EXTR_SKIP);
	 
		echo $before_widget;
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		 
		if (!empty($title))
			echo $before_title . $title . $after_title;;
		 
		    // Do Your Widgety Stuff Here...
		$plistIDs = get_option('awYoutubePlaylistIDs');
		$plistTitles = get_option('awYoutubePlaylistTitles');
		$plist = array_combine($plistIDs, $plistTitles);
		
		echo "<select id='awSelecter'>";
		
		foreach($plist as $id => $value){
			?>
			<option id = "<?php echo $id ?>" > <?php echo $value ?> </option>
			<?php
		}
		
		echo "</select>";	
		foreach($plist as $id => $value){
			$filteredVal = implode('-',explode(' ', $value));
			$argus = (array('tag'=>$filteredVal, 'showposts'=>5));
			$myQuery = new WP_Query($argus);
			$termid = get_term_by('name',$filteredVal,'post_tag')->term_id;
			if($termid)
				$link = get_tag_link($termid);
			else
				$link = '';
			
			if($myQuery->have_posts()){
				echo $link;
				echo "<ul class='awYoutubeList' id='list$id'>";
				
				while($myQuery->have_posts()) { 
					$myQuery->the_post();
					 $str = get_the_content();
					 preg_match_all('/<a[^>]+href=([\'"])(.+?)\1[^>]*>/i', $str, $m);
					 echo "<li> <a href='{$m[2][0]}' target='_blank'>".get_the_title()."</a></li>";
				
				}
				if($link)
					echo "<small><a href='$link'> View All </a></small>";
				echo "</ul>";
			}
			wp_reset_query();  
		}
		 
		echo $after_widget;
	}

 	public function form( $instance ) {
		$instance = wp_parse_args((array) $instance, array( 'title' => '' ));
		$title = $instance['title'];
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">Title:
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
		</label></p>
		
<?php
  	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}
}

function aw_regWidget(){
	register_widget( 'YoutubePlaylist' );
	if(!is_admin()){
		//wp_enqueue_style('awStyle', plugins_url('style.css', __FILE__));
		wp_enqueue_script('awScript', plugins_url('script.js', __FILE__), array('jquery'));
	}
}

?>
