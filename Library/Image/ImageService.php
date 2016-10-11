<?php

namespace Acilia\Bundle\AssetBundle\Library\Image;

use Acilia\Bundle\AssetBundle\Library\ImageOption\AbstractOption;
use Acilia\Bundle\AssetBundle\Library\ImageOption\RenditionOption;
use Acilia\Bundle\AssetBundle\Library\ImageOption\CustomOption;
use Acilia\Bundle\AssetBundle\Entity\Asset;
use Exception;

abstract class ImageService
{
    protected $imageOptions;

    public function __construct($imageOptions)
    {
        $this->imageOptions = $imageOptions;
    }

    /**
     * @param Asset|string $entity
     *
     * @return mixed|string
     */
    protected function getEntityCode($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
            $entity = explode('\\', $entity);
            $entity = array_pop($entity);
            $entity = strtolower($entity);
        }

        return $entity;
    }

    /**
     * @param Asset|string $entity
     * @param string $type
     *
     * @return AbstractOption
     *
     * @throws Exception
     */
    public function getOption($entity, $type = null)
    {
        if ($entity instanceof Asset) {
            list($entity, $type) = explode('-', $entity->getType(), 2);
        }

        $entity = $this->getEntityCode($entity);
        if (!isset($this->imageOptions['entities'][$entity])) {
            throw new Exception(sprintf('Entity %s does not exists.', $entity));
        }

        if (!isset($this->imageOptions['entities'][$entity][$type])) {
            throw new Exception(sprintf('Type %s in entity %s does not exists.', $type, $entity));
        }

        $options = $this->imageOptions['entities'][$entity][$type];
        if (isset($options['renditions'])) {
            $imageOption = new RenditionOption($options, $entity, $type, $this->imageOptions['ratios'], $this->imageOptions['renditions']);
        } else {
            $imageOption = new CustomOption($options, $entity, $type);
        }

        return $imageOption;
    }

    /**
     * @param Asset $asset
     *
     * @return string
     */
    protected function getBaseDirectory(Asset $asset)
    {
        $options = $this->getOption($asset, $asset->getType());

        $directory = $asset->getType();
        if ($options->getAssetsPerDirectory() !== false) {
            $subDirectory = floor($asset->getId() / $options->getAssetsPerDirectory());
            $directory .= '/' . $subDirectory;
        }

        return $directory;
    }

    /**
     * @param Asset $asset
     * @param string  $size
     * @param bool $retina
     *
     * @throws Exception
     *
     * @return string
     */
    public function getAssetFilename(Asset $asset, $size = null, $retina = false)
    {
        $imageOption = $this->getOption($asset);
        if ($size === null) {
            $size = $imageOption->getFirstSize();
        }

        if ($retina === true) {
            $size .= '@2x';
        }

        $filename = sprintf('%s/%u.%s.%s', $this->getBaseDirectory($asset), $asset->getId(), $size, $asset->getExtension());

        return $filename;
    }
}
