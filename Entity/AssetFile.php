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
    protected int $id;

    /**
     * @ORM\Column(type="string", length=64, name="file_type")
     */
    protected string $type;

    /**
     * @ORM\Column(type="string", length=192, name="file_name", nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(type="string", length=4, name="file_extension", nullable=false)
     */
    protected string $extension;

    /**
     * @ORM\Column(type="string", length=32, name="file_mime_type", nullable=false)
     */
    protected string $mimeType;

    /**
     * @ORM\Column(type="integer", name="file_size", nullable=false)
     */
    protected string $size;

    public function getId(): int
    {
        return $this->id;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }
    public function getSize(): int
    {
        return $this->size;
    }
}
