<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_attachment');
        $rootNode
            ->children()
                ->booleanNode('debug_images')
                    ->defaultTrue()
                ->end()
                ->arrayNode('upload_file_mime_types')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('upload_image_mime_types')
                    ->prototype('scalar')
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'upload_file_mime_types'  => ['value' => null],
                'upload_image_mime_types' => ['value' => null]
            ]
        );

        $rootNode
            ->validate()
                ->always(function ($v) {
                    if (null === $v['settings']['upload_file_mime_types']['value']) {
                        $v['settings']['upload_file_mime_types']['value'] = MimeTypesConverter::convertToString(
                            $v['upload_file_mime_types']
                        );
                    }
                    if (null === $v['settings']['upload_image_mime_types']['value']) {
                        $v['settings']['upload_image_mime_types']['value'] = MimeTypesConverter::convertToString(
                            $v['upload_image_mime_types']
                        );
                    }

                    return $v;
                })
            ->end();

        return $treeBuilder;
    }
}
