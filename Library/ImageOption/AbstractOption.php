<?php

namespace Acilia\Bundle\AssetBundle\Library\ImageOption;

abstract class AbstractOption
{
    public const DEFAULT_QUALITY = 80;

    protected $randomId;
    protected $entity;
    protected $type;
    protected $title;
    protected $attribute;
    protected $aspectRatios = [];
    protected $renditions = [];
    protected $retina;
    protected $quality;
    protected $assetsPerDirectory;
    protected $preserveOriginal;
    protected $minWidths;
    protected $minHeights;

    public function __construct(array $options, $entity, string $type)
    {
        $this->randomId = 'cropper-'.md5(time().mt_rand());
        $this->entity = $entity;
        $this->type = $type;
        $this->title = $options['title'];
        $this->attribute = $options['attribute'];
        $this->retina = isset($options['retina']) ? $options['retina'] : false;
        $this->quality = isset($options['quality']) ? $options['quality'] : self::DEFAULT_QUALITY;
        $this->assetsPerDirectory = isset($options['assetsPerDirectory']) ? (int) $options['assetsPerDirectory'] : null;
        $this->preserveOriginal = isset($options['preserveOriginal']) ? $options['preserveOriginal'] : false;
    }

    abstract public function getRendition(?string $rendition): array;

    abstract public function getFirstSize(): ?string;

    abstract public function getAspectRatios(string $replace = 'x'): array;

    abstract public function getSpecs(): array;

    abstract public function getMinHeight(string $rendition): int;

    abstract public function getFinalRenditions(string $aspectRatio): array;

    public function getSetter(): string
    {
        $method = 'set'.ucfirst($this->attribute);

        return $method;
    }

    public function getGetter(): string
    {
        $method = 'get'.ucfirst($this->attribute);

        return $method;
    }

    public function getQuality(): int
    {
        if (is_numeric($this->quality)) {
            return $this->quality;
        }

        return self::DEFAULT_QUALITY;
    }

    public function getAssetsPerDirectory(): ?int
    {
        return $this->assetsPerDirectory;
    }

    public function getPreserveOriginal(): bool
    {
        return $this->preserveOriginal;
    }

    public function getAssetType(): string
    {
        return $this->entity.'-'.$this->type;
    }

    public function randomId(): string
    {
        return $this->randomId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
