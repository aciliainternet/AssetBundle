<?php

namespace Acilia\Bundle\AssetBundle\Service;

use Acilia\Bundle\AssetBundle\Library\Image\ImageService as AbstractImageService;
use Acilia\Bundle\AssetBundle\Library\Exception\ImageException;
use Acilia\Bundle\AssetBundle\Library\Image\ImageStream;
use Acilia\Bundle\AssetBundle\Entity\Asset;
use Acilia\Bundle\AssetBundle\Entity\AssetFile;
use Acilia\Bundle\AssetBundle\Library\AssetResponse;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Exception\NotReadableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class ImageService extends AbstractImageService
{
    protected $em;
    protected $logger;
    protected $imageOptions;
    protected $assetsDirectory;
    protected $assetsPublic;
    protected $assetDomain;
    protected $imageManager = null;
    protected $error;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->imageOptions = $params->get('acilia_asset.assets_images');
        $this->assetsDirectory = $params->get('acilia_asset.assets_dir');
        $this->assetsPublic = $params->get('acilia_asset.assets_public');
        $this->assetDomain = $params->get('acilia_asset.assets_domain');
    }

    protected function getImageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(['driver' => 'imagick']);
        }

        return $this->imageManager;
    }

    public function getAssetFromEntity($entity, string $type): ?AssetFile
    {
        $asset = null;
        if (is_object($entity)) {
            $imageOptions = $this->getOption($entity, $type);
            $reflex = new \ReflectionMethod(get_class($entity), $imageOptions->getGetter());
            $asset = $reflex->invoke($entity);
        }

        return $asset;
    }

    public function getUrl(Asset $asset, ?string $rendition = null, string $size = null): string
    {
        try {
            $imageOption = $this->getOption($asset);
            $sizes = $imageOption->getRendition($rendition);

            if (is_array($sizes)) {
                $size = isset($sizes[$size]) ? $sizes[$size] : $size;
            } else {
                $size = $imageOption->getFirstSize();
            }
        } catch (\Exception $e) {
            $size = $rendition;
        }

        $filename = $this->getAssetFilename($asset, $size);
        $url = '/' . trim($this->assetsPublic, '/') . '/' . $filename;

        return $url;
    }

    public function handleRequest(Request $request, $entity): AssetResponse
    {
        // create the response object
        $assetResponse = new AssetResponse();
        if (! $request->request->has('asset')) {
            $assetResponse->setStatus(true);

            return $assetResponse;
        }

        $assets = (array) $request->request->get('asset');
        try {
            $this->em->beginTransaction();

            $asset = null;
            foreach ($assets as $type => $aspectRatios) {
                if ($aspectRatios == null) {
                    continue;
                }

                $imageOption = $this->getOption($this->getEntityCode($entity), $type);

                if (in_array('::delete::', $aspectRatios)) {
                    $reflex = new \ReflectionMethod(get_class($entity), $imageOption->getSetter());
                    $reflex->invoke($entity, null);
                    $this->em->flush($entity);
                } else {
                    $asset = false;
                    $streamsFound = false;

                    foreach ($aspectRatios as $aspectRatio => $stream) {
                        if (empty($stream)) {
                            continue;
                        }

                        $streamsFound = true;
                        $imageStream = ImageStream::getInstanceFromStream($stream);

                        // Create the Asset if it's not created already
                        if (!$asset instanceof Asset) {
                            $asset = new Asset();
                            $asset->setType($imageOption->getAssetType())
                                ->setExtension($imageStream->getType());
                            $this->em->persist($asset);
                            $this->em->flush($asset);
                        }

                        // Store Original
                        $this->saveOriginal($asset, $imageStream->getContent(), $aspectRatio);

                        // Save Renditions
                        foreach ($imageOption->getFinalRenditions($aspectRatio) as $rendition) {
                            $this->saveRendition($asset, $rendition, $imageOption->getQuality(), $aspectRatio);
                        }
                    }

                    if ($streamsFound) {
                        // Associate Asset
                        $reflex = new \ReflectionMethod(get_class($entity), $imageOption->getSetter());
                        $reflex->invoke($entity, $asset);

                        $this->em->flush($entity);
                    }
                }

                $assetResponse->addAsset($type, $asset);
            }

            $assetResponse->setStatus(true);

            $this->em->commit();
        } catch (ImageException $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error generating the image from the stream , ImageException: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('Error generating the image from the stream , ImageException: %s', $e->getMessage()));
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error saving the image, Exception: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('Error saving the image, Exception: %s', $e->getMessage()));
        }

        return $assetResponse;
    }

    public function createAsset(array $data, $entity): ?Asset
    {
        try {
            $imageOption = $this->getOption($this->getEntityCode($entity), $data['type']);

            $asset = new Asset();
            $asset->setType($imageOption->getAssetType())
                ->setExtension($data['extension']);

            $this->em->persist($asset);
            $this->em->flush($asset);

            return $asset;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error creating the asset, Exception: %s', $e->getMessage()));
        }

        return null;
    }

    public function createRenditions(Asset $asset, array $data, $entity, string $aspectRatio): void
    {
        try {
            $imageOption = $this->getOption($this->getEntityCode($entity), $data['type']);

            $imageStream = ImageStream::getInstanceFromStream($data['stream']);

            // Store Original
            $this->saveOriginal($asset, $imageStream->getContent(), $aspectRatio);

            // Save Renditions
            foreach ($imageOption->getFinalRenditions($aspectRatio) as $rendition) {
                $this->saveRendition($asset, $rendition, $imageOption->getQuality(), $aspectRatio);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error saving the asset stream, Exception: %s', $e->getMessage()));

            throw $e;
        }
    }

    public function getProfiles($entity): array
    {
        $entity = $this->getEntityCode($entity);
        if (isset($this->imageOptions['entities'][$entity])) {
            return $this->imageOptions['entities'][$entity];
        }

        return [];
    }

    protected function saveOriginal(Asset $asset, string $stream, string $aspectRatio): void
    {
        $directory = $this->createDirectory($asset);

        $fileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
        file_put_contents($fileName, $stream);

        if (! file_exists($fileName)) {
            throw new \Exception(sprintf('Original file for asset cannot be saved (%s)', $fileName));
        }
    }

    protected function saveRendition(Asset $asset, string $rendition, int $quality, string $aspectRatio): void
    {
        $directory = $this->assetsDirectory . '/' . $this->getBaseDirectory($asset);

        $originalFileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
        $renditionFileName = sprintf('%s/%u.%s.%s', $directory, $asset->getId(), $rendition['n'], $asset->getExtension());

        try {
            $image = $this->getImageManager()->make($originalFileName)->fit($rendition['w'], $rendition['h']);

            $image->interlace(false);
            $image->save($renditionFileName, $quality);
        } catch (NotReadableException $e) {
            $this->logger->error(sprintf('Catch NotReadableException saving rendition %s using original %s', $renditionFileName, $originalFileName));

            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Catch Generic Exception saving rendition %s using original %s', $renditionFileName, $originalFileName));

            throw $e;
        }
    }

    protected function createDirectory(Asset $asset): string
    {
        $directory = $this->assetsDirectory . '/' . $this->getBaseDirectory($asset);

        if (!file_exists($directory)) {
            $parentDir = dirname($directory);
            if (!file_exists($parentDir)) {
                $preParentDir = dirname($parentDir);
                if (!file_exists($preParentDir)) {
                    mkdir($preParentDir);
                }
                mkdir($parentDir);
            }
            mkdir($directory);
        }

        return $directory;
    }
}
