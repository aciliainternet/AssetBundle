<?php

namespace Acilia\Bundle\AssetBundle\Controller;

use Acilia\Bundle\AssetBundle\Entity\AssetFile;
use Acilia\Bundle\AssetBundle\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class FileController extends AbstractController
{
    public function __construct(FileService $service)
    {
        $this->service = $service;
    }

    protected function getService(): FileService
    {
        return $this->service;
    }

    public function form(AssetFile $entity, ?string $type): Response
    {
        $fileService = $this->getService();

        $fileOption = $fileService->getOption($entity, $type);
        $asset = $fileService->getAssetFromEntity($entity, $type);

        $assetUrl = ($asset instanceof AssetFile) ? $fileService->getUrl($asset) : null;

        return $this->render('AciliaAssetBundle:File:form.html.twig', [
            'asset' => $asset,
            'assetUrl' => $assetUrl,
            'fileOption' => $fileOption,
        ]);
    }
}
