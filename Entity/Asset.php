<?php

namespace Acilia\Bundle\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="asset",  options={"collate"="utf8_unicode_ci", "charset"="utf8", "engine"="InnoDB"})
 */
class Asset
{
    /**
     * @ORM\Column(type="integer", name="asset_id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=64, name="asset_type")
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=4, name="asset_extension")
     */
    protected $extension;

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

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
