<?php

namespace Acilia\Bundle\AssetBundle\Library\File;

use Acilia\Bundle\AssetBundle\Entity\AssetFile;

interface FileWrapperInterface
{
    public function setAssetFile(AssetFile $assetFile);

    public function getAssetFile();
}
