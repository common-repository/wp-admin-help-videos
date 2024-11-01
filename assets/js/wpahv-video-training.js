jQuery(document).ready(function($){

	// click from admin menu
	$('#wp-admin-bar-admin-videos-start-recording').click(function(e){
		e.preventDefault();

		var screen_location = $('#screen_location').val();

		// open video controls
		window.open(RWPAV_video_training.admin_url+'?page=wpahv-video-controls&screen_location='+screen_location, '_blank', 'width=740,height=525,menubar=0,status=0,titlebar=0');
	    return false;
	});

	// click from add video page
	$('#video-start-recording').click(function(e){
		e.preventDefault();

		var screen_location = 'no_modal';

		// open video controls
		window.open(RWPAV_video_training.admin_url+'?page=wpahv-video-controls&screen_location='+screen_location, '_blank', 'width=740,height=525,menubar=0,status=0,titlebar=0');
	    return false;
	});

    window.resize_video_control_window = function (video_control_window,type) {  
    	if(type == 'small'){
			video_control_window.resizeTo("200", "150");
    	} else if (type == 'preview'){
    		video_control_window.resizeTo("740", "560");
    	}
   	} 
   	window.close_video_control = function(video_control_window){
   		video_control_window.close();
   	}

   	window.finish_recording = function(video_control_window,response, screen_location){

   		$('#wp-admin-bar-admin-videos-show-preview').show();

   		// close controls
   		video_control_window.close();

   		// make preview
   		video = make_video_element(response.video_file_direct_src);
		$('#rw-admin-video-player').html(video);	

		// append to form
		$('#video_file_url').val(response.video_file_url)

		// append original screen location to form
		$('#screen_location').val(screen_location);

		// show modal
		if( screen_location != 'no_modal' ){
			show_preview_modal();
		}
   	}

   	window.error_message = function(video_control_window, error){

    	// close controls
   		video_control_window.close();
   		
   		alert(error);

   	}


   	// hide on page load
	$('#wp-admin-bar-admin-videos-show-preview').hide();

	$('#rwpav-start-recording').click(function(e){
		e.preventDefault();
		
		start_recording(function(){ // success

			// make window small
			opener.resize_video_control_window(window,'small');

			$('#rwpav-start-recording').hide();
			$('#rwpav-pause-recording').show();


		}, function(){ // failed

			// close window
        	opener.close_video_control(window);
		});
	});

	$('#rwpav-pause-recording').click(function(e){
		e.preventDefault();

		pause_recording(function(){

			$('#rwpav-pause-recording').hide();
			$('#rwpav-resume-recording').show();

		});

	});

	$('#rwpav-resume-recording').click(function(e){
		e.preventDefault();

		resume_recording(function(){

			$('#rwpav-pause-recording').show();
			$('#rwpav-resume-recording').hide();

		});

	});	

	$('#rwpav-stop-recording').click(function(e){
		e.preventDefault();

		$('.rw-contorl-unit-wrapper').find('.spinner').addClass('is-active');

		stop_recording(function(){

			$('.rw-contorl-unit').hide();

			// upload the video
			upload_video(function(response){

				if( typeof response.error != 'undefined' ){
					opener.error_message(window, response.error);
					return;
				}

				// get screen location from get param
				var urlParams = new URLSearchParams(window.location.search);
				var screen_location = urlParams.get('screen_location');

				opener.finish_recording(window, response, screen_location);

			});

		});

	});	


	$('#rw-admin-save-video').click(function(e){
		e.preventDefault();

		$(this).prop('disabled', true);
		$('#rw-admin-new-video-form').find('.spinner').addClass('is-active');

		save_video_details();
	});


	// setTimeout(function(){
	// 	show_preview_modal();
	// },50);

	$('#wp-admin-bar-admin-videos-show-preview').click(function(e){
		show_preview_modal();
	});

	function show_preview_modal(){
		tb_show('Preview', '#TB_inline?width=600&height=350&inlineId=rw-admin-video-preview&width=720&height=800');
	}

	$('#rw-admin-modal-close').click(function(){
		tb_remove('Preview');
	});


	///////////////////////////////////////////////////////////////////////////
	//
	// video functions
	//
	////////////////////////////////////////////////////////////////////////////

	// for recorder
	var recordRTC;
	var stream;

	// for chunking upload
	var reader = {};
    var file = {};
    var slice_size = 1000 * 1024;

    // upload in chunks
    function upload_file( start, successCallback ) {

	    var next_slice = start + slice_size + 1;
	    var blob = file.slice( start, next_slice );

	    reader.onloadend = function( event ) {
	        if ( event.target.readyState !== FileReader.DONE ) {
	            return;
	        }

	        $.ajax( {
	            url: ajaxurl,
	            type: 'POST',
	            dataType: 'json',
	            cache: false,
	            data: {
	                action: 'rw_video_upload_video',
	                file_data: event.target.result,
	                file: file.name,
	                file_type: file.type,
	                nonce: rwp_vars.upload_file_nonce
	            },
	            error: function( jqXHR, textStatus, errorThrown ) {
	            	opener.resize_video_control_window(window,'preview');
	                alert(textStatus);
	            },
	            success: function( data ) {

	                var size_done = start + slice_size;
	                var percent_done = Math.floor( ( size_done / file.size ) * 100 );

	                if ( next_slice < file.size ) {
	                    // Update upload progress
	                    $('#rw-admin-upload-progress').html( percent_done+'%' );

	                    // More to upload, call function recursively
	                    upload_file( next_slice, successCallback);
	                } else {

	                	// finished
	                	successCallback(data);
	                }
	            }
	        });
	    };

	    reader.readAsDataURL( blob );
    }


	function upload_video(successCallback){

        reader = new FileReader();

		var blob = recordRTC.getBlob();
		var fileName = make_file_name()+'.webm';

		file = new File([blob], fileName, {
		    type: 'video/webm'
		});

        upload_file( 0, successCallback );

		// var blob = recordRTC.getBlob();

		// var fileName = 'video.webm';

		// var fileObject = new File([blob], fileName, {
		//     type: 'video/webm'
		// });

		// var formData = new FormData();
		// formData.append('action', 'rw_video_upload_video' );
		// formData.append('video-blob', fileObject);

	 //    return $.ajax({
	 //        url: ajaxurl, 
	 //        data: formData,
	 //        cache: false,
	 //        contentType: false,
	 //        processData: false,
	 //        type: 'POST',
	 //        success: function(response) {

	 //        	var data = JSON.parse(response);

	 //        	successCallback(data);

	 //        }
	 //    });		

	}

	function make_file_name(){
		var length 		     = 12;
		var result           = '';
		var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var charactersLength = characters.length;
		for ( var i = 0; i < length; i++ ) {
			result += characters.charAt(Math.floor(Math.random() * charactersLength));
		}
		return result;		
	}

	function save_video_details(){
		var formData = new FormData( document.getElementById('rw-admin-video-new') );
		formData.append('action', 'rw_video_save_video_details' );

	    return $.ajax({
	        url: ajaxurl, 
	        data: formData,
	        cache: false,
	        contentType: false,
	        processData: false,
	        type: 'POST',
	        success: function(response) {

	        	var data = JSON.parse(response);

	        	if( typeof data.post_id != 'undefined' ){
	        		$('#rw-admin-new-video-form').hide();
	        		$('#rw-admin-new-video-complete').show();
	        		return;
	        	} 

	        	// error
	        	alert('There has been an error');
	        	
	        }
	    });		

	}

	function start_recording(successCallback,failedCallback){

		if (navigator.getDisplayMedia) {
			var obj = navigator;
		} else if (navigator.mediaDevices.getDisplayMedia) {
			var onj = navigator.mediaDevices;
		} else {
            var error = 'getDisplayMedia API are not supported in this browser.';
            alert(error);
            return false;
        }

		return onj.getDisplayMedia({
            video: true
        }).then(screenStream => {

        	stream = screenStream;

            navigator.mediaDevices.getUserMedia({audio:true}).then(function(mic) {

                screenStream.addTrack(mic.getTracks()[0]);

                var options = {
                    type: 'video',
                    mimeType: 'video/webm',
                    disableLogs: false,
                    getNativeBlob: false, // enable it for longer recordings
                    //video: $('#recording-player'),
                    ignoreMutedMedia: false
                };

                recordRTC = RecordRTC(screenStream, options);

                recordRTC.onStateChanged.call = function(self, state){
                	if( state != undefined && state == 'recording'){
                		successCallback();
                	}
                }

                setTimeout(function(){
                	recording = recordRTC.startRecording();
                },100);
                

                addStreamStopListener(screenStream, function() {
                    $('#rwpav-stop-recording').click();
                });

            });

        }).catch(function(error) {
        	failedCallback();
        });   		
	}

	function stop_recording(successCallback){

		recordRTC.stopRecording(function(url) {

	        getSeekableBlob(recordRTC.getBlob(), function(seekableBlob) {

	            recordRTC.getBlob = function() {
	                return seekableBlob;
	            };

	            stream.url = window.URL.createObjectURL(seekableBlob);

	            stream.stop();

	            successCallback();

	        });

	    });

	}

	function pause_recording(successCallback){

        recordRTC.onStateChanged.call = function(self, state){
        	if( state != undefined && state == 'paused'){
        		successCallback();
        	}
        }
		recordRTC.pauseRecording();
	}

	function resume_recording(successCallback){

        recordRTC.onStateChanged.call = function(self, state){
        	if( state != undefined && state == 'recording'){
        		successCallback();
        	}
        }
		recordRTC.resumeRecording();
	}

    function make_video_element(arg) {
        var url = getURL(arg);

        $('#recording-player').html('');

        recordingPlayer = document.createElement('video');
        recordingPlayer.setAttribute("controls", "controls");
        recordingPlayer.disablePictureInPicture = true;
        
        // recordingPlayer.addEventListener('loadedmetadata', function() {
        //     if(navigator.userAgent.toLowerCase().indexOf('android') == -1) return;

        //     // android
        //     setTimeout(function() {
        //         if(typeof recordingPlayer.play === 'function') {
        //             recordingPlayer.play();
        //         }
        //     }, 2000);
        // }, false);

        //recordingPlayer.poster = '';

        if(arg instanceof MediaStream) {
            recordingPlayer.srcObject = arg;
        } else {
            recordingPlayer.src = url;
        }

        if(typeof recordingPlayer.play === 'function') {
            //recordingPlayer.play();
        }

        return recordingPlayer;
    }  	

    function getURL(arg) {
        var url = arg;

        if(arg instanceof Blob || arg instanceof File) {
            url = URL.createObjectURL(arg);
        }

        if(arg instanceof RecordRTC || arg.getBlob) {
            url = URL.createObjectURL(arg.getBlob());
        }

        if(arg instanceof MediaStream || arg.getTracks) {
            // url = URL.createObjectURL(arg);
        }

        return url;
    }
    function addStreamStopListener(stream, callback) {
        stream.addEventListener('ended', function() {
            callback();
            callback = function() {};
        }, false);
        stream.addEventListener('inactive', function() {
            callback();
            callback = function() {};
        }, false);
        stream.getTracks().forEach(function(track) {
            track.addEventListener('ended', function() {
                callback();
                callback = function() {};
            }, false);
            track.addEventListener('inactive', function() {
                callback();
                callback = function() {};
            }, false);
        });
    }	    

});