<?php

namespace Acilia\Bundle\AssetBundle\Library\File;

use Acilia\Bundle\AssetBundle\Entity\AssetFile;

abstract class FileService
{
    protected array $fileOptions;

    public function __construct(array $fileOptions)
    {
        $this->fileOptions = $fileOptions;
    }

    /**
     * @param AssetFile|string $entity
     */
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

    /**
     * @param AssetFile|string $entity
     */
    public function getOption($entity, ?string $type = null): FileOption
    {
        if ($entity instanceof AssetFile) {
            list($entity, $type) = explode('-', $entity->getType(), 2);
        }

        $entity = $this->getEntityCode($entity);
        if (!isset($this->fileOptions['entities'][$entity])) {
            throw new \Exception(sprintf('Entity %s does not exists.', $entity));
        }

        if (!isset($this->fileOptions['entities'][$entity][$type])) {
            throw new \Exception(sprintf('Type %s in entity %s does not exists.', $type, $entity));
        }

        $options = $this->fileOptions['entities'][$entity][$type];
        $fileOption = new FileOption($options, $entity, $type);

        return $fileOption;
    }

    protected function getBaseDirectory(AssetFile $asset): string
    {
        return $asset->getType();
    }

    public function getAssetFilename(AssetFile $asset): string
    {
        return sprintf(
            '%s/%u.%s',
            $this->getBaseDirectory($asset),
            $asset->getId(),
            $asset->getExtension()
        );
    }
}
