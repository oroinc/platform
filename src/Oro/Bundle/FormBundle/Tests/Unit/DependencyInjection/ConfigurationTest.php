<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        /** @var $root ArrayNode */
        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('oro_form', $root->getName());
    }

    public function testProcessConfiguration()
    {
        $processor = new Processor();

        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'wysiwyg_enabled' => [
                        'value' => true,
                        'scope' => 'app'
                    ]
                ],
                'purifier' => [
                    'scope_1' => [
                        'html_purifier_mode' => 'strict',
                        'html_purifier_iframe_domains' => [],
                        'html_purifier_uri_schemes' => [],
                        'html_allowed_elements' => [],
                    ],
                    'scope_2' => [
                        'html_purifier_mode' => 'disabled',
                        'html_purifier_iframe_domains' => [],
                        'html_purifier_uri_schemes' => [],
                        'html_allowed_elements' => [],
                    ],
                    'scope_3' => [
                        'html_purifier_mode' => 'extended',
                        'html_purifier_iframe_domains' => [],
                        'html_purifier_uri_schemes' => [],
                        'html_allowed_elements' => [
                            'table' => [
                                'attributes' => ['cellspacing', 'cellpadding'],
                                'hasClosingTag' => true
                            ]
                        ],
                    ]
                ]
            ],
            $processor->processConfiguration(new Configuration(), [[
                'purifier' => [
                    'scope_1' => [],
                    'scope_2' => [
                        'html_purifier_mode' => 'disabled'
                    ],
                    'scope_3' => [
                        'html_purifier_mode' => 'extended',
                        'html_allowed_elements' => [
                            'table' => [
                                'attributes' => ['cellspacing', 'cellpadding']
                            ]
                        ]
                    ],
                ]
            ]])
        );
    }

    public function testProcessConfigurationEmpty()
    {
        $processor = new Processor();

        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'wysiwyg_enabled' => [
                        'value' => true,
                        'scope' => 'app'
                    ]
                ],
                'purifier' => []
            ],
            $processor->processConfiguration(new Configuration(), [])
        );
    }
}
