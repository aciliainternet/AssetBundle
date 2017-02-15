<?php

namespace Acilia\Bundle\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="asset_file",  options={"collate"="utf8_unicode_ci", "charset"="utf8", "engine"="InnoDB"})
 */
class AssetFile
{
    /**
     * @ORM\Column(type="integer", name="file_id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=64, name="file_type")
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=192, name="file_name", nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=4, name="file_extension", nullable=false)
     */
    protected $extension;

    /**
     * @ORM\Column(type="string", length=32, name="file_mime_type", nullable=false)
     */
    protected $mimeType;

    /**
     * @ORM\Column(type="integer", name="file_size", nullable=false)
     */
    protected $size;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return AssetFile
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Set name.
     *
     * @param string $name
     *
     * @return AssetFile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set extension.
     *
     * @param string $extension
     *
     * @return AssetFile
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set mimeType.
     *
     * @param string $mimeType
     *
     * @return AssetFile
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set size.
     *
     * @param integer $size
     *
     * @return AssetFile
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }
}
