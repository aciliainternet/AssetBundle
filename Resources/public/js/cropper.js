var Cropper = {};
Cropper.currentOptions = null;

Cropper.reconfigure = function($image, id, aspectRatio, options) {
    jQuery('#title-aspect-ratio-' + id).html(aspectRatio.replace('x', ':'));

    Cropper.currentOptions = options;

    var aspectRatios = jQuery.parseJSON($('#save-' + id).attr('data-aspect-ratios'));
    if (aspectRatios.length > 0) {
        $('#save-' + id).html('Next');
    } else {
        $('#save-' + id).html('Confirm');
    }

    if (options.aspectRatio >= 1) {
        jQuery('#' + id + ' .img-preview').css('width', '250px');
        jQuery('#' + id + ' .img-preview').css('height', '' + parseInt(250 / options.aspectRatio) + 'px');
    } else {
        jQuery('#' + id + ' .img-preview').css('width', '' + parseInt(250 / options.aspectRatio) + 'px');
        jQuery('#' + id + ' .img-preview').css('height', '250px');
    }

    $($image).cropper({
        zoomable: false,
        mouseWheelZoom: false,
        rotatable: false,
        aspectRatio: Cropper.currentOptions.aspectRatio,
        preview: '#' + id + ' .img-preview',
        autoCropArea: 0.8,
        done: function(data) {
            cropData = $image.cropper('getData');

            if (Cropper.currentOptions.minWidth <= Math.round(cropData.width) && Cropper.currentOptions.minHeight <= Math.round(cropData.height)) {
                $('#' + id + ' .alert').hide();
                $('#save-' + id).show();
            } else {
                $('#' + id + ' .alert').show();
                $('#save-' + id).hide();
            }
        }
    });
    $image.cropper('reset', true);
};

Cropper.init = function(id) {
    // Delete
    jQuery('#delete-' + id).on('click', function(event) {
        jQuery('.asset-' + id).val('::delete::');
        jQuery('#image-placeholder-' + id).attr('src', jQuery('#image-placeholder-' + id).attr('data-empty-src'));
        jQuery('#image-placeholder-' + id).attr('data-asset', 'false');
    });

    jQuery('#cancel-' + id).on('click', function(event) {
        var $image = $('#' + id + ' .image-crop > img');
        $image.cropper('destroy');
        $('#cropper-modal-' + id).modal('hide');
    });

    jQuery('#save-' + id).on('click', function(event) {
        var options = jQuery.parseJSON(jQuery('#' + id).attr('data-specs'));
        var $image = $('#' + id + ' .image-crop > img');
        var aspectRatios = jQuery.parseJSON($('#save-' + id).attr('data-aspect-ratios'));

        var metadata = jQuery('#' + id);
        $('#asset-' + id + '-' + $('#save-' + id).attr('data-aspect-ratio')).val($image.cropper('getDataURL', metadata.attr('data-image-type'), 1));

        if (aspectRatios.length > 0) {
            var aspectRatio = aspectRatios.shift();
            $image.cropper('setAspectRatio', aspectRatio);
            $('#save-' + id).attr('data-aspect-ratios', JSON.stringify(aspectRatios));
            $('#save-' + id).attr('data-aspect-ratio', aspectRatio);
            Cropper.reconfigure($image, id, aspectRatio, options[aspectRatio]);

        } else {
            aspectRatios = jQuery.parseJSON($('#save-' + id).attr('data-base-aspect-ratios'));
            var firstAspectRatio = aspectRatios.shift();

            $image.attr('src', '/bundles/aciliaasset/img/missing-image-640x360.png');
            $('#image-placeholder-' + id).attr('src', $('#asset-' + id + '-' + firstAspectRatio).val());

            $image.cropper('destroy');
            $('#cropper-modal-' + id).modal('hide');
        }

        jQuery('#image-placeholder-' + id).attr('data-asset', 'true');
    });

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
                    var options = jQuery.parseJSON(jQuery('#' + id).attr('data-specs'));
                    $result = this.result;

                    $('#cropper-modal-' + id).off();
                    $('#cropper-modal-' + id).on('shown.bs.modal', function(event) {
                        var $image = $('#' + id + ' .image-crop > img');

                        var aspectRatios = jQuery.parseJSON($('#save-' + id).attr('data-base-aspect-ratios'));
                        var aspectRatio = aspectRatios.shift();

                        $('#save-' + id).attr('data-aspect-ratios', JSON.stringify(aspectRatios));
                        $('#save-' + id).attr('data-aspect-ratio', aspectRatio);
                        Cropper.reconfigure($image, id, aspectRatio, options[aspectRatio]);

                        $inputImage.val('');
                        $image.cropper('reset', true).cropper('replace', $result);
                        jQuery('#' + id).attr('data-image-type', file.type);
                        jQuery('#' + id).show();
                    });

                    $('#cropper-modal-' + id).modal('show');
                };
            } else {
                alert('Please choose an image file.');
            }
        });
    } else {
        $inputImage.addClass('hide');
    }
};

jQuery('.acilia-image-corpper').each(function(idx, obj) {
    Cropper.init(jQuery(obj).attr('id'));
});