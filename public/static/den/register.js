$(document).ready(function() {
    $("#2fa-container").hide();
    $("#username").on('change', function () {
        var username = $(this).val();
        var el = $("#2fa-uri");

        $("#2fa-container").show();

        // Replace
        var uri = el.data('base')
            .split('%24username')
            .join(username);
        el.val(uri);
        $("#qr-code-txt").html(uri);

        window.qr = new QRious({
            element: document.getElementById("qr-code"),
            value: document.getElementById("2fa-uri").value,
            size: 400
        });
    });
});

