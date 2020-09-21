<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('oro_attachment', $root->getName());
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

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

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
