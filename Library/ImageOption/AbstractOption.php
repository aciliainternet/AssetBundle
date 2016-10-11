<?php

namespace Acilia\Bundle\AssetBundle\Library\ImageOption;

abstract class AbstractOption
{
    const DEFAULT_QUALITY = 80;

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
    protected $minWidths;
    protected $minHeights;

    public function __construct($options, $entity, $type)
    {
        $this->randomId = 'cropper-'.md5(time().mt_rand());
        $this->entity = $entity;
        $this->type = $type;
        $this->title = $options['title'];
        $this->attribute = $options['attribute'];
        $this->retina = isset($options['retina']) ? $options['retina'] : false;
        $this->quality = isset($options['quality']) ? $options['quality'] : self::DEFAULT_QUALITY;
        $this->assetsPerDirectory = isset($options['assetsPerDirectory']) ? $options['assetsPerDirectory'] : false;
    }

    abstract public function getRendition($rendition);

    abstract public function getFirstSize();

    abstract public function getAspectRatios($replace = 'x');

    abstract public function getSpecs();

    abstract public function getMinHeight($rendition);

    abstract public function getFinalRenditions($aspectRatio);

    public function getSetter()
    {
        $method = 'set'.ucfirst($this->attribute);

        return $method;
    }

    public function getGetter()
    {
        $method = 'get'.ucfirst($this->attribute);

        return $method;
    }

    public function getQuality()
    {
        if (is_numeric($this->quality)) {
            return $this->quality;
        }

        return self::DEFAULT_QUALITY;
    }

    public function getAssetsPerDirectory()
    {
        if (is_numeric($this->assetsPerDirectory)) {
            return $this->assetsPerDirectory;
        }

        return false;
    }

    public function getAssetType()
    {
        return $this->entity.'-'.$this->type;
    }

    public function randomId()
    {
        return $this->randomId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getType()
    {
        return $this->type;
    }
}
