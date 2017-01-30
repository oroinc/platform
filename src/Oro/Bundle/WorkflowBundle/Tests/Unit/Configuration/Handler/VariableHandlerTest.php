<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\VariableHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class VariableHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VariableHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new VariableHandler();
    }

    /**
     * @param array $expected
     * @param array $input
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $actual = $this->handler->handle($input);

        $this->assertEquals($expected, $actual);
    }

    public function handleDataProvider()
    {
        return [
            'no configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [],
                    ],
                ],
                'input' => [],
            ],
            'empty variable_definition configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [],
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [],
                ],
            ],
            'empty variables configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [],
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [],
                    ],
                ],
            ],
            'simple configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            [
                                'name'  => 'test_variable',
                                'value' => 'value_1',
                            ],
                        ],
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'test_variable' => [
                                'value' => 'value_1'
                            ],
                        ],
                    ],
                ],
            ],
            'full configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            [
                                'name'  => 'test_variable',
                                'value' => 'value_1',
                                'label' => 'Test Variable',
                                'type'  => 'entity',
                            ],
                        ],
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'test_variable' => [
                                'value' => 'value_1',
                                'label' => 'Test Variable', //should be kept as filtering disposed to another class
                                'type'  => 'entity',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = [
            WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                WorkflowConfiguration::NODE_VARIABLES => [
                    'test_variable' => [],
                ],
            ],
        ];

        $result = $this->handler->handle($configuration);

        $variables = $result[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS][WorkflowConfiguration::NODE_VARIABLES];
        $this->assertCount(1, $variables);
        $step = current($variables);

        $this->assertArrayHasKey('name', $step);
        $this->assertEquals('test_variable', $step['name']);
    }
}
