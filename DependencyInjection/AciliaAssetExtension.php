<?php

namespace Acilia\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AciliaAssetExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Locator
        $locator = new FileLocator(__DIR__.'/../Resources/config');

        $loader = new Loader\YamlFileLoader($container, $locator);
        $loader->load('services.yml');

        $processor = new Processor();

        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        // Load Images mapping
        $images = Yaml::parse(file_get_contents($config['assets_images']));
        $container->setParameter('acilia_asset.assets_images', $images);
        $container->setParameter('acilia_asset.assets_dir', $config['assets_dir']);
        $container->setParameter('acilia_asset.assets_public', $config['assets_public']);
        $container->setParameter('acilia_asset.assets_domain', $config['assets_domain']);

        // Load Files mapping
        $files = ($config['assets_files'] !== false) ? Yaml::parse(file_get_contents($config['assets_files'])) : false;
        $container->setParameter('acilia_asset.assets_files', $files);
        $container->setParameter('acilia_asset.assets_files_dir', $config['assets_files_dir']);
        $container->setParameter('acilia_asset.assets_files_public', $config['assets_files_public']);
        $container->setParameter('acilia_asset.assets_files_domain', $config['assets_files_domain']);
    }
}
