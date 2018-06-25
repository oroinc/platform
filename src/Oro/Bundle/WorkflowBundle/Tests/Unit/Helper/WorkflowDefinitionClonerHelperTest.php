<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDefinitionClonerHelper;

class WorkflowDefinitionClonerHelperTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @dataProvider copyConfigurationVariablesProvider
     *
     * @param array $definition
     * @param array $source
     * @param array $expected
     */
    public function testCopyConfigurationVariables($definition, $source, $expected)
    {
        $sourceDefinition = $this->createDefinition();
        $existingDefinition = $this->createDefinition();

        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        $existingConfig[$definitionsNode][$variablesNode] = $definition;
        $existingDefinition->setConfiguration($existingConfig);

        $sourceConfig[$definitionsNode][$variablesNode] = $source;
        $sourceDefinition->setConfiguration($sourceConfig);

        $mergedConfiguration = WorkflowDefinitionClonerHelper::copyConfigurationVariables(
            $existingDefinition,
            $sourceDefinition
        );

        $this->assertEquals($mergedConfiguration[$definitionsNode][$variablesNode], $expected);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function copyConfigurationVariablesProvider()
    {
        return [
            'no_existing_definition' => [
                'definition' => [],
                'source' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
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
                            'identifier' => 'id'
                        ]
                    ]
                ]
            ],
            'correct_merge' => [
                'definition' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'source' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => null,
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
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
                            'identifier' => 'id'
                        ]
                    ]
                ]
            ],
            'type_change' => [
                'definition' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'string',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'source' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ]
            ],
            'class_change' => [
                'definition' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'source' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'DateTime',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'DateTime',
                            'identifier' => 'id'
                        ]
                    ]
                ]
            ],
            'identifier_change' => [
                'definition' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_1',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    ]
                ],
                'source' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'code'
                        ]
                    ]
                ],
                'expected' => [
                    'test_variable' => [
                        'label' => 'label',
                        'value' => 'value_2',
                        'type' => 'entity',
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'code'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return WorkflowDefinition
     */
    protected function createDefinition()
    {
        $step1 = new WorkflowStep();
        $step1->setName('step1');

        $step2 = new WorkflowStep();
        $step2->setName('step2');

        $steps = new ArrayCollection([$step1, $step2]);

        $entityAcl1 = new WorkflowEntityAcl();
        $entityAcl1->setStep($step1);

        $entityAcl2 = new WorkflowEntityAcl();
        $entityAcl2->setStep($step2);

        $entityAcls = new ArrayCollection([$entityAcl1, $entityAcl2]);

        $restriction1 = new WorkflowRestriction();
        $restriction1->setStep($step1);

        $restriction2 = new WorkflowRestriction();
        $restriction2->setStep($step2);

        $restrictions = new ArrayCollection([$restriction1, $restriction2]);

        $definition = new WorkflowDefinition();
        $definition
            ->setSteps($steps)
            ->setStartStep($step2)
            ->setEntityAcls($entityAcls)
            ->setRestrictions($restrictions)
            ->setApplications(['app1', 'app2']);

        return $definition;
    }
}
