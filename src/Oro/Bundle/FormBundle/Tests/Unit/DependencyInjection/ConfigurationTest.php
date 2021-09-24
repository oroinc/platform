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
                'html_purifier_modes' => [
                    'scope_1' => [
                        'extends' => null,
                        'allowed_iframe_domains' => [],
                        'allowed_uri_schemes' => [],
                        'allowed_html_elements' => [],
                        'allowed_rel' => []
                    ],
                    'scope_2' => [
                        'extends' => 'scope_1',
                        'allowed_iframe_domains' => [],
                        'allowed_uri_schemes' => [],
                        'allowed_html_elements' => [],
                        'allowed_rel' => []
                    ],
                    'scope_3' => [
                        'extends' => null,
                        'allowed_iframe_domains' => [],
                        'allowed_uri_schemes' => [],
                        'allowed_html_elements' => [
                            'table' => [
                                'attributes' => ['cellspacing', 'cellpadding'],
                                'hasClosingTag' => true
                            ]
                        ],
                        'allowed_rel' => ['alternate' => true, 'canonical' => true]
                    ]
                ]
            ],
            $processor->processConfiguration(new Configuration(), [[
                'html_purifier_modes' => [
                    'scope_1' => [],
                    'scope_2' => [
                        'extends' => 'scope_1'
                    ],
                    'scope_3' => [
                        'allowed_html_elements' => [
                            'table' => [
                                'attributes' => ['cellspacing', 'cellpadding']
                            ]
                        ],
                        'allowed_rel' => ['alternate', 'canonical']
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
                'html_purifier_modes' => []
            ],
            $processor->processConfiguration(new Configuration(), [])
        );
    }
}
