<?php

namespace Acilia\Bundle\AssetBundle\Library\Image;

use Acilia\Bundle\AssetBundle\Library\ImageOption\AbstractOption;
use Acilia\Bundle\AssetBundle\Library\ImageOption\RenditionOption;
use Acilia\Bundle\AssetBundle\Library\ImageOption\CustomOption;
use Acilia\Bundle\AssetBundle\Entity\Asset;

abstract class ImageService
{
    protected $imageOptions;

    public function __construct(array $imageOptions)
    {
        $this->imageOptions = $imageOptions;
    }

    protected function getEntityCode($entity): string
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
            $entity = explode('\\', $entity);
            $entity = array_pop($entity);
            $entity = strtolower($entity);
        }

        return $entity;
    }

    public function getOption($entity, ?string $type = null): AbstractOption
    {
        if ($entity instanceof Asset) {
            list($entity, $type) = explode('-', $entity->getType(), 2);
        }

        $entity = $this->getEntityCode($entity);
        if (!isset($this->imageOptions['entities'][$entity])) {
            throw new \Exception(sprintf('Entity %s does not exists.', $entity));
        }

        if (!isset($this->imageOptions['entities'][$entity][$type])) {
            throw new \Exception(sprintf('Type %s in entity %s does not exists.', $type, $entity));
        }

        $options = $this->imageOptions['entities'][$entity][$type];
        if (isset($options['renditions'])) {
            $imageOption = new RenditionOption($options, $entity, $type, $this->imageOptions['ratios'], $this->imageOptions['renditions']);
        } else {
            $imageOption = new CustomOption($options, $entity, $type);
        }

        return $imageOption;
    }

    protected function getBaseDirectory(Asset $asset): string
    {
        $options = $this->getOption($asset, $asset->getType());

        $directory = $asset->getType();
        if ($options->getAssetsPerDirectory() !== null) {
            $subDirectory = floor($asset->getId() / $options->getAssetsPerDirectory());
            $directory .= '/' . $subDirectory;
        }

        return $directory;
    }

    public function getAssetFilename(Asset $asset, ?string $size = null, bool $retina = false): string
    {
        $imageOption = $this->getOption($asset);
        if (null === $size) {
            $size = ($imageOption->getFirstSize() !== null) ? $imageOption->getFirstSize() : '';
        }

        if ($retina === true) {
            $size .= '@2x';
        }

        $filename = sprintf(
            '%s/%u.%s.%s',
            $this->getBaseDirectory($asset),
            $asset->getId(),
            $size,
            $asset->getExtension()
        );

        return $filename;
    }
}
