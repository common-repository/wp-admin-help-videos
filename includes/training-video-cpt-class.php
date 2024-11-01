<?php

class WPAHV_Training_Video_CPT {

	public function __construct(){

		$this->register_cpt();

	}

	public function register_cpt(){

		register_post_type( 'admin_help_video', array(
			'labels' => array(
				'name' 				=> 'Admin Help Videos',
				'singular_name' 	=> 'Admin Help Video',
				'edit_item' 		=> 'Edit Admin Help Video',
				'add_new_item' 		=> 'Add an Admin Help Video',
			),
			/* edit but not add new */
			'capabilities' => array(
				'create_posts' => 'do_not_allow',
			),	
			'map_meta_cap' 		=> true,
			/* .edit but not add new */	
			'public' 			=> false,
			'publicly_queryable' => false,
			'show_ui' 			=> true,
			'show_in_menu' 		=> false,
			'show_in_admin_bar' => false,
			'query_var' 		=> false,
			'supports' 			=> array('title')
		));

		add_action('add_meta_boxes_admin_help_video', array($this, 'add_meta_boxes'));
		add_action('save_post_admin_help_video', array($this, 'save_admin_video'));

		add_action('before_delete_post', array($this, 'delete_admin_help_video') );

		
	}

	public function add_meta_boxes(){
		add_meta_box('wpahv_help_video_description_meta_box', 'Description', array($this, 'description_meta'), '', 'normal', 'low' );
		add_meta_box('wpahv_help_video_video_meta_box', 'Video', array($this, 'video_meta'), '', 'normal', 'low' );
		add_meta_box('wpahv_help_video_location_meta_box', 'Video Location', array($this, 'video_location'), '', 'side', 'low' );
	}


	public function description_meta(){

		wp_nonce_field( basename( __FILE__ ), 'admin_help_video' );

		$video_description = get_post_meta( get_the_ID(), 'video_description', true );
		?>
		<div class="map-my-locations">
			<div class="row">
				<div class="col">
				<?php

				$settings = array( 'media_buttons' => false,
					'teeny' 		=> true,
					'tinymce' 		=> array(
						'toolbar1' 					=> 'bold,italic,underline,undo,redo',
						'paste_as_text' 			=> true,
						'paste_text_sticky' 		=> true,
						'paste_text_sticky_default' => true,
					),
					'editor_height' => 200,
					'quicktags' 	=> false
				);

				wp_editor( $video_description, 'video_description', $settings );
				?>
				</div>
			</div>
		</div>
		<?php		
	}

	public function video_meta(){

		$video_file_location = get_post_meta( get_the_ID(), 'video_file_location', true );
		$video_mime_type = get_post_meta( get_the_ID(), 'video_mime_type', true );

		$dir = wp_get_upload_dir();

		if( $video_file_location ){
		?>
			<video width="800" controls disablePictureInPicture >
				<source src="<?php echo WPAHV_VideoStream::make_url($video_file_location);?>" type="<?php _e($video_mime_type);?>">
			</video>
		<?php
		}

		if(!isset($_GET['post'])){
		?>
		<div id="video-start-recording" >Record New Video</div>
		<div id="rw-admin-video-player" style="width:800px;"></div>
		<?php
		}

	}

	public function delete_admin_help_video($post_id){

		// check post type
		if ( 'admin_help_video' != get_post_type( $post_id ) ){
			return $post_id;
		}

		// check nonce
		if ( !isset( $_GET['_wpnonce'] ) || !wp_verify_nonce( $_GET['_wpnonce'], 'delete-post_'.$post_id ) ){
			return $post_id;
		}

		// delete video file
		$dir = wp_get_upload_dir();
		$video_file_location = get_post_meta( $post_id, 'video_file_location', true );
		if($video_file_location != '' ){
			unlink($dir['basedir'].$video_file_location);
		}

	}

	public function save_admin_video($post_id){

		if ( !isset( $_POST['admin_help_video'] ) || !wp_verify_nonce( $_POST['admin_help_video'], basename( __FILE__ ) ) ){
			return $post_id;
		}

		if( isset($_POST['video_description']) ){

			$video_description = sanitize_text_field($_POST['video_description']);

			update_post_meta( $post_id, 'video_description', $video_description );
		}

		if( isset($_POST['screen_location']) && $_POST['screen_location'] != '' ){

			$screen_location = json_decode(stripslashes($_POST['screen_location']), true);
			$screen_location = array_map( 'sanitize_text_field', $screen_location );

			update_post_meta( $post_id, 'screen_location', $screen_location );

		}
		
	}

	public function video_location(){

		$screen_location_type = get_post_meta( get_the_ID(), 'screen_location_type', true );
		$screen_location = get_post_meta( get_the_ID(), 'screen_location', true );

		echo "<div id='rwpav_existing_screen'>";
			
		switch ($screen_location_type) {
		    case 'WP_Screen':
		        
		    	echo "<strong>WP Screen Data</strong><br><br>";

		    	foreach( $screen_location as $key=>$val ){
		    		if( $val == '' ){
		    			continue;
		    		}
		    		echo "<strong>".__($key).";</strong>&nbsp;".__($val)."<br>";
		    	}

		        break;
		
		}

		echo "<br><div class='button' id='rwpav_change_video_location'>Change</div>";
		echo "</div>";
		?>

		<div id="rwpav_new_screen" style="display:none;">
			<label>New admin page URL:</label><span id="rwpav_new_screen_cancel" style="float:right;cursor: pointer;">&#10005;</span>
			<br><br>
			<input type="text" id="rwpav_admin_page_url" style="width:100%;" placeholder="Paste full URL">
			<input type="hidden" name="screen_location_type" value="WP_Screen">
			<input type="hidden" name="screen_location" id="rwpav_screen_location" value="">
		</div>
		
		<script>
		jQuery(document).ready(function($){
			$('#rwpav_change_video_location').click(function(){
				$('#rwpav_existing_screen').hide();
				$('#rwpav_new_screen').show();
			});
			$('#rwpav_new_screen_cancel').click(function(){
				$('#rwpav_existing_screen').show();
				$('#rwpav_new_screen').hide();
			});

			$('#rwpav_admin_page_url').change(function(){

				var url = $(this).val();

				url += (url.match(/\?/) ? '&' : '?') + 'get_screen_location=true';

			    $.ajax({
			        url: url, 
			        type: 'GET',
			        success: function(response) {
			        	$('#rwpav_screen_location').val(response)
			        }
			    });						

			});

		});
		</script>
		<?php

	}

}