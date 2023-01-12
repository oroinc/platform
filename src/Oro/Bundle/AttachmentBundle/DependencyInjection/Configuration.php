<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /** Maximum upload file size default value. */
    private const MAX_FILESIZE_MB = 10;

    public const JPEG_QUALITY = 85;
    public const PNG_QUALITY = 100;
    public const WEBP_QUALITY = 85;

    /** Bytes in one MB. Used to calculate exact bytes in certain MB amount. */
    public const BYTES_MULTIPLIER = 1048576;

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_attachment');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->booleanNode('debug_images')
                    ->defaultTrue()
                ->end()
                ->integerNode('maxsize')
                    ->min(1)
                    ->defaultValue(self::MAX_FILESIZE_MB)
                ->end()
                ->arrayNode('upload_file_mime_types')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('upload_image_mime_types')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->booleanNode('processors_allowed')
                    ->defaultTrue()
                ->end()
                ->integerNode('png_quality')
                    ->min(1)
                    ->max(100)
                    ->defaultValue(self::PNG_QUALITY)
                ->end()
                ->integerNode('jpeg_quality')
                    ->min(30)
                    ->max(100)
                    ->defaultValue(self::JPEG_QUALITY)
                ->end()
                ->enumNode('webp_strategy')
                    ->info('Strategy for converting uploaded images to WebP format.')
                    ->values([
                        WebpConfiguration::ENABLED_FOR_ALL,
                        WebpConfiguration::ENABLED_IF_SUPPORTED,
                        WebpConfiguration::DISABLED,
                    ])
                    ->defaultValue(WebpConfiguration::ENABLED_IF_SUPPORTED)
                ->end()
            ->end();
        $this->appendCleanupOptions($rootNode->children());

        SettingsBuilder::append(
            $rootNode,
            [
                'maxsize' => ['value' => self::MAX_FILESIZE_MB],
                'upload_file_mime_types' => ['value' => null],
                'upload_image_mime_types' => ['value' => null],
                'processors_allowed' => ['value' => true],
                'jpeg_quality' => ['value' => self::JPEG_QUALITY],
                'png_quality' => ['value' => self::PNG_QUALITY],
                'webp_quality' => ['value' => self::WEBP_QUALITY],
                'external_file_allowed_urls_regexp' => ['value' => '', 'type'  => 'string'],
                'original_file_names_enabled' => ['type' => 'boolean', 'value' => true],
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

    private function appendCleanupOptions(NodeBuilder $node): void
    {
        $node
            ->arrayNode('cleanup')
                ->info('The configuration of the attachment cleanup command.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('collect_attachment_files_batch_size')
                        ->info('The number of attachment files that can be loaded from the filesystem at once.')
                        ->cannotBeEmpty()
                        ->defaultValue(20000)
                    ->end()
                    ->scalarNode('load_existing_attachments_batch_size')
                        ->info(
                            'The number of attachment entities that can be loaded from the database at once'
                            . ' to check whether an attachment file is linked to an attachment entity.'
                        )
                        ->cannotBeEmpty()
                        ->defaultValue(500)
                    ->end()
                    ->scalarNode('load_attachments_batch_size')
                        ->info('The number of attachment entities that can be loaded from the database at once.')
                        ->cannotBeEmpty()
                        ->defaultValue(10000)
                    ->end()
                ->end()
            ->end();
    }
}
