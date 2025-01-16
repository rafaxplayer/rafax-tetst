jQuery(document).ready(function ($) {

    $('#test_is_messages').change(function () {
        
        if ($(this).is(':checked')) {
            $('#test_messages').parent().show();
        } else {
            $('#test_messages').parent().hide();
        }

    });

    if ($('#test_is_messages').is(':checked')) {
        $('#test_messages').parent().show();
    }
    

    $(".text_shortcode").on("click", function () {
        // Obtener el contenido del <td>
        var text = $(this).text().trim();

        // Crear un elemento temporal para copiar al portapapeles
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(text).select();

        // Selecciona todo el contenido del <td>
        var range = document.createRange();
        range.selectNodeContents(this);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        try {
            // Intentar copiar el texto al portapapeles
            document.execCommand("copy");

            // Mostrar mensaje de éxito
            $(".message").fadeIn().delay(2000).fadeOut();
        } catch (err) {
            console.error("Error al copiar al portapapeles:", err);
        }

        // Eliminar el elemento temporal
        $temp.remove();
    });


});
