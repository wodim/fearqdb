$(document).ready(function() {
    $("#hamburger").click(function() {
        if ($("#bar").hasClass("nomobile")) {
            console.log("holi");
            $("#bar").removeClass("nomobile");
        } else {
            console.log("bye");
            $("#bar").addClass("nomobile");
        }
    })
})