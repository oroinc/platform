<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionGroupListConfiguration;

class ActionGroupListConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActionGroupListConfiguration
     */
    protected $configuration;

    public function setUp()
    {
        $this->configuration = new ActionGroupListConfiguration();
    }

    /**
     * @param array $config
     * @param array $expected
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $config, array $expected)
    {
        $this->assertEquals($expected, $this->configuration->processConfiguration($config));
    }

    /**
     * @return array
     */
    public function processValidConfigurationProvider()
    {
        return [
            'empty configuration' => [
                'config' => [],
                'expected' => []
            ],
            'min valid configuration' => [
                'config' => [
                    'test' => []
                ],
                'expected' => [
                    'test' => [
                        'parameters' => [],
                        'conditions' => [],
                        'actions' => []
                    ]
                ]
            ],
            'max valid configuration' => [
                'config' => [
                    'test' => [
                        'acl_resource' => ['EDIT', new \stdClass()],
                        'parameters' => [
                            'arg1' => [
                                'type' => 'string',
                                'message' => 'Exception message',
                                'default' => 'test string'
                            ],
                            'arg2' => []
                        ],
                        'conditions' => [
                            '@equal' => ['a1', 'b1']
                        ],
                        'actions' => [
                            '@assign_value' => ['$field1', 'value2']
                        ]
                    ]
                ],
                'expected' => [
                    'test' => [
                        'acl_resource' => ['EDIT', new \stdClass()],
                        'parameters' => [
                            'arg1' => [
                                'type' => 'string',
                                'message' => 'Exception message',
                                'default' => 'test string'
                            ],
                            'arg2' => []
                        ],
                        'conditions' => [
                            '@equal' => ['a1', 'b1']
                        ],
                        'actions' => [
                            '@assign_value' => ['$field1', 'value2']
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider processInvalidConfigurationProvider
     *
     * @param array $config
     * @param string $message
     */
    public function testProcessInvalidConfiguration(array $config, $message)
    {
        $this->expectException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $this->expectExceptionMessage($message);

        $this->configuration->processConfiguration($config);
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'config' => [
                    'group1' => 'not array value'
                ],
                'message' => 'Invalid type for path "action_groups.group1". Expected array, but got string'
            ],
            'incorrect action_groups[parameters]' => [
                'input' => [
                    'group1' => [
                        'parameters' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.parameters". Expected array, but got string'
            ],
            'incorrect array action_groups[parameters]' => [
                'input' => [
                    'group1' => [
                        'parameters' => ['not array value']
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.parameters.0". Expected array, but got string'
            ],
            'incorrect action_groups[parameters][type]' => [
                'input' => [
                    'group1' => [
                        'parameters' => [
                            'arg1' => [
                                'type' => []
                            ]
                        ]
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.parameters.arg1.type". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect action_groups[parameters][message]' => [
                'input' => [
                    'group1' => [
                        'parameters' => [
                            'arg1' => [
                                'message' => []
                            ]
                        ]
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.parameters.arg1.message". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect action_groups[conditions]' => [
                'input' => [
                    'group1' => [
                        'conditions' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.conditions". Expected array, but got string'
            ],
            'incorrect action_groups[actions]' => [
                'input' => [
                    'group1' => [
                        'actions' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "action_groups.group1.actions". Expected array, but got string'
            ],
        ];
    }
}
