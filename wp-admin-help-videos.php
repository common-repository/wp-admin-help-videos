<?php
/**
 * Plugin Name:  WP Admin Help Videos
 * Plugin URI:   
 * Description:  Add help videos to the WordPress admin help tabs
 * Author:       raiserweb.com
 * Author URI:   raiserweb.com
 *
 * Version:      1.0.2
 *
 * Text Domain:  help-videos, admin-video, training-video
 * Domain Path:  languages
 *
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * https://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WPAHV_Plugin {

	public function __construct() {

		define('WPAHV_Plugin_URL', plugin_dir_url(__FILE__));

		include_once(trailingslashit( dirname( __FILE__ ) ).'/includes/video-upload-class.php');
		include_once(trailingslashit( dirname( __FILE__ ) ).'/includes/screen-recorder-class.php');
		include_once(trailingslashit( dirname( __FILE__ ) ).'/includes/training-video-cpt-class.php');
		include_once(trailingslashit( dirname( __FILE__ ) ).'/includes/screen-help-class.php');
		include_once(trailingslashit( dirname( __FILE__ ) ).'/includes/video-stream.php');

		$this->init();

		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action( 'admin_bar_menu', array($this, 'toolbar_menu'), 999);
		
	}

	public function init(){

		new WPAHV_Screen_Recorder();

		new WPAHV_Upload();

		new WPAHV_Training_Video_CPT();

		new WPAHV_Screen_Help();

		if(is_admin()){
			new WPAHV_VideoStream();
		}
	}

	public function admin_enqueue_scripts(){
		// for modal
		add_thickbox();

		// css
		wp_enqueue_style( 'rwp-video-training', plugin_dir_url(__FILE__).'assets/css/wpahv-video-training.css' );

		// RTC lib
		wp_enqueue_script( 'rwp-video-training-DetectRTC', plugin_dir_url(__FILE__).'assets/js/record-rtc/DetectRTC.js' );
		wp_enqueue_script( 'rwp-video-training-EBML', plugin_dir_url(__FILE__).'assets/js/record-rtc/EBML.js' );
		wp_enqueue_script( 'rwp-video-training-RecordRTC', plugin_dir_url(__FILE__).'assets/js/record-rtc/RecordRTC.js' );

		// web streams API polyfill to support Firefox
		wp_enqueue_script( 'rwp-video-training-polyfill', plugin_dir_url(__FILE__).'assets/js/record-rtc/polyfill.min.js' );

		// for Edge/FF/Chrome/Opera/etc. getUserMedia support
		wp_enqueue_script( 'rwp-video-training-adapter', plugin_dir_url(__FILE__).'assets/js/record-rtc/adapter.js' );

		// main js
		wp_enqueue_script( 'rwp-video-training', plugin_dir_url(__FILE__).'assets/js/wpahv-video-training.js', ['jquery'] );		
		wp_localize_script( 'rwp-video-training', 'RWPAV_video_training', array('admin_url' => admin_url('index.php') ) );
        wp_localize_script( 'rwp-video-training', 'rwp_vars', array('upload_file_nonce' => wp_create_nonce( 'rwp-file-upload' )));		
	}

	public function toolbar_menu($wp_admin_bar){
		if(!is_admin()){
			return;
		}
	    $args = array(
	        'id' => 'admin-videos',
	        'title' => 'Admin Videos', 
	        'meta' => array(
	            'class' => 'admin-videos', 
	        )
	    );
	    $wp_admin_bar->add_node($args);
	 
	    $args = array(
	        'id' => 'admin-videos-start-recording',
	        'title' => '<span class="rw-start-icon"></span>'.'Start Recording',  
	        'parent' => 'admin-videos', 
	        'href' => '#',
	    );
	    $wp_admin_bar->add_node($args);
	 
	    $args = array(
	        'id' => 'admin-videos-show-preview',
	        'title' => 'Show Last Preview',  
	        'parent' => 'admin-videos', 
	        'href' => '#',
	    );

	    $args = array(
	        'id' => 'admin-videos-view',
	        'title' => 'Saved Videos',  
	        'parent' => 'admin-videos', 
	        'href' => admin_url('edit.php?post_type=admin_help_video'),
	    );

	    $wp_admin_bar->add_node($args);	
	}

}

// start plugin
add_action('init', function(){
	new WPAHV_Plugin();
});
