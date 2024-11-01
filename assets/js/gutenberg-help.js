window.wpahv_show_help_videos = function(e){
	e.preventDefault();
	tb_show('Help Videos', '#TB_inline?width=600&height=350&inlineId=rw-gberg-help-content&width=720&height=550');
}

setTimeout(function(){

    if( !jQuery('#rw-gberg-help-content').length ){
        return;
    }

    // custom-link-in-toolbar.js
    // wrapped into IIFE - to leave global space clean.
    ( function( window, wp ){

        // just to keep it cleaner - we refer to our link by id for speed of lookup on DOM.
        var link_id = 'wpahv-gpage-help-videos';

        // prepare our custom link's html.
        var link_html = '<a id="' + link_id + '" class="components-button" href="#" onCLick="wpahv_show_help_videos(event);" >Help Videos</a>';

        // check if gutenberg's editor root element is present.
        var editorEl = document.getElementById( 'editor' );
        if( !editorEl ){ // do nothing if there's no gutenberg root element on page.
            return;
        }

        var unsubscribe = wp.data.subscribe( function () {
            setTimeout( function () {
                if ( !document.getElementById( link_id ) ) {
                    var toolbalEl = editorEl.querySelector( '.edit-post-header__toolbar' );
                    if( toolbalEl instanceof HTMLElement ){
                        toolbalEl.insertAdjacentHTML( 'beforeend', link_html );
                    }
                }
            }, 1 )
        } );
        // unsubscribe is a function - it's not used right now 
        // but in case you'll need to stop this link from being reappeared at any point you can just call unsubscribe();

    } )( window, wp )

},100);