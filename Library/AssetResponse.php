<?php

namespace Acilia\Bundle\AssetBundle\Library;

class AssetResponse
{
    protected $status;
    protected $errorMessage;
    protected $asset;

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

    public function setAsset($asset)
    {
        $this->asset = $asset;
    }

    public function getAsset()
    {
        return $this->asset;
    }
}
