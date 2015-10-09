$(document).ready(function() {
    $("#hamburger").click(function() {
        if ($("#bar").hasClass("nomobile")) {
            $("#bar").removeClass("nomobile");
        } else {
            $("#bar").addClass("nomobile");
        }
    })
})