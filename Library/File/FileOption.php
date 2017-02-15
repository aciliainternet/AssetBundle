<?php

namespace Acilia\Bundle\AssetBundle\Library\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Exception;

class FileOption
{
    protected $randomId;
    protected $entity;
    protected $type;
    protected $title;
    protected $attribute;
    protected $restrictions;
    protected $wrapper;

    public function __construct($options, $entity, $type)
    {
        $this->randomId = 'file-' . md5(time() . mt_rand());
        $this->entity = $entity;
        $this->type = $type;
        $this->title = $options['title'];
        $this->attribute = $options['attribute'];
        $this->restrictions = $options['restrictions'];
        $this->wrapper = isset($options['wrapper']) ? $options['wrapper'] : false;
    }

    public function getSetter()
    {
        $method = 'set' . ucfirst($this->attribute);

        return $method;
    }

    public function getGetter()
    {
        $method = 'get' . ucfirst($this->attribute);

        return $method;
    }

    public function getAssetType()
    {
        return $this->entity . '-' . $this->type;
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

    public function hasWrapper()
    {
        return $this->wrapper;
    }

    public function validate(UploadedFile $file)
    {
        // Size Restriction
        if (isset($this->restrictions['size'])) {
            $multiplier = 1;
            if (strpos($this->restrictions['size'], 'G')) {
                $multiplier = 1024 * 1024 * 1024;
            } elseif (strpos($this->restrictions['size'], 'M')) {
                $multiplier = 1024 * 1024;
            } elseif (strpos($this->restrictions['size'], 'K')) {
                $multiplier = 1024;
            }

            $size = $multiplier * (integer) $this->restrictions['size'];
            if ($file->getSize() > $size) {
                throw new Exception(sprintf('File "%s" exceeds the limit of %s.', $file->getClientOriginalName(), $this->restrictions['size']));
            }
        }

        if (isset($this->restrictions['mime'])) {
            if (!in_array($file->getMimeType(), $this->restrictions['mime'])) {
                throw new Exception(sprintf('File "%s" does not have a valid type.', $file->getClientOriginalName()));
            }
        }
    }
}
