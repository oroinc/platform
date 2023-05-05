<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testProcessConfiguration(): void
    {
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
            $this->processConfiguration([[
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

    public function testProcessConfigurationEmpty(): void
    {
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
            $this->processConfiguration([])
        );
    }
}
