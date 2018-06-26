<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionListConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurablePermissionListConfigurationTest extends \PHPUnit\Framework\TestCase
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
                    'default' => true,
                    'entities' => [],
                    'workflows' => [],
                    'capabilities' => [],
                ]
            ]
        ];

        yield 'configuration permissions list 2' => [
            'config' => [
                'commerce' => [
                    'entities' => [
                        'Entity1' => ['create' => false]
                    ]
                ]
            ],
            'expected' => [
                'commerce' => [
                    'default' => true,
                    'entities' => [
                        'Entity1' => ['CREATE' => false]
                    ],
                    'capabilities' => [],
                    'workflows' => [],
                ]
            ]
        ];

        yield 'configuration permissions list 3' => [
            'config' => [
                'commerce' => [
                    'default' => false,
                    'capabilities' => [
                        'test1' => false,
                        'test2' => false
                    ],
                    'workflows' => [
                        'workflow1' => [
                            'Test1' => true,
                            'test2' => false
                        ]
                    ],
                ]
            ],
            'expected' => [
                'commerce' => [
                    'default' => false,
                    'entities' => [],
                    'capabilities' => [
                        'test1' => false,
                        'test2' => false
                    ],
                    'workflows' => [
                        'workflow1' => [
                            'TEST1' => true,
                            'TEST2' => false
                        ]
                    ],
                ]
            ]
        ];

        yield 'boolean values' => [
            'config' => [
                'commerce' => [
                    'default' => true,
                    'entities' => ['Entity1' => true],
                    'capabilities' => [],
                    'workflows' => ['workflow1' => false],
                ]
            ],
            'expected' => [
                'commerce' => [
                    'default' => true,
                    'entities' => ['Entity1' => true],
                    'capabilities' => [],
                    'workflows' => ['workflow1' => false],
                ]
            ]
        ];
    }

    /**
     * @dataProvider configurationExceptionProvider
     *
     * @param array $config
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testProcessConfigurationException(array $config, $exception, $exceptionMessage)
    {
        $config = [
            ConfigurablePermissionListConfiguration::ROOT_NODE_NAME => $config
        ];
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->configuration->processConfiguration($config);
    }

    /**
     * @return \Generator
     */
    public function configurationExceptionProvider()
    {
        yield 'not array or boolean value for entity item' => [
            'config' => [
                'commerce' => [
                    'entities' => ['Entity1' => 1],
                ]
            ],
            'exception' => InvalidConfigurationException::class,
            'exceptionMessage' => 'For node "entities" allowed only array or boolean value',
        ];

        yield 'not boolean value for capability item' => [
            'config' => [
                'commerce' => [
                    'capabilities' => ['capability1' => 1],
                ]
            ],
            'exception' => InvalidConfigurationException::class,
            'exceptionMessage' => 'For items of node "capabilities" allowed only boolean values',
        ];

        yield 'not boolean value for workflow permission' => [
            'config' => [
                'commerce' => [
                    'workflows' => ['workflow1' => ['permission1' => 1]],
                ]
            ],
            'exception' => InvalidConfigurationException::class,
            'exceptionMessage' => 'For every permission of node "workflows" can be set only boolean value',
        ];
    }
}
