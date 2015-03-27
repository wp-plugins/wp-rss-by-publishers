jQuery(document).ready(function($){
    $("input.button.action").click(function(e){
        if($("#bulk-action-selector-top").val()=='-1' && $("#bulk-action-selector-bottom").val()=='-1') {
            e.preventDefault();
            return false;
        }
    });

    $('.meta-image-button').click(function(e){
        e.preventDefault();
        formfield = $(this).attr('rel');
        tb_show('', 'media-upload.php?type=image&TB_iframe=true');
        return false;
    });

    // If on the Publisher form use this
    var publisherImage = $('.publisher-image');
    if(publisherImage.length > 0) {
        window.send_to_editor = function (html) {
            try {
                imgurl = $('img', html).attr('src');
                $('#' + formfield).val(imgurl);
                $('#' + formfield + '_preview').attr('src', imgurl);
                $('#' + formfield + '_preview').closest('.publisher-image').parent().find('.no-image').remove();
                $('#' + formfield + '_preview').closest('.publisher-image').show();
                tb_remove();
            } catch (e) {
                console.log(e);
            }
        }
    }

    /**
     * Add/Edit rule page
     * this will disable the tags textarea if the user selects Any
     */
    rule_tags_any_checked("#rule_tags_any");
    $("#rule_tags_any").on('click',function(){
        rule_tags_any_checked(this);
    });
    $(".confirm").on('click',function(e){
        if(confirm_wrapper($(this).attr('data-confirmation'))) {
            return true;
        }
        else {
            e.preventDefault();
            return false;
        }
    });

    /**
     * Home featured post metabox
     */
    var homeFeaturedDataInput = $('#home-featured-post-expiration');
    if(homeFeaturedDataInput.length > 0) {
        var previous = homeFeaturedDataInput.val();
        homeFeaturedDataInput.datepicker({
            dateFormat: 'yy-mm-dd'
        });
        $('#home-featured-post-edit-expiration, .home-featured-post-hide-expiration').click(function (e) {
            e.preventDefault();
            var date = $('#home-featured-post-expiration').val();
            if ($(this).hasClass('cancel')) {
                $('#home-featured-post-expiration').val(previous);
            } else if (date) {
                $('#home-featured-post-expiration-label').text($('#home-featured-post-expiration').val());
            }
            $('#home-featured-post-expiration-field').slideToggle();
        });
    }
    /**
     * END Home featured post
     */

    function rule_tags_any_checked(checkbox) {
        if($(checkbox).is(":checked")) {
            $("#rule_tags").prop('disabled',true);
            return true;
        }

        $("#rule_tags").prop('disabled',false);
        return false;
    }
});

function confirm_wrapper(message) {
    return confirm(message);
}