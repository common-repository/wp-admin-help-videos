<?php

class WPAHV_Screen_Recorder {

	public function __construct(){

		add_action( 'admin_menu', function(){
		    add_submenu_page( 
		        null,
		        'Video Controls',
		        'Video Controls',
		        'manage_options',
		        'wpahv-video-controls',
		        array($this,'video_controls')
		    );	
		});

		if( isset($_GET['page']) && $_GET['page'] == 'wpahv-video-controls'){

			add_filter('admin_title', function(){
				return 'Video Controls';
			});

			add_action( 'admin_head', function(){

				wp_localize_script( 'rwp-file-uploader', 'rwp_vars', array('upload_file_nonce' => wp_create_nonce( 'rwp-file-upload' )));	

				$this->hide_admin_bar();

				$this->video_controls();

				$this->video_js();

				die();
			});
		}
	}

	public function hide_admin_bar(){
		echo "<style>html.wp-toolbar{padding-top:0px;}body{min-width:20px!important;}</style>";
	}

	public function video_controls(){

		echo "</head><body>";

		?>

		<div class="rw-contorl-unit-wrapper">
			<div class="rw-contorl-unit">

				<div class="rw-control-title">Video Controls</div>

				<span id="rwpav-start-recording" class="rw-control-icon rw-control-start-icon"></span>

				<span id="rwpav-pause-recording" class="rw-control-icon rw-control-pause-icon" style="display:none;"></span>

				<span id="rwpav-resume-recording" class="rw-control-icon rw-control-resume-icon" style="display:none;"></span>

				<span id="rwpav-stop-recording" class="rw-control-icon rw-control-stop-icon"></span>

			</div>
			
			<span class="spinner" style="float:left;"></span>
			<div id="rw-admin-upload-progress"></div>
		</div>

		<?php		
	}

	public function video_js(){
		?>
		<script>
		jQuery(document).ready(function($){
			// start recording on pageload
			$('#rwpav-start-recording').click();
		});
		</script>
		<?php
	}

}