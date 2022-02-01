$('#img-logo')[0].click();

$('#upload_logo_image').on('click', function(e) {
    $('#img-logo')[0].click();
});

$('#img-logo').change(function() {
    readURL(this, 'img-logo-container', 'upload_logo_body');
});




$('#img-banner')[0].click();

$('#upload_banner_image').on('click', function(e) {
    $('#img-banner')[0].click();
});

$('#img-banner').change(function() {
    readURL(this, 'img-banner-container', 'upload_banner_body');
});





function readURL(input, img_container, upload_body) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            $('#' + img_container).attr('src', e.target.result);
            $('#' + img_container).show();
        }

        reader.readAsDataURL(input.files[0]); // convert to base64 string
    }
    $('#' + upload_body).hide();
}