<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    /** Maximum upload file size default value. */
    private const MAX_FILESIZE_MB = 10;

    public const JPEG_QUALITY = 85;
    public const PNG_QUALITY = 100;
    public const WEBP_QUALITY = 85;

    /** Bytes in one MB. Used to calculate exact bytes in certain MB amount. */
    public const BYTES_MULTIPLIER = 1048576;

    public const array DEFAULT_INLINE_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    private const string RENDERABLE_MEDIA_TYPE_PATTERN =
        '#^(text/(html|xml|xsl|cache-manifest|x-component|x-scriptlet|htc)'
        . '|application/(xml|x-shockwave-flash|hta)'
        . '|[^/]+/[^/]*\+xml'
        . '|[^/]+/(x-)?(java|ecma|vb|live|j)script)$#D';

    #[\Override]
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
                ->arrayNode('external_file_details_http_methods')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('regex')->end()
                            ->arrayNode('methods')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
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
        $this->appendOptionGroups($rootNode->children());

        SettingsBuilder::append(
            $rootNode,
            [
                'maxsize' => ['value' => self::MAX_FILESIZE_MB],
                'upload_file_mime_types' => ['value' => null],
                'upload_image_mime_types' => ['value' => null],
                'external_file_details_http_methods' => ['value' => null, 'type' => 'array'],
                'processors_allowed' => ['value' => true],
                'jpeg_quality' => ['value' => self::JPEG_QUALITY],
                'png_quality' => ['value' => self::PNG_QUALITY],
                'webp_quality' => ['value' => self::WEBP_QUALITY],
                'external_file_allowed_urls_regexp' => ['value' => '', 'type'  => 'string'],
                'original_file_names_enabled' => ['type' => 'boolean', 'value' => true],
                'metadata_service_allowed' => ['type' => 'boolean', 'value' => false],
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

                    if (!$v['settings']['external_file_details_http_methods']['value']) {
                        $v['settings']['external_file_details_http_methods']['value'] =
                            $v['external_file_details_http_methods'];
                    }

                    return $v;
                })
            ->end();

        return $treeBuilder;
    }

    /**
     * @param string[] $mimeTypes
     *
     * @return string[]
     *
     * @throws InvalidConfigurationException if a MIME type is invalid or can be rendered as a page by a browser
     */
    private function normalizeInlineMimeTypes(array $mimeTypes): array
    {
        $normalized = [];
        foreach ($mimeTypes as $mimeType) {
            $mediaType = MimeTypesConverter::convertToMediaType((string)$mimeType);
            if (!MimeTypesConverter::isMediaType($mediaType)) {
                throw new InvalidConfigurationException(sprintf(
                    'The value "%s" is not a valid MIME type for "oro_attachment.inline_mime_types".',
                    $mimeType
                ));
            }
            if (1 === preg_match(self::RENDERABLE_MEDIA_TYPE_PATTERN, $mediaType)) {
                throw new InvalidConfigurationException(sprintf(
                    'The MIME type "%s" cannot be opened in the browser, as it can run scripts.'
                        . ' It is not allowed in "oro_attachment.inline_mime_types".',
                    $mimeType
                ));
            }
            $normalized[$mediaType] = $mediaType;
        }

        return array_values($normalized);
    }

    private function appendOptionGroups(NodeBuilder $node): void
    {
        $this->appendInlineMimeTypesOption($node);
        $this->appendCleanupOptions($node);
        $this->appendMetadataServiceOptions($node);
    }

    private function appendInlineMimeTypesOption(NodeBuilder $node): void
    {
        $node->arrayNode('inline_mime_types')
            ->info(
                'The MIME types of the stored files that the "get" file action opens in the browser.'
                    . ' Files of any other type are served as a download.'
            )
            ->prototype('scalar')->end()
            ->defaultValue(self::DEFAULT_INLINE_MIME_TYPES)
            ->validate()
                ->always(fn (array $mimeTypes) => $this->normalizeInlineMimeTypes($mimeTypes))
            ->end()
        ->end();
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

    private function appendMetadataServiceOptions(NodeBuilder $node): void
    {
        $node
            ->arrayNode('metadata_service')
                ->info('The configuration of the external metadata service for image processing.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('service_url')
                        ->info('The URL of the metadata service endpoint.')
                        ->defaultValue(
                            sprintf(
                                '%%env(default:%s:string:%s)%%',
                                // Fallback container parameter.
                                'oro_attachment.metadata_service_url_default',
                                // Environment variable name.
                                'ORO_METADATA_SERVICE_URL'
                            )
                        )
                        ->example(['https://metadata-service.example.com', 'http://127.0.0.1:8040'])
                    ->end()
                    ->scalarNode('api_key')
                        ->info('The API key for authentication with the metadata service.')
                        ->defaultValue(
                            sprintf(
                                '%%env(default:%s:trim:string:%s)%%',
                                // Fallback container parameter.
                                'oro_attachment.metadata_service_api_key_default',
                                // Environment variable name.
                                'ORO_METADATA_SERVICE_API_KEY'
                            )
                        )
                    ->end()
                ->end()
                ->end()
            ->end();
    }
}
