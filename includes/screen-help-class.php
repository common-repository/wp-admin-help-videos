<?php

class WPAHV_Screen_Help {

	public function __construct(){

        add_action( 'enqueue_block_editor_assets', function(){
        	wp_enqueue_script('wpahv-help-toolbar', WPAHV_Plugin_URL.'assets/js/gutenberg-help.js', array(), '1.0', true );
        });

		add_action('in_admin_header', array($this, 'help_tabs'));

		if( isset($_GET['get_screen_location']) && $_GET['get_screen_location'] == 'true'){
			add_action('current_screen', function(){
				echo json_encode($this->get_current_screen_array());
				exit;
			});
		}
	}

	public static function get_current_screen_array(){
		global $current_screen;
		$screen = get_current_screen();
		return self::make_screen_array($screen);
	}

	public static function get_current_url(){
		return $_SERVER['REQUEST_URI'];
	}

	public static function make_screen_array($screen){
		if(!is_array($screen)){
			$screen = json_encode($screen);
			$screen = json_decode($screen,true);			
		}
		$take_from_screen = array('base','id','post_type','taxonomy');
		$array = array_intersect_key($screen, array_flip($take_from_screen));	
		// convert null to empty string
	    $array = array_map(function($v){
	        return (is_null($v)) ? "" : $v;
	    },$array);		
	    return $array;	
	}

	public function help_tabs(){

		// check if any videos exist for this screen (or url)
		$wp_screen_location = self::get_current_screen_array();

		$videos = get_posts(array(
			'post_type'			=> 'admin_help_video',
			'numberposts'		=> -1,
			'post_status'		=> 'publish',
		    'meta_query' 		=> array(
		        array(
		            'key' => 'screen_location',
		            'value' => serialize($wp_screen_location),
		            'compare' => '='
		        )
		    )

		));

	    if ( !empty($videos) && $screen = get_current_screen()) {

	    	// force help tab to show
	    	echo '<style>#contextual-help-link-wrap{display:block!important;}</style>';	    	

	        $help_tabs = $screen->get_help_tabs();
	        $screen->remove_help_tabs();

	        $content = $this->help_content($videos);

	        $screen->add_help_tab(array(
	            'id' => 'training-videos-tab',
	            'title' => 'Admin Videos',
	            'content' => $content,
	        ));

	        if (count($help_tabs)){
	            foreach ($help_tabs as $help_tab){
	                $screen->add_help_tab($help_tab);
	            }
	        }

	        // this is for gutenberg videos, poped up via js
	        echo "<div id='rw-gberg-help-content' style='display:none;'>".$content."</div>";

	    }	

	}

	public function help_content($videos){

		$content = "<div id='' class='rwpav-help-videos-container'>";

		foreach($videos as $index=>$video){

			$video_file_location = get_post_meta( $video->ID, 'video_file_location', true );
			$video_mime_type = get_post_meta( $video->ID, 'video_mime_type', true );
			$video_description = get_post_meta( $video->ID, 'video_description', true );

			$video_src = WPAHV_VideoStream::make_url($video_file_location);

			$content .= "<div class='rwpav-video-box-wrapper'>";
			$content .= "<div class='rwpav-video-box'>";

			$content .= "<div class='rwpav-video-title'>".$video->post_title."</div>";

			$content .= "<video class='rwpav-video' controls disablePictureInPicture ><source src='".$video_src."' type='".$video_mime_type."' ></video>";

			$content .= "<div>".nl2br($video_description)."</div>";

			$content .= "</div>";
			$content .= "</div>";

		}

		$content .= "</div>";

		return $content;

	}

}