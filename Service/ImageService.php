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
use Aws\S3\S3Client;
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
    protected $s3Client = null;
    protected $s3Bucket = null;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger, $imageOptions, $assetsDirectory, $assetsPublic, $assetDomain)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->imageOptions = $imageOptions;
        $this->assetsDirectory = $assetsDirectory;
        $this->assetsPublic = $assetsPublic;
        $this->assetDomain = $assetDomain;

        if (getenv('ACILIA_ASSET_BUNDLE_MEDIA_BUCKET')) {
            $this->s3Client = new S3Client([
                'region' => getenv('AWS_REGION'),
                'version' => '2006-03-01',
            ]);
            $this->s3Bucket = getenv('ACILIA_ASSET_BUNDLE_MEDIA_BUCKET');
        }
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

            if (is_array($sizes)) {
                $size = isset($sizes[$size]) ? $sizes[$size] : $size;
            } else {
                $size = $imageOption->getFirstSize();
            }
        } catch (Exception $e) {
            $size = $rendition;
        }

        $filename = $this->getAssetFilename($asset, $size);
        $url = '/' . trim($this->assetsPublic, '/') . '/' . $filename;

        if ($this->s3Client !== null) {
            $url = $this->getUrlFromS3($filename);
        }

        return $url;
    }

    public function getUrlFromS3($filename)
    {
        if ($this->s3Client !== null) {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->s3Bucket,
                'Key' => $filename
            ]);
            $request = $this->s3Client->createPresignedRequest($cmd, '+24 hours');

            return (string) $request->getUri();
        }

        throw new Exception('Requested S3 File, but no bucket defined');
    }

    public function handleRequest(Request $request, $entity)
    {
        // create the response object
        $assetResponse = new AssetResponse();
        if (!$request->request->has('asset')) {
            $assetResponse->setStatus(true);

            return $assetResponse;
        }

        $assets = $request->request->get('asset');
        try {
            $this->em->beginTransaction();

            $asset = null;
            foreach ($assets as $type => $aspectRatios) {
                if ($aspectRatios == null) {
                    continue;
                }

                $imageOption = $this->getOption($this->getEntityCode($entity), $type);

                if (in_array('::delete::', $aspectRatios)) {
                    $reflex = new ReflectionMethod(get_class($entity), $imageOption->getSetter());
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
                                ->setExtension(strtolower($imageStream->getType()));
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
                        $reflex = new ReflectionMethod(get_class($entity), $imageOption->getSetter());
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
                ->setExtension(strtolower($data['extension']));

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

    /**
     * @param $entity
     * @return array
     *
     * Returns an array with the profiles defined for the entity
     */
    public function getProfiles($entity)
    {
        $entity = $this->getEntityCode($entity);
        if (isset($this->imageOptions['entities'][$entity])) {
            return $this->imageOptions['entities'][$entity];
        }
        return [];
    }

    protected function saveOriginal(Asset $asset, $stream, $aspectRatio)
    {
        if ($this->s3Client !== null) {
            $fileName = sprintf('%s/%u.original.%s.%s', $this->getBaseDirectory($asset), $asset->getId(), $aspectRatio, $asset->getExtension());
            $this->saveToS3($fileName, $stream, $this->getContentType($asset->getExtension()));
        } else {
            $directory = $this->createDirectory($asset);

            $fileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
            file_put_contents($fileName, $stream);

            if (!file_exists($fileName)) {
                throw new Exception(sprintf('Original file for asset cannot be saved (%s)', $fileName));
            }
        }
    }

    protected function saveRendition(Asset $asset, $rendition, $quality, $aspectRatio)
    {
        if ($this->s3Client !== null) {
            return $this->saveRenditionToS3($asset, $rendition, $quality, $aspectRatio);
        }

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
        } catch (Exception $e) {
            $this->logger->error(sprintf('Catch Generic Exception saving rendition %s using original %s', $renditionFileName, $originalFileName));

            throw $e;
        }
    }

    protected function saveRenditionToS3(Asset $asset, $rendition, $quality, $aspectRatio)
    {
        $directory = $this->getBaseDirectory($asset);
        $originalFileName = sprintf('%s/%u.original.%s.%s', $directory, $asset->getId(), $aspectRatio, $asset->getExtension());
        $object = $this->s3Client->getObject([
            'Bucket' => $this->s3Bucket,
            'Key' => $originalFileName
        ]);
        $tmpFile = tempnam('/tmp', 's3');
        file_put_contents($tmpFile, $object->get('Body'));

        if (!isset($rendition['w']) || $rendition['w'] === null) {
            list($rendition['w'], $rendition['h']) = getimagesize($tmpFile);
            if ($rendition['n'] === 'free') {
                $rendition['n'] = sprintf('original.%s', $rendition['n']);
            }
        }

        $renditionFileName = sprintf('%s/%u.%s.%s', $directory, $asset->getId(), $rendition['n'], $asset->getExtension());

        try {
            $image = $this->getImageManager()->make($tmpFile)->resize($rendition['w'], $rendition['h']);
            $image->interlace(false);
            $renditionContent = $image->encode($asset->getExtension(), $quality);
            $this->saveToS3($renditionFileName, $renditionContent, $this->getContentType($asset->getExtension()));
            unlink($tmpFile);
        } catch (NotReadableException $e) {
            $this->logger->error(sprintf('Catch NotReadableException saving rendition %s using original %s', $renditionFileName, $originalFileName));
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Catch Generic Exception saving rendition %s using original %s', $renditionFileName, $originalFileName));
            throw $e;
        }
    }

    protected function saveToS3($key, $body, $contentType)
    {
        $this->s3Client->upload(
            $this->s3Bucket,
            $key,
            $body,
            'private',
            ['params' => ['ContentType' => $contentType]]
        );
    }

    protected function getContentType($extension)
    {
        switch (strtolower($extension)) {
            case 'png':
                return 'image/png';
                break;
            case 'gif':
                return 'image/gif';
                break;
            case 'webp':
                return 'image/webp';
                break;
            case 'jp2':
                return 'image/jp2';
                break;
            default:
                return 'image/jpeg';
                break;
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
