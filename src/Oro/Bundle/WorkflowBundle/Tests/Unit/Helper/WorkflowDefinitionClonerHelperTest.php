<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowDefinitionClonerHelper;

class WorkflowDefinitionClonerHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider variableDefinitionsProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function testParseVariableDefinitions(array $configuration, array $expected)
    {
        $result = WorkflowDefinitionClonerHelper::parseVariableDefinitions($configuration);

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function variableDefinitionsProvider()
    {
        return [
            'empty_set' => [
                'configuration' => [],
                'expected' => []
            ],
            'value_only' => [
                'configuration' => [
                    'test_variable' => [
                        'value' => 'value_1'
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => null,
                        'value' => 'value_1',
                        'type' => null,
                        'options' => []
                    ]
                ]
            ],
            'minimum_configuration' => [
                'configuration' => [
                    'test_variable' => [
                        'value' => 'value_1',
                        'type' => 'string'
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => null,
                        'value' => 'value_1',
                        'type' => 'string',
                        'options' => []
                    ]
                ]
            ],
            'full_configuration' => [
                'configuration' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id',
                            'form_options' => [
                                'constraints' => [
                                    'NotBlank' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id',
                            'form_options' => [
                                'constraints' => [
                                    'NotBlank' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getOptionsProvider
     *
     * @param array  $options
     * @param string $key
     * @param mixed  $expected
     * @param mixed  $default
     */
    public function testGetOptions(array $options, $key, $expected, $default = null)
    {
        $result = WorkflowDefinitionClonerHelper::getOption($key, $options, $default);

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getOptionsProvider()
    {
        return [
            'empty_set' => [
                'options' => [],
                'key' => null,
                'expected' => null
            ],
            'empty_key' => [
                'options' => [
                    'key' => 'value_1',
                ],
                'key' => null,
                'expected' => null
            ],
            'flat_key' => [
                'options' => [
                    'key' => 'value_1',
                ],
                'key' => 'key',
                'expected' => 'value_1'
            ],
            'nested_key' => [
                'options' => [
                    'key' => [
                        'test' => 'nested_value'
                    ]
                ],
                'key' => 'key.test',
                'expected' => 'nested_value'
            ],
            'empty_nested_key' => [
                'options' => [
                    'key' => [
                        'nested_value'
                    ]
                ],
                'key' => 'key.',
                'expected' => 'nested_value'
            ],
            'empty_nested_key_with_mixed_options' => [
                'options' => [
                    'key' => [
                        'index' => 'value',
                        'nested_value'
                    ]
                ],
                'key' => 'key.',
                'expected' => 'nested_value'
            ],
            'numeric_nested_key' => [
                'options' => [
                    'key' => [
                        'nested_value'
                    ]
                ],
                'key' => 'key.0',
                'expected' => 'nested_value'
            ],
            'multiple_nested_levels_invalid_key' => [
                'options' => [
                    'test_variable' => [
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id',
                            'form_options' => [
                                'constraints' => [
                                    'NotBlank' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'key' => 'test_variable.options.form_options.constraint',
                'expected' => null
            ],
            'multiple_nested_levels_invalid_key_with_default' => [
                'options' => [
                    'test_variable' => [
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id',
                            'form_options' => [
                                'constraints' => [
                                    'NotBlank' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'key' => 'test_variable.options.form_options.constraint',
                'expected' => [],
                'default' => []
            ],
            'multiple_nested_levels' => [
                'options' => [
                    'test_variable' => [
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id',
                            'form_options' => [
                                'constraints' => [
                                    'NotBlank' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'key' => 'test_variable.options.form_options.constraints',
                'expected' => [
                    'NotBlank' => null
                ]
            ],
        ];
    }
}
