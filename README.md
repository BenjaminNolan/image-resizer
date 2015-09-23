# Image Resizer
This librarylet provides a class which allows you to easily resize images using PHP's GD library.

# Installation
The easiest way to install this library is with composer. Simply run this command in your project's root directory:

    composer require twowholeworms/image-resizer

# Usage
Here's the simplest usage for the resizer:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    // Resize an image using the default settings (PNG, cover) to 300x200
    $processedImage = ImageResizer::load('/path/to/image_file.png')->resize(300, 200);

You can save the results directly to a file like this:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    // Resize an image using the default settings (PNG, cover) to 300x200 and save it to a file
    $resizer = ImageResizer::load('/path/to/image_file.png')->save('/path/to/save/processed_image.png');

You can load from a URL:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    $resizer = ImageResizer::load('http://url.to/image.png');

You can pass in configuration options when loading the source image:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    // Create a new instance with specific settings
    $resizer = ImageResizer::load('/path/to/image.gif', [
        'format' => IMAGETYPE_PNG,
        'mode' => ImageResizer::MODE_COVER,
        'width' => 300,
        'height' => 200,
    ]);

Or you can set each option individually:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    $resizer = ImageResizer::load('/path/to/image.gif');
    $resizer->setFormat(IMAGETYPE_JPEG)
            ->setMode(ImageResizer::MODE_STRETCH)
            ->setWidth(150)
            ->setHeight(100);

If you want to save several image sizes straight from one source image, you can just do this:

    <?php

    use TwoWholeWorms\Utilities\ImageResizer;

    $resizer = ImageResizer::load('/path/to/image')
    $resizer->save($destFileLarge, ['width' => 1000, 'height' => 750]);
    $resizer->save($destFileMedium, ['width' => 600, 'height' => 400]);
    $resizer->save($destFileSmall, ['width' => 300, 'height' => 200]);
    $resizer->save($destFileThumb, ['width' => 100, 'height' => 100]);
