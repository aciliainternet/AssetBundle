<?php

namespace Acilia\Bundle\AssetBundle\Service;

use Acilia\Bundle\AssetBundle\Library\File\FileService as AbstractFileService;
use Acilia\Bundle\AssetBundle\Library\Exception\FileException;
use Acilia\Bundle\AssetBundle\Entity\AssetFile;
use Acilia\Bundle\AssetBundle\Library\AssetResponse;
use Acilia\Bundle\AssetBundle\Library\File\FileWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class FileService extends AbstractFileService
{
    protected $em;
    protected $logger;
    protected $fileOptions;
    protected $fileDirectory;
    protected $filePublic;
    protected $fileDomain;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->fileOptions = $params->get('acilia_asset.assets_files');
        $this->fileDirectory = $params->get('acilia_asset.assets_files_dir');
        $this->filePublic = $params->get('acilia_asset.assets_files_public');
        $this->fileDomain = $params->get('acilia_asset.assets_files_domain');
    }

    public function getAssetFromEntity($entity, string $type): ?AssetFile
    {
        $asset = null;
        if (is_object($entity)) {
            $fileOptions = $this->getOption($entity, $type);
            $reflex = new \ReflectionMethod(get_class($entity), $fileOptions->getGetter());
            $asset = $reflex->invoke($entity);
        }

        return $asset;
    }

    public function getUrl(AssetFile $asset): string
    {
        $filename = $this->getAssetFilename($asset);
        $url = '/' . trim($this->filePublic, '/') . '/' . $filename;

        return $url;
    }

    public function handleRequest(Request $request, $entity): AssetResponse
    {
        // create the response object
        $assetResponse = new AssetResponse();
        if (! $request->files->has('file')) {
            $assetResponse->setStatus(true);

            return $assetResponse;
        }

        $assets = $request->files->get('file');
        try {
            $asset = null;
            $this->em->beginTransaction();

            /** @var UploadedFile $fileData  */
            foreach ($assets as $type => $fileData) {
                if ($fileData == null) {
                    continue;
                }

                $fileOption = $this->getOption($this->getEntityCode($entity), $type);

                /**
                 * @todo check delete
                 */
                /*
                if (in_array('::delete::', $aspectRatios)) {
                    $reflex = new ReflectionMethod(get_class($entity), $imageOption->getSetter());
                    $reflex->invoke($entity, null);
                    $this->em->flush($entity);
                }
                */

                try {
                    $fileOption->validate($fileData);
                } catch (\Exception $e) {
                    $assetResponse->setStatus(false);
                    $assetResponse->setErrorMessage($e->getMessage());
                    return $assetResponse;
                }

                $asset = new AssetFile();
                $asset->setType($fileOption->getAssetType())
                    ->setExtension($fileData->getClientOriginalExtension())
                    ->setName($fileData->getClientOriginalName())
                    ->setSize($fileData->getSize())
                    ->setMimeType($fileData->getMimeType());
                $this->em->persist($asset);
                $this->em->flush($asset);


                // Save
                $this->save($asset, $fileData);

                // Associate Asset
                $reflex = new \ReflectionMethod(get_class($entity), $fileOption->getSetter());
                if (!$fileOption->hasWrapper()) {
                    $reflex->invoke($entity, $asset);
                    $this->em->flush($entity);

                // Associate Wrapper
                } else {
                    $reflexGetter = new \ReflectionMethod(get_class($entity), $fileOption->getGetter());
                    $wrapper = $reflexGetter->invoke($entity);
                    if (!$wrapper instanceof FileWrapperInterface) {
                        throw new \Exception(sprintf('Class "%s" must implement FileWrapperInterface interface', get_class($wrapper)));
                    }

                    $wrapper->setAssetFile($asset);
                    $this->em->flush($wrapper);
                }

                $assetResponse->addAsset($type, $asset);
            }

            $assetResponse->setStatus(true);

            $this->em->commit();
        } catch (FileException $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error saving the file, FileException: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('EError saving the file, FileException: %s', $e->getMessage()));
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error(sprintf('Error saving the file, Exception: %s', $e->getMessage()));

            $assetResponse->setStatus(false);
            $assetResponse->setErrorMessage(sprintf('Error saving the file, Exception: %s', $e->getMessage()));
        }

        return $assetResponse;
    }

    public function createAsset(array $data, $entity): ?AssetFile
    {
        try {
            $fileOption = $this->getOption($this->getEntityCode($entity), $data['type']);

            $asset = new AssetFile();
            $asset->setType($fileOption->getAssetType())
                ->setExtension($data['extension']);

            $this->em->persist($asset);
            $this->em->flush($asset);

            return $asset;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error creating the asset file, Exception: %s', $e->getMessage()));
        }

        return null;
    }

    protected function save(AssetFile $asset, UploadedFile $fileData): void
    {
        $directory = $this->createDirectory($asset);

        $fileName = sprintf('%s/%u.%s', $directory, $asset->getId(), $asset->getExtension());
        file_put_contents($fileName, file_get_contents($fileData->getPathname()));

        if (! file_exists($fileName)) {
            throw new \Exception(sprintf('Original file for asset cannot be saved (%s)', $fileName));
        }
    }

    protected function createDirectory(AssetFile $asset): string
    {
        $directory = $this->fileDirectory . '/' . $this->getBaseDirectory($asset);

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
