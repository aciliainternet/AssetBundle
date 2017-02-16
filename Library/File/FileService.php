<?php

namespace Acilia\Bundle\AssetBundle\Library\File;

use Acilia\Bundle\AssetBundle\Entity\AssetFile;
use Exception;

abstract class FileService
{
    protected $fileOptions;

    public function __construct($fileOptions)
    {
        $this->fileOptions = $fileOptions;
    }

    /**
     * @param AssetFile|string $entity
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
     * @param AssetFile|string $entity
     * @param string $type
     *
     * @return FileOption
     *
     * @throws Exception
     */
    public function getOption($entity, $type = null)
    {
        if ($entity instanceof AssetFile) {
            list($entity, $type) = explode('-', $entity->getType(), 2);
        }

        $entity = $this->getEntityCode($entity);
        if (!isset($this->fileOptions['entities'][$entity])) {
            throw new Exception(sprintf('Entity %s does not exists.', $entity));
        }

        if (!isset($this->fileOptions['entities'][$entity][$type])) {
            throw new Exception(sprintf('Type %s in entity %s does not exists.', $type, $entity));
        }

        $options = $this->fileOptions['entities'][$entity][$type];
        $fileOption = new FileOption($options, $entity, $type);

        return $fileOption;
    }

    /**
     * @param AssetFile $asset
     *
     * @return string
     */
    protected function getBaseDirectory(AssetFile $asset)
    {
        $directory = $asset->getType();
        return $directory;
    }

    /**
     * @param AssetFile $asset
     * @param string  $size
     * @param bool $retina
     *
     * @throws Exception
     *
     * @return string
     */
    public function getAssetFilename(AssetFile $asset)
    {
        $filename = sprintf('%s/%u.%s', $this->getBaseDirectory($asset), $asset->getId(), $asset->getExtension());

        return $filename;
    }
}
