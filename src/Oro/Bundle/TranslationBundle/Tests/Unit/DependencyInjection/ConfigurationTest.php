<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TranslationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /** @var Configuration */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new Configuration();
    }

    public function testGetConfigTreeBuilder()
    {
        $this->assertInstanceOf(TreeBuilder::class, $this->configuration->getConfigTreeBuilder());
    }

    public function testProcessConfiguration()
    {
        $processor = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                'installed_translation_meta' => [
                    'value' => [],
                    'scope' => 'app'
                ]
            ],
            'js_translation' => [
                'domains' => [
                    'jsmessages',
                    'validators'
                ],
                'debug' => '%kernel.debug%'
            ],
            'api' => [
                'crowdin' => [
                    'endpoint' => Configuration::DEFAULT_CROWDIN_API_URL
                ],
                'oro_service' => [
                    'endpoint' => Configuration::DEFAULT_PROXY_API_URL,
                    'key' => ''
                ]
            ],
            'default_api_adapter' => Configuration::DEFAULT_ADAPTER,
            'debug_translator' => false
        ];

        $this->assertEquals(
            $expected,
            $processor->processConfiguration(
                $this->configuration,
                [
                    'oro_translation' => [
                        'js_translation' => [],
                        'api' => []
                    ]
                ]
            )
        );
    }
}
