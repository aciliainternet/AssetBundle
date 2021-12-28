<?php

namespace Acilia\Bundle\AssetBundle\Controller;

use Acilia\Bundle\AssetBundle\Entity\Asset;
use Acilia\Bundle\AssetBundle\Service\ImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends AbstractController
{
    protected $service;

    public function __construct(ImageService $service)
    {
        $this->service = $service;
    }

    protected function getService(): ImageService
    {
        return $this->service;
    }

    public function form(object $entity, ?string $type): Response
    {
        $imageService = $this->getService();
        $imageOption = $imageService->getOption($entity, $type);
        $asset = $imageService->getAssetFromEntity($entity, $type);

        $assetUrl = ($asset instanceof Asset) ? $imageService->getUrl($asset) : null;

        return $this->render('AciliaAssetBundle:Asset:asset.html.twig', [
            'asset' => $asset,
            'assetUrl' => $assetUrl,
            'imageOption' => $imageOption,
        ]);
    }
}
