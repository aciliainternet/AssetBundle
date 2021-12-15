<?php

namespace Acilia\Bundle\AssetBundle\Library\Image;

use Acilia\Bundle\AssetBundle\Library\Exception\ImageException;

class ImageStream
{
    public const TYPE_JPG = 'jpg';
    public const TYPE_PNG = 'png';

    protected string $type;
    protected string $content;

    public function __construct(string $type, string $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public static function getInstanceFromStream(string $stream): self
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
