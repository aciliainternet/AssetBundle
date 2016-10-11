var Uploader = {};
Uploader.init = function(id) {

    // Upload and Crop
    var $inputImage = $('#input-' + id);
    if (window.FileReader) {
        $inputImage.change(function() {
            var fileReader = new FileReader(),
                files = this.files,
                file;

            if (!files.length) {
                return;
            }

            file = files[0];

            if (/^image\/\w+$/.test(file.type)) {
                fileReader.readAsDataURL(file);
                fileReader.onload = function () {
                    $result = this.result;

                    var img = new Image();
                    img.src = $result;
                    $size = $("#image-placeholder-" + id).attr('data-size').split('x');
                    if ($size[0] > Math.round(img.width) || $size[1] > Math.round(img.height)) {
                        $("#acilia-size-message-" + id).addClass('acilia-error-message');
                        return false;
                    }
                    $("#acilia-size-message-" + id).removeClass('acilia-error-message');

                    $("#image-placeholder-" + id).attr('src', $result);

                    $(".asset-" + id).each(function(idx, obj) {
                        jQuery(obj).attr('value', $result);
                    });
                    return;
                };
            } else {
                alert('Please choose an image file.');
            }
        });
    } else {
        $inputImage.addClass('hide');
    }
};
jQuery('.acilia-image-upload').each(function(idx, obj) {
    Uploader.init(jQuery(obj).attr('id'));
});
