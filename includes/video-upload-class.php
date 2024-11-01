<?php

class WPAHV_Upload {

	public function __construct(){

		$this->ajax_actions();

		add_filter( 'admin_footer_text', array($this, 'modal_preview_content') );
	}

	public function ajax_actions(){
		add_action( 'wp_ajax_rw_video_upload_video', array($this, 'upload_video') );
		add_action( 'wp_ajax_rw_video_save_video_details', array($this, 'save_video_details') );
	}


	public function upload_video(){

		 check_ajax_referer( 'rwp-file-upload', 'nonce' );

		$return = array();

		if(!current_user_can('upload_files')) {
			return;
		}

		add_filter( 'upload_dir', array($this, 'set_upload_dir') );

	    $wp_upload_dir = wp_upload_dir();
	    $file_path     = trailingslashit( $wp_upload_dir['path'] ) . sanitize_file_name($_POST['file']);
	    $file_data     = $this->decode_chunk( $_POST['file_data'] );

	    if ( false === $file_data ) {
			$return['error'] = 'An invalid file was supplied.';
			echo json_encode($return);
			die();
	    }

	    file_put_contents( $file_path, $file_data, FILE_APPEND );

		// make htaccess file on first upload
		$this->make_htaccess_once();

		if ( !isset($upload_file['error']) ){

			$return['video_file_url'] = str_replace($wp_upload_dir['wp_basedir'], '', $file_path);
			$return['video_file_direct_src'] = WPAHV_VideoStream::make_url( $return['video_file_url'] );

		} else {

			$return['error'] = $upload_file['error'];

		}
  
	    echo json_encode($return);
		die();
	}

	public function decode_chunk( $data ) {
	    $data = explode( ';base64,', $data );

	    if ( ! is_array( $data ) || ! isset( $data[1] ) ) {
	        return false;
	    }

	    $data = base64_decode( $data[1] );
	    if ( ! $data ) {
	        return false;
	    }

	    return $data;
	}	

	public function save_video_details(){

		if ( !isset( $_POST['admin_help_video_upload'] ) || !wp_verify_nonce( $_POST['admin_help_video_upload'], 'admin_help_video' ) ){
			return;
		}		

		$return = array();

		$video['title'] = sanitize_text_field($_POST['video_title']);
		$video['description'] = sanitize_text_field($_POST['video_description']);
		$video['video_file_url'] = sanitize_text_field($_POST['video_file_url']);

		$screen['screen_location'] = json_decode(stripslashes($_POST['screen_location']), true);
		$screen['screen_location'] = array_map( 'sanitize_text_field', $screen['screen_location'] );
		$screen['screen_location_type'] = sanitize_text_field($_POST['screen_location_type']);

		$upload_dir = wp_get_upload_dir();

		$video_post = array(
			'post_type' 	=> 'admin_help_video',
			'post_title' 	=> $video['title'],
			'post_content' 	=> '',
			'post_status' 	=> 'publish',
			'meta_input' 	=> array(
				'video_file_location' 	=> str_replace( $upload_dir['baseurl'], '', $video['video_file_url']),
				'video_mime_type' 		=> 'video/webm',
				'video_description' 	=> $video['description'],
				'screen_location' 		=> $screen['screen_location'],
				'screen_location_type' 	=> $screen['screen_location_type'],
			)
		);

		$post_id = wp_insert_post($video_post);

		echo json_encode(array(
			'post_id' => $post_id
		));

		die();

	}

	public function make_htaccess_once(){
		$dir = wp_get_upload_dir();

		$location = $dir['basedir'].'/.htaccess';

		if(file_exists($location)){
			return;
		}

		$content = 'deny from all' . "\n";
		file_put_contents($location, $content);
	}

	public function set_upload_dir($upload){

		$save_folder = '/admin-help-videos';

		$upload['path'] = str_replace( $upload['subdir'], $save_folder.$upload['subdir'], $upload['path'] );
		$upload['url'] = str_replace( $upload['subdir'], $save_folder.$upload['subdir'], $upload['url'] );

		$upload['wp_basedir'] = $upload['basedir'];

		$upload['basedir'] = $upload['basedir'].$save_folder;
		$upload['baseurl'] = $upload['baseurl'].$save_folder;

		$upload['subdir'] = $save_folder;

		return $upload;		
	}

	public function make_file_name(){
		return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1,10))), 1, 10);
	}

	public function modal_preview_content(){

		$screen = WPAHV_Screen_Help::get_current_screen_array();	
		?>

		<div id="rw-admin-video-preview" style="display:none;">
			<div id="rw-admin-new-video-form">
				<div id="rw-admin-video-player"></div>

				<form id="rw-admin-video-new" name="rw-admin-video-new">

					<?php wp_nonce_field( 'admin_help_video', 'admin_help_video_upload' ); ?>

					<input type="hidden" name="screen_location" id="screen_location" value='<?php _e(json_encode($screen));?>' >
					<input type="hidden" name="screen_location_type" value="WP_Screen" >
					<input type="hidden" name="video_file_url" id="video_file_url">

					<table class="form-table">
						<tbody>		
							<tr>
								<th style="width:100px;" scope="row">Video Title</th>
								<td>
									<input type="text" name="video_title" style="width:100%">
								</td>
							</tr>
							<tr>
								<th style="width:100px;" scope="row">Description</th>
								<td>
									<textarea name="video_description" style="width:100%" rows="5"></textarea>
								</td>
							</tr>
							<tr>
								<td></td>
								<td><button id="rw-admin-save-video" class="button button-primary">Save Video</button><span class="spinner"></span></td>
							</tr>
						</tbody>
					</table>

				</form>
			</div>
			<div id="rw-admin-new-video-complete" style="display:none;">
				<div style="text-align: center;">
					Video Saved!
					<br><br>
					<div id="rw-admin-modal-close"><div class="button">Close</div></div>
				</div>

			</div>

		</div>
		<?php		
	}

}