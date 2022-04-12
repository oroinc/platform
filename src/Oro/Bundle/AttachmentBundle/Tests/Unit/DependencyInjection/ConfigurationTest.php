<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        self::assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        $root = $builder->buildTree();
        self::assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        self::assertEquals('oro_attachment', $root->getName());
    }

    public function testProcessConfiguration(): void
    {
        $expected = [
            'settings' => [
                'png_quality' => [
                    'value' => 100,
                    'scope' => 'app'
                ],
                'jpeg_quality' => [
                    'value' => 85,
                    'scope' => 'app'
                ],
                'maxsize' => [
                    'value' => 10,
                    'scope' => 'app'
                ],
                'upload_file_mime_types' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'upload_image_mime_types' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'processors_allowed' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'original_file_names_enabled' => [
                    'value' => false,
                    'scope' => 'app',
                ],
                'resolved' => true
            ],
            'debug_images' => true,
            'maxsize' => 10,
            'upload_file_mime_types' => [],
            'upload_image_mime_types' => [],
            'png_quality' => 100,
            'jpeg_quality' => 85,
            'processors_allowed' => true
        ];

        $processor = new Processor();

        self::assertEquals($expected, $processor->processConfiguration(new Configuration(), []));
    }
}
