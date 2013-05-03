<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\LessBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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
        $rootNode = $treeBuilder->root('da_less');

        $this->addCompilationSection($rootNode);
        $this->addRolesSection($rootNode);

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }

    /**
     * compilation:
     *     {compilation_id}:
     *         default: {default_less_directory}
     *         override: {override_less_directory}
     *         source: {source_less_filename}
     *         destination: {destination_css_filename}
     */
    private function addCompilationSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('compilation')
                   ->useAttributeAsKey('dumb')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('default')->end()
                            ->scalarNode('override')->end()
                            ->scalarNode('source')
                                ->isRequired(true)
                            ->end()
                            ->scalarNode('destination')
                                ->isRequired(true)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * roles: [{role1}, {role2}]
     */
    private function addRolesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('roles')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
    }
}
