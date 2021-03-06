<?php

namespace Acilia\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('acilia_asset');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->scalarNode('assets_images')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('assets_dir')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('assets_public')->defaultValue('/media')->end()
                ->scalarNode('assets_domain')->isRequired()->cannotBeEmpty()->end()

                ->scalarNode('assets_files')->defaultValue(false)->end()
                ->scalarNode('assets_files_dir')->defaultValue('')->end()
                ->scalarNode('assets_files_public')->defaultValue('/files')->end()
                ->scalarNode('assets_files_domain')->defaultValue('')->end()
            ->end();

        return $treeBuilder;
    }
}
