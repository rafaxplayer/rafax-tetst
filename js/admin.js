jQuery(document).ready(function ($) {

    $('#test_is_messages').change(function () {
        console.log('change');
        if ($(this).is(':checked')) {
            $('#test_messages').parent().show();
        } else {
            $('#test_messages').parent().hide();
        }

    });

    if ($('#test_is_messages').is(':checked')) {
        $('#test_messages').parent().show();
    }


});
