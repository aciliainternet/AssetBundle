<?php

namespace Acilia\Bundle\AssetBundle\Library\Image;

use Acilia\Bundle\AssetBundle\Library\Exception\ImageException;

class ImageStream
{
    const TYPE_JPG = 'jpg';
    const TYPE_PNG = 'png';

    protected $type;
    protected $content;

    public function __construct($type, $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public static function getInstanceFromStream($stream)
    {
        if (strpos($stream, 'data:image/png;base64,') === 0) {
            $stream = base64_decode(substr($stream, 22));

            return new self(self::TYPE_PNG, $stream);
        } elseif (strpos($stream, 'data:image/jpg;base64,') === 0) {
            $stream = base64_decode(substr($stream, 22));

            return new self(self::TYPE_JPG, $stream);
        } elseif (strpos($stream, 'data:image/jpeg;base64,') === 0) {
            $stream = base64_decode(substr($stream, 23));

            return new self(self::TYPE_JPG, $stream);
        }

        throw new ImageException('Invalid stream');
    }

    public function getType()
    {
        return $this->type;
    }

    public function getContent()
    {
        return $this->content;
    }
}
