<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\VariableHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use PHPUnit\Framework\TestCase;

class VariableHandlerTest extends TestCase
{
    private VariableHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new VariableHandler();
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input): void
    {
        $actual = $this->handler->handle($input);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function handleDataProvider(): array
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
            'no value configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            [
                                'name'  => 'test_variable',
                                'value' => null,
                                'label' => 'Test Variable',
                                'type'  => 'string'
                            ],
                        ],
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'test_variable' => [
                                'label' => 'Test Variable', //should be kept as filtering disposed to another class
                                'type'  => 'string',
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
                                'type'  => 'string'
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
                                'type'  => 'string'
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testHandleEmptyConfiguration(): void
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
