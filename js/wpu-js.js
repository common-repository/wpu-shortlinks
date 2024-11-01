function wpu_load_box() {
    wpu_lockScroll();
    setTimeout(function(){
        jQuery("#wpu_box_overlay").fadeIn("fast");
        jQuery("#wpu_box").fadeIn("fast");
        jQuery("#wpu_box .long_url").val("").focus();
    }, 500);
}

function wpu_lockScroll(){
    jQueryhtml = jQuery('html');
    jQuerybody = jQuery('body');
    var initWidth = jQuerybody.outerWidth();
    var initHeight = jQuerybody.outerHeight();

    var scrollPosition = [
        self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
        self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
    ];
    jQueryhtml.data('scroll-position', scrollPosition);
    jQueryhtml.data('previous-overflow', jQueryhtml.css('overflow'));
    jQueryhtml.css('overflow', 'hidden');
    window.scrollTo(scrollPosition[0], scrollPosition[1]);

    var marginR = jQuerybody.outerWidth()-initWidth;
    var marginB = jQuerybody.outerHeight()-initHeight;
    jQuerybody.css({'margin-right': marginR,'margin-bottom': marginB});

}

function wpu_unlockScroll(){
    jQueryhtml = jQuery('html');
    jQuerybody = jQuery('body');
    jQueryhtml.css('overflow', jQueryhtml.data('previous-overflow'));
    var scrollPosition = jQueryhtml.data('scroll-position');
    window.scrollTo(scrollPosition[0], scrollPosition[1]);

    jQuerybody.css({'margin-right': 0, 'margin-bottom': 0});
}

function wpu_remove_box() {
    jQuery("#wpu_box_overlay").fadeOut("fast");
    jQuery("#wpu_box").fadeOut("fast");
    wpu_unlockScroll();
}

function wpu_ajax_request() {
    var url = jQuery("#wpu_box .long_url").val();

    if (url.length < 4) {
        jQuery("#wpu_box .long_url").focus();
        return;
    }

    jQuery("#wpu_box .result").hide();
    jQuery("#wpu_box .update-nag").hide();
    jQuery("#wpu_box .update-nag p").hide();
    jQuery("#wpu_box .spinner").css('display', 'inline-block');
    var data = {
        action: 'wpu_shortlinks_get',
        url: url
    };
    jQuery.post(ajaxurl, data, function (response) {
        if (WPU_IsJsonString(response)) {
            var obj = JSON.parse(response);
            if (obj.status == "OK") {
                jQuery("#wpu_box .result").show();
                jQuery('#wpu_box .short_url').val(obj.short_url);
                jQuery('#wpu_box .qrcode').attr("src", obj.qrcode_img);
            } else {
                jQuery("#wpu_box .update-nag").show();
                jQuery("#wpu_box .update-nag p." + obj.status).css('display', 'inline-block');
            }
            //alert(obj.status);
        } else {
            jQuery("#wpu_box .update-nag").show();
            jQuery("#wpu_box .update-nag p.INTERNAL_ERROR").show();
        }
        jQuery("#wpu_box .spinner").hide();
    });
}

jQuery("#wpu_box .long_url").keyup(function (e) {
    if (e.keyCode == 13) {
        wpu_ajax_request();
    }
});

jQuery('#wpu_box .button').click(function () {
    wpu_ajax_request();
});

jQuery('#wpu_box_overlay').click(function () {
    wpu_remove_box();
});

jQuery('#wpu_box .hide').on('click', function (e) {
    wpu_remove_box();
});

jQuery(document).keydown(function(e) {
    // ESCAPE key pressed
    if (e.keyCode == 27) {
        wpu_remove_box();
    }
});

function WPU_IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}