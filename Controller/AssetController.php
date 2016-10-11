<?php

namespace Acilia\Bundle\AssetBundle\Controller;

use Acilia\Bundle\AssetBundle\Entity\Asset;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function formAction(Request $request, $entity, $type)
    {
        $imageService = $this->getService();
        $imageOption = $imageService->getOption($entity, $type);
        $asset = $imageService->getAssetFromEntity($entity, $type);

        $assetUrl = null;
        if ($asset instanceof Asset) {
            $assetUrl = $imageService->getUrl($asset);
        }

        return $this->render('AciliaAssetBundle:Asset:asset.html.twig', [
            'asset'       => $asset,
            'assetUrl'    => $assetUrl,
            'imageOption' => $imageOption,
        ]);
    }

    protected function getService()
    {
        return $this->get('acilia.asset.service.image');
    }
}
