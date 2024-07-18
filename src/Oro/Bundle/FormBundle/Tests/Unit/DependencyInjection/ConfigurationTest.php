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
                    Configuration::WYSIWYG_ENABLED => [
                        'value' => true,
                        'scope' => 'app'
                    ],
                    Configuration::ENABLED_CAPTCHA => [
                        'value' => false,
                        'scope' => 'app'
                    ],
                    Configuration::CAPTCHA_SERVICE => [
                        'value' => 'recaptcha',
                        'scope' => 'app'
                    ],
                    Configuration::CAPTCHA_PROTECTED_FORMS => [
                        'value' => [],
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_PRIVATE_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_MINIMAL_ALLOWED_SCORE => [
                        'value' => '0.5',
                        'scope' => 'app'
                    ],
                    Configuration::HCAPTCHA_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::HCAPTCHA_PRIVATE_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::TURNSTILE_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::TURNSTILE_PRIVATE_KEY => [
                        'value' => '',
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
                    Configuration::WYSIWYG_ENABLED => [
                        'value' => true,
                        'scope' => 'app'
                    ],
                    Configuration::ENABLED_CAPTCHA => [
                        'value' => false,
                        'scope' => 'app'
                    ],
                    Configuration::CAPTCHA_SERVICE => [
                        'value' => 'recaptcha',
                        'scope' => 'app'
                    ],
                    Configuration::CAPTCHA_PROTECTED_FORMS => [
                        'value' => [],
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_PRIVATE_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::RECAPTCHA_MINIMAL_ALLOWED_SCORE => [
                        'value' => '0.5',
                        'scope' => 'app'
                    ],
                    Configuration::HCAPTCHA_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::HCAPTCHA_PRIVATE_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::TURNSTILE_PUBLIC_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::TURNSTILE_PRIVATE_KEY => [
                        'value' => '',
                        'scope' => 'app'
                    ]
                ],
                'html_purifier_modes' => []
            ],
            $this->processConfiguration([])
        );
    }
}
