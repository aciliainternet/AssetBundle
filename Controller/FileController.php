<?php

namespace Acilia\Bundle\AssetBundle\Controller;

use Acilia\Bundle\AssetBundle\Entity\AssetFile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function formAction(Request $request, $entity, $type)
    {
        $fileService = $this->getService();
        $fileOption = $fileService->getOption($entity, $type);
        $asset = $fileService->getAssetFromEntity($entity, $type);

        $assetUrl = null;
        if ($asset instanceof AssetFile) {
            $assetUrl = $fileService->getUrl($asset);
        }

        return $this->render('AciliaAssetBundle:File:form.html.twig', [
            'asset'       => $asset,
            'assetUrl'    => $assetUrl,
            'fileOption' => $fileOption,
        ]);
    }

    protected function getService()
    {
        return $this->get('acilia.asset.service.file');
    }
}
