# AssetBundle
Acilia Asset Bundle for Symfony2 and symfony3

# AssetBundle

Symfony2 and Symfony3 Asset bundle developed by Acilia Internet

This bundle allows to upload and crop images, and save the images as an "Asset" entity on a database.

## Installation and configuration:

Pretty simple with [Composer](http://packagist.org), run:

```sh
composer require aciliainternet/asset-bundle
```

### Add AssetBundle to your application kernel

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Acilia\Bundle\AssetBundle\AciliaAssetBundle(),
        // ...
    );
}
```

### Add assets resources to your public dir

```sh
php bin/console assets:install web/backend/
```

### Link resources on your template

```php
{% stylesheets
    'bundles/aciliaasset/css/plugins/cropper/cropper.min.css'
    filter='cssrewrite' output='css/compiled/app.css' %}
    <link rel="stylesheet" href="{{ asset_url }}" />
{% endstylesheets %}

% javascripts
    'bundles/aciliaasset/js/plugins/cropper/cropper.min.js'
    'bundles/aciliaasset/js/cropper.js'
    'bundles/aciliaasset/js/uploader.js'
    output='js/compiled/app.js' %}
    <script src="{{ asset_url }}"></script>
{% endjavascripts %}
```


<a name="configuration"></a>

### Configuration example

You must configure some parameters

```yaml
acilia_asset:
    assets_images: Resources/config/images.yml  # yaml file to indicate ratios and sizes
    assets_dir: /var/www/media/                 # path to where store the images uploaded
    assets_public: /media                       # relative path to the images on the web server (default /media)
    assets_domain: www.my-images.com            # domain from which the images can be access
```

Image sizes file example:
```yaml
ratios: { 177: 16x9, 100: 1x1, 200: 2x1, 133: 4x3 }

renditions:
    main_highlight: { large: 1200x675,  medium: 1024x576, small: 640x640 }
    big_cards: { large: 1200x675, medium: 1024x576, small: 640x360 }
    secondary_highlight: { large: 600x338, medium: 1024x576, small: 640x360 }

entities:
    card:
        image:
            title: Main
            renditions: [ main_highlight, secondary_highlight, big_cards ]
            attribute: image

    serie:
        main:
            title: Main image
            renditions: [ main_highlight, big_cards ]
            attribute: picture
```
