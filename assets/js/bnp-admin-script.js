/*
 * This JS file is loaded for the backend area.
 */

jQuery(document).ready(function($) {

    // Bg color picker options.
    var bg_options = {
        // a callback to fire whenever the color changes to a valid color
        change: function(event, ui) {
            var bg_color = $(this).val();
            $(".bnp_news_preview_section").css("background", bg_color);
        },
    };

    // Add color picker for background color field.
    $('.bnp-bg-color-field').wpColorPicker(bg_options);


    // Text color picker options.
    var text_options = {
        // a callback to fire whenever the color changes to a valid color
        change: function(event, ui) {
            var text_color = $(this).val();
            $(".bnp_news_preview_section span, .bnp_news_preview_section a").css("color", text_color);
        },
    };

    // Add color picker for text color field.
    $('.bnp-text-color-field').wpColorPicker(text_options);


    // Change title text based on field value.
    jQuery('#bnp_section_title').on('keyup', function() {
        var title_val = $(this).val();
        jQuery('.bnp_area_title').text(title_val);
    });

});