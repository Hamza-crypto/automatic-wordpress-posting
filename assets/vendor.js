/*jQuery(document).ready(function () {
    jQuery('#awp_form').submit(function(e){
        e.preventDefault();
        jQuery.ajax({
            url: ajaxurl,
            action: 'awp_upload_posts',
            nonce: FbApi.nonce,
            info: jQuery(this).serialize(),
            success: function (response) {
                console.log(response)
            },
            /*cache: false,
            contentType: false,
            processData: false
        });
        /*let data = {
            action: 'awp_upload_posts',
            nonce: FbApi.nonce,
            info: jQuery(this).serialize(),
        };
        jQuery.post( ajaxurl, data, function( response ){
            console.log(response);
        })
    });
});*/