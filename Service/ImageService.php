<?php

namespace Acilia\Bundle\AssetBundle\Service;

use Acilia\Bundle\AssetBundle\Library\Image\ImageService as AbstractImageService;
use Acilia\Bundle\AssetBundle\Library\Exception\ImageException;
use Acilia\Bundle\AssetBundle\Library\Image\ImageStream;
use Acilia\Bundle\AssetBundle\Entity\Asset;
use Acilia\Bundle\AssetBundle\Library\AssetResponse;
use Doctrine\ORM\EntityManager;
use Intervention\Image\ImageManager;
use Intervention\Image\Exception\NotReadableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Exception;

class ImageService extends AbstractImageService
{

    protected $em;
    protected $logger;
    protected $imageOptions;
    protected $assetsDirectory;
    protected $assetsPublic;
    protected $assetDomain;
    protected $imageManager;
    protected $error;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger, $imageOptions, $assetsDirectory, $assetsPublic, $assetDomain)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->imageOptions = $imageOptions;
        $this->assetsDirectory = $assetsDirectory;
        $this->assetsPublic = $assetsPublic;
        $this->assetDomain = $assetDomain;
    }

    /**
     * @return ImageManager
     */
    protected function getImageManager()
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(['driver' => 'imagick']);
        }

        return $this->imageManager;
    }

    /**
     * @param $entity
     * @param $type
     * @return mixed|null
     */
    public function getAssetFromEntity($entity, $type)
    {
        $asset = null;
        if (is_object($entity)) {
            $imageOptions = $this->getOption($entity, $type);
            $reflex = new ReflectionMethod(get_class($entity), $imageOptions->getGetter());
            $asset = $reflex->invoke($entity);
        }

        return $asset;
    }

    /**
     * @param Asset $asset
     * @param string $rendition
     * @param string $size
     *
     * @return string
     */
    public function getUrl(Asset $asset, $rendition = null, $size = null)
    {
        try {
            $imageOption = $this->getOption($asset);
            $sizes = $imageOption->getRendition($rendition);

            $size = isset($sizes[$size]) ? $sizes[$size] : $size;
        } catch (Exception $e) {
            $size = $rendition;
        }

        $filename = $this->getAssetFilename($asset, $size);
        $url = '/' . trim($this->assetsPublic, '/') . '/' . $filename;

        return $url;
    }

    public function handleRequest(Request $request, $entity)
    {
        // create the response object
        $assetResponse = new AssetResponse();
        if (! $request->request->has('asset')) {
            $assetResponse->setStatus(true);

            return $assetResponse;
        }

        $assets = $request->request->get('asset');
        try {
            $this->em->beginTransaction();

            $asset = null;
            foreach ($assets as $type => $aspectRatios) {
                $imageOption = $this->getOption($this->getEntityCode($entity), $type);

                if (in_array('::delete::', $aspectRatios)) {
                    $reflex = new ReflectionMethod(get_class($entity), $imageOption->getSetter());
                    $reflex->invoke($entity, null);
                    $this->em->flush($entity);
                } else {
                    $asset = new Asset();
                    $asset->setType($imageOption->getAssetType())
                        ->setExtension('jpg');
                    $this->em->persist($asset);
                    $this->em->flush($asset);

                    $streamsFound = false;

                    foreach ($aspectRatios as $aspectRatio => $stream) {
                        if (empty($stream)) {
                            continue;
                        }

                        $streamsFound = true;
                        $imageStream = ImageStream::getInstanceFromStream($stream);

                        // persist the asset extension
                        $asset->setExtension($imageStream->getType());
                        $this->em->flush($asset);

                        // Store Original
                        $this->saveOriginal($asset, $imageStream->getContent(), $aspectRatio);

                        // Save Renditions
                        foreach ($imageOption->getFinalRenditions($aspectRatio) as $rendition) {
                            $this->saveRendition($asset, $rendition, $imageOption->getQuality(), $aspectRatio);
                        }
                    }

                    if ($streamsFound) {
                        // Associate Asset
                        $reflex = new ReflectionMethod(get_class($entity), $imageOption->getSetter());
                        $reflex->invoke($entity, $asset);

                        $this->em->flush($entity);
                        $this->em->flush($asset);
                    }
                }
            }

            $assetResponse->setStatus(true);
            $assetResponse->setAsset($asset);

            $this->em->commit();
        } catch (ImageException $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error generating the image from the stream , ImageException: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('Error generating the image from the stream , ImageException: %s', $e->getMessage()));

        } catch (Exception $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error saving the image, Exception: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('Error saving the image, Exception: %s', $e->getMessage()));
        }

        return $assetResponse;
    }

    public function createAsset($data, $entity)
    {
        try {
            $imageOption = $this->getOption($this->getEntityCode($entity), $data['type']);

            $asset = new Asset();
            $asset->setType($imageOption->getAssetType())
                ->setExtension($data['extension']);

            $this->em->persist($asset);
            $this->em->flush($asset);

            return $asset;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Error creating the asset, Exception: %s', $e->getMessage()));
        }

        return null;
    }

    public function createRenditions(Asset $asset, $data, $entity, $aspectRatio)
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
        } catch (Exception $e) {
            $this->logger->error(sprintf('Error saving the asset stream, Exception: %s', $e->getMessage()));

            throw $e;
        }
    }

    protected function saveOriginal(Asset $asset, $stream, $aspectRatio)
    {
        $directory = $this->createDirectory($asset);

        $fileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
        file_put_contents($fileName, $stream);

        if (! file_exists($fileName)) {
            throw new Exception(sprintf('Original file for asset cannot be saved (%s)', $fileName));
        }
    }

    protected function saveRendition(Asset $asset, $rendition, $quality, $aspectRatio)
    {
        $directory = $this->assetsDirectory . '/' . $this->getBaseDirectory($asset);

        $originalFileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
        $renditionFileName = sprintf('%s/%u.%s.%s', $directory, $asset->getId(), $rendition['n'], $asset->getExtension());

        try {
            $image = $this->getImageManager()->make($originalFileName)->resize($rendition['w'], $rendition['h']);
            $image->interlace(false);
            $image->save($renditionFileName, $quality);
        } catch (NotReadableException $e) {
            $this->logger->error(sprintf('Catch NotReadableException saving rendition %s using original %s', $renditionFileName, $originalFileName));

            throw $e;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Catch Generic Exception saving rendition %s using original %s', $renditionFileName, $originalFileName));

            throw $e;
        }
    }

    protected function createDirectory(Asset $asset)
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
