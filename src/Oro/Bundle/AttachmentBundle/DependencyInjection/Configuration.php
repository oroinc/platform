<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for OroAttachmentBundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Maximum upload file size default value.
     */
    private const MAX_FILESIZE_MB = 10;

    public const JPEG_QUALITY = 85;
    public const PNG_QUALITY = 100;

    /**
     * Bytes in one MB. Used to calculate exact bytes in certain MB amount.
     */
    public const BYTES_MULTIPLIER = 1048576;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
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
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'maxsize' => ['value' => self::MAX_FILESIZE_MB],
                'upload_file_mime_types' => ['value' => null],
                'upload_image_mime_types' => ['value' => null],
                'processors_allowed' => ['value' => true],
                'jpeg_quality' => ['value' => self::JPEG_QUALITY],
                'png_quality' => ['value' => self::PNG_QUALITY],
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
