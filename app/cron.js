$(document).ready(function() {
    $.ajax({
        type: "POST",
        url: "http://localhost/refreshTest/refreshToken.php",
    });
});