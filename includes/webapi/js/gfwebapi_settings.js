function gfapiCalculateSig(stringToSign, privateKey) {
    var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
    var base64 = hash.toString(CryptoJS.enc.Base64);
    return encodeURIComponent(base64);
}

jQuery(document).ready(function () {

    jQuery("#gfwebapi-qrbutton").click(function () {
        jQuery("#gfwebapi-qrcode-container").toggle();
        var $img = jQuery('#gfwebapi-qrcode');
        if ($img.length > 0)
            $img.attr('src', ajaxurl + '?action=gfwebapi_qrcode&rnd=' + Date.now());

        return false;
    });

    jQuery("#public_key, #private_key").on("keyup", function () {
        jQuery("#gfwebapi-qrcode-container").html("The keys have changes. Please save the changes and try again.")
    });

    jQuery("#gfapi-url-builder-button").click(function (e) {
        e.preventDefault();
        var publicKey, privateKey, expiration, method, route, stringToSign, url, sig;
        publicKey = jQuery("#public_key").val();
        privateKey = jQuery("#private_key").val();
        expiration = parseInt(jQuery("#gfapi-url-builder-expiration").val());
        method = jQuery("#gfapi-url-builder-method").val();
        route = jQuery("#gfapi-url-builder-route").val();
        route = route.replace(/\/$/, ""); // remove trailing slash
        var d = new Date;
        var unixtime = parseInt(d.getTime() / 1000);
        var future_unixtime = unixtime + expiration;

        stringToSign = publicKey + ":" + method + ":" + route + ":" + future_unixtime;
        sig = gfapiCalculateSig(stringToSign, privateKey);
        url = gfapiBaseUrl + "/" + route + "/?api_key=" + publicKey + "&signature=" + sig + "&expires=" + future_unixtime;
        jQuery('#gfapi-url-builder-generated-url').val(url);
        return false;
    });
    var gfapiTesterAjaxRequest;
    jQuery("#gfapi-url-tester-button").click(function (e) {
        var $button = jQuery(this);
        var $loading = jQuery("#gfapi-url-tester-loading");
        var $results = jQuery("#gfapi-url-tester-results");
        var url = jQuery('#gfapi-url-tester-url').val();
        var method = jQuery('#gfapi-url-tester-method').val();
        gfapiTesterAjaxRequest = jQuery.ajax({
            url       : url + "&test=1",
            type      : method,
            dataType  : 'json',
            data      : {},
            beforeSend: function (xhr, opts) {
                $button.attr('disabled', 'disabled');
                $loading.show();
            }
        })
            .done(function (data, textStatus, xhr) {
                $button.removeAttr('disabled');
                $loading.hide();
                $results.html(xhr.status);
                $results.fadeTo("fast", 1);
            })
            .fail(function (jqXHR) {

                $button.removeAttr('disabled');
                $loading.hide();
                $results.fadeTo("fast", 1);
                var msg;
                $loading.hide();
                if (msg == "abort") {
                    msg = "Request cancelled";
                } else {
                    msg = jqXHR.status + ": " + jqXHR.statusText;
                }
                $results.html(msg);
            });
        return false;
    });

    // Reload page when modal is closed.
    jQuery( 'body' ).on( 'thickbox:removed', function( e ) {

        if ( modalSubmitted ) {
            jQuery( '#gform-settings' ).submit();
        }

    } );

});

var modalSubmitted = false;

