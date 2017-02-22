<?php

namespace Acilia\Bundle\AssetBundle\Library;

use Doctrine\Common\Collections\ArrayCollection;

class AssetResponse
{
    protected $status;
    protected $errorMessage;
    protected $assets;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function success()
    {
        return $this->status;
    }

    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function addAsset($key, $asset)
    {
        $this->assets[$key] = $asset;

        return $this;
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function getAsset($key)
    {
        if (isset($this->assets[$key])) {
            return $this->assets[$key];
        }

        return null;
    }
}
