<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurablePermissionConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new ConfigurablePermissionConfiguration(), $configs);
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testProcessConfiguration(array $config, array $expected)
    {
        $this->assertEquals($expected, $this->processConfiguration(['oro_configurable_permissions' => $config]));
    }

    public function configurationProvider(): array
    {
        return [
            'configuration permissions list 1' => [
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
            ],
            'configuration permissions list 2' => [
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
            ],
            'configuration permissions list 3' => [
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
            ],
            'boolean values' => [
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
            ]
        ];
    }

    /**
     * @dataProvider configurationExceptionProvider
     */
    public function testProcessConfigurationException(array $config, string $exception, string $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->processConfiguration(['oro_configurable_permissions' => $config]);
    }

    public function configurationExceptionProvider(): array
    {
        return [
            'not array or boolean value for entity item' => [
                'config' => [
                    'commerce' => [
                        'entities' => ['Entity1' => 1],
                    ]
                ],
                'exception' => InvalidConfigurationException::class,
                'exceptionMessage' => 'For node "entities" allowed only array or boolean value',
            ],
            'not boolean value for capability item' => [
                'config' => [
                    'commerce' => [
                        'capabilities' => ['capability1' => 1],
                    ]
                ],
                'exception' => InvalidConfigurationException::class,
                'exceptionMessage' => 'For items of node "capabilities" allowed only boolean values',
            ],
            'not boolean value for workflow permission' => [
                'config' => [
                    'commerce' => [
                        'workflows' => ['workflow1' => ['permission1' => 1]],
                    ]
                ],
                'exception' => InvalidConfigurationException::class,
                'exceptionMessage' => 'For every permission of node "workflows" can be set only boolean value',
            ]
        ];
    }
}