// Update key.
function saveKey() {

    var requestData = {
        action:      'gfwebapi_edit_key',
        nonce:       jQuery( '#gform-webapi-edit input[name="_wpnonce"]' ).val(),
        key_id:      jQuery( '#gform-webapi-key' ).val(),
        description: jQuery( '#gform-webapi-description' ).val(),
        user_id:     jQuery( '#gform-webapi-user' ).val(),
        permissions: jQuery( '#gform-webapi-permissions' ).val()
    };

    // Attempt to save key, display response.
    jQuery.ajax(
        {
            url:      ajaxurl,
            type:     'POST',
            dataType: 'json',
            data:     requestData,
            success:  function ( response ) {

                // Get alert class.
                var alertClass = response.success ? 'success' : 'error';

                // Remove existing alert, add new alert.
                jQuery( '#gform-webapi-edit .alert', document ).remove();
                jQuery( '#gform-webapi-edit' ).prepend( '<div class="alert ' + alertClass + '">' + response.data.message + '</div>' );

                // Display consumer key, secret.
                if ( response.data.key ) {
                    jQuery( '#gform-webapi-description, #gform-webapi-user, #gform-webapi-permissions, #gform-webapi-truncated-key, #gform-webapi-last-access' ).parent().hide();
                    jQuery( '#gform-webapi-consumer-key' ).val( response.data.key.consumer_key ).parent().show();
                    jQuery( '#gform-webapi-consumer-secret' ).val( response.data.key.consumer_secret ).parent().show();
                    jQuery( '#gform-webapi-edit button' ).hide();
                } else {
                    jQuery( '#gform-webapi-consumer-key' ).val( '' ).parent().hide();
                    jQuery( '#gform-webapi-consumer-secret' ).val( '' ).parent().hide();
                }

            }
        }
    );

    modalSubmitted = true;

    return false;

}

// Open edit key modal.
function editKey( keyId ) {

    modalSubmitted = false;

    // Remove existing alert, hide consumer key/secret, show button.
    jQuery( '#gform-webapi-edit .alert', document ).remove();
    jQuery( '#gform-webapi-consumer-key, #gform-webapi-consumer-secret' ).parent().hide();
    jQuery( '#gform-webapi-edit button' ).show();

    // If this is a new key, reset the form and open modal.
    if ( keyId == 0 ) {

        jQuery( '#gform-webapi-key' ).val( keyId );
        jQuery( '#gform-webapi-description' ).val( '' );
        jQuery( '#gform-webapi-user' ).val( jQuery( '#gform-webapi-user option:first-child' ).val() );
        jQuery( '#gform-webapi-permissions' ).val( jQuery( '#gform-webapi-permissions option:first-child' ).val() );

        jQuery( '#gform-webapi-edit button' ).html( jQuery( '#gform-webapi-edit button' ).data( 'add' ) );

        jQuery( '#gform-webapi-key, #gform-webapi-description, #gform-webapi-user, #gform-webapi-permissions' ).parent().show();
        jQuery( '#gform-webapi-truncated-key, #gform-webapi-last-access' ).parent().hide();

        tb_show( 'Add New Key', '#TB_inline?width=375&height=330&inlineId=gform-webapi-edit-container' );

        jQuery( '#gform-webapi-edit', document ).on( 'submit', saveKey );

        return;

    }

    // Get key details, open modal.
    jQuery.ajax(
        {
            url:      ajaxurl,
            type:     'GET',
            dataType: 'json',
            data:     {
                action: 'gfwebapi_edit_key',
                key_id: keyId,
                nonce:  jQuery( '#gform-webapi-edit input[name="_wpnonce"]' ).val(),
            },
            success:  function ( response ) {

                // If key could not be retrieve, display error.
                if ( ! response.success ) {
                    alert( response.data.message );
                    return;
                }

                var key = response.data.key;

                jQuery( '#gform-webapi-key' ).val( key.key_id );
                jQuery( '#gform-webapi-description' ).val( key.description );
                jQuery( '#gform-webapi-user' ).val( key.user_id );
                jQuery( '#gform-webapi-permissions' ).val( key.permissions );
                jQuery( '#gform-webapi-truncated-key' ).html( key.consumer_key ).parent().show();
                jQuery( '#gform-webapi-last-access' ).html( key.last_access ).parent().show();

                jQuery( '#gform-webapi-edit button' ).html( jQuery( '#gform-webapi-edit button' ).data( 'edit' ) );

                jQuery( '#gform-webapi-description, #gform-webapi-user, #gform-webapi-permissions, #gform-webapi-truncated-key, #gform-webapi-last-access' ).parent().show();

                tb_show( 'Edit Key', '#TB_inline?width=375&height=445&inlineId=gform-webapi-edit-container' );

                jQuery( '#gform-webapi-edit', document ).on( 'submit', saveKey );

            }
        }
    );

}
