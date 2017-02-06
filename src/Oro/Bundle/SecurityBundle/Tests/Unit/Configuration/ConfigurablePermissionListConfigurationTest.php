<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionListConfiguration;

class ConfigurablePermissionListConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigurablePermissionListConfiguration */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configuration = new ConfigurablePermissionListConfiguration();
    }

    /**
     * @dataProvider configurationProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function testProcessConfiguration(array $config, array $expected)
    {
        $config = [
            ConfigurablePermissionListConfiguration::ROOT_NODE_NAME => $config
        ];

        $this->assertEquals($expected, $this->configuration->processConfiguration($config));
    }

    /**
     * @return \Generator
     */
    public function configurationProvider()
    {
        yield 'configuration permissions list 1' => [
            'config' => [
                'commerce' => null
            ],
            'expected' => [
                'commerce' => [
                    'default' => false,
                    'entities' => [],
                    'capabilities' => [],
                ]
            ]
        ];

        yield 'configuration permissions list 2' => [
            'config' => [
                'commerce' => [
                    'entities' => [
                        'Entity1' => ['created' => false]
                    ]
                ]
            ],
            'expected' => [
                'commerce' => [
                    'default' => false,
                    'entities' => [
                        'Entity1' => ['created' => false]
                    ],
                    'capabilities' => [],
                ]
            ]
        ];

        yield 'configuration permissions list 3' => [
            'config' => [
                'commerce' => [
                    'default' => true,
                    'capabilities' => [
                        'test1' => false,
                        'test2' => false
                    ]
                ]
            ],
            'expected' => [
                'commerce' => [
                    'default' => true,
                    'entities' => [],
                    'capabilities' => [
                        'test1' => false,
                        'test2' => false
                    ]
                ]
            ]
        ];
    }
}
