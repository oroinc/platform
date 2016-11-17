<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowAssembler;

    /** @var WorkflowDefinitionConfigurationBuilder */
    protected $builder;

    protected function setUp()
    {
        $this->workflowAssembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new WorkflowDefinitionConfigurationBuilder($this->workflowAssembler);
    }

    /**
     * @param WorkflowDefinition $definition
     * @return array
     */
    protected function getDataAsArray(WorkflowDefinition $definition)
    {
        $data = [
            'name' => $definition->getName(),
            'label' => $definition->getLabel(),
            'entity' => $definition->getRelatedEntity(),
            'defaults' => ['active' => $definition->isActive()],
            'priority' => $definition->getPriority(),
            'configuration' => $definition->getConfiguration(),
        ];

        if ($definition->getStartStep()) {
            $data['start_step'] = $definition->getStartStep()->getName();
        }

        return $data;
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param array $expectedAcls
     * @dataProvider buildFromConfigurationDataProvider
     */
    public function testBuildFromConfiguration(array $inputData, array $expectedData, array $expectedAcls = [])
    {
        $workflowConfiguration = current($inputData);
        $definition = new WorkflowDefinition();
        $definition->setConfiguration($workflowConfiguration);

        $stepManager = new StepManager($this->getSteps($workflowConfiguration));
        $attributeManager = new AttributeManager($this->getAttributes($workflowConfiguration));
        $transitionManager = new TransitionManager($this->getTransitions($workflowConfiguration));

        $activeGroups = [];
        $recordGroups = [];
        if (!empty($workflowConfiguration[WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS])) {
            $activeGroups = array_map(
                'strtolower',
                $workflowConfiguration[WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS]
            );
        }
        if (!empty($workflowConfiguration[WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS])) {
            $recordGroups = array_map(
                'strtolower',
                $workflowConfiguration[WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS]
            );
        }

        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStepManager', 'getAttributeManager', 'getRestrictions', 'getTransitionManager'])
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));
        $workflow->expects($this->any())
            ->method('getAttributeManager')
            ->will($this->returnValue($attributeManager));
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $workflow->expects($this->any())
            ->method('getRestrictions')
            ->will($this->returnValue([]));
        $workflow->setDefinition($definition);

        $this->workflowAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'), false)
            ->willReturn($workflow);

        $workflowDefinitions = $this->builder->buildFromConfiguration($inputData);
        $this->assertCount(1, $workflowDefinitions);

        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = current($workflowDefinitions);
        $this->assertEquals($expectedData, $this->getDataAsArray($workflowDefinition));
        $this->assertEquals($workflowDefinition->getExclusiveActiveGroups(), $activeGroups);
        $this->assertEquals($workflowDefinition->getExclusiveRecordGroups(), $recordGroups);

        $actualAcls = $workflowDefinition->getEntityAcls()->toArray();
        $this->assertSameSize($expectedAcls, $actualAcls);
        foreach ($expectedAcls as $expectedAcl) {
            /** @var WorkflowEntityAcl $actualAcl */
            $actualAcl = array_shift($actualAcls);
            $this->assertEquals($expectedAcl['step'], $actualAcl->getStep()->getName());
            $this->assertEquals($expectedAcl['attribute'], $actualAcl->getAttribute());
            $this->assertEquals($expectedAcl['permissions']['UPDATE'], $actualAcl->isUpdatable());
            $this->assertEquals($expectedAcl['permissions']['DELETE'], $actualAcl->isDeletable());
        }
    }

    /**
     * @return array
     */
    public function buildFromConfigurationDataProvider()
    {
        $minimumConfiguration = [
            'label' => 'Test Workflow',
            'entity' => 'My\Entity',
            'defaults' => ['active' => false],
            'priority' => 0,
        ];

        $maximumConfiguration = [
            'label' => 'Test Workflow',
            'is_system' => true,
            'entity' => 'My\Entity',
            'defaults' => ['active' => false],
            'priority' => 1,
            'start_step' => 'test_step',
            'entity_attribute' => 'my_entity',
            'steps_display_ordered' => true,
            WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS => [
                'active_group1',
                'active_group2',
            ],
            WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS => [
                'record_group1',
                'record_group2',
            ],
            WorkflowConfiguration::NODE_STEPS => [
                [
                    'name' => 'first',
                    'entity_acl' => [
                        'entity_attribute' => [
                            'update' => false,
                        ]
                    ],
                    'is_final' => true,
                ],
            ],
            WorkflowConfiguration::NODE_ATTRIBUTES => [
                [
                    'name' => 'string_attribute',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_attribute',
                    'type' => 'entity',
                    'entity_acl' => [
                        'delete' => false,
                    ],
                    'options' => [
                        'class' => 'TestClass',
                    ],
                ],
            ],
            WorkflowConfiguration::NODE_TRANSITIONS => [
                [
                    'name' => 'transit1',
                    WorkflowConfiguration::NODE_INIT_ENTITIES => ['entity1', 'entity2'],
                    WorkflowConfiguration::NODE_INIT_ROUTES => ['route1', 'route2'],
                    'is_start' => true,
                ],
            ],
        ];

        return [
            'minimum configuration' => [
                'inputData' => [
                    'test_workflow' => $minimumConfiguration,
                ],
                'expectedData' => [
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => 'My\Entity',
                    'defaults' => ['active' => false],
                    'priority' => 0,
                    'configuration' => $this->filterConfiguration($minimumConfiguration),
                ],
            ],
            'maximum configuration' => [
                'inputData' => [
                    'test_workflow' => $maximumConfiguration,
                ],
                'expectedData' => [
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'start_step' => 'test_step',
                    'entity' => 'My\Entity',
                    'defaults' => ['active' => false],
                    'priority' => 1,
                    'configuration' => $this->filterConfiguration(
                        array_merge(
                            $maximumConfiguration,
                            [
                                WorkflowConfiguration::NODE_INIT_ENTITIES => [
                                    'entity1' => ['transit1'],
                                    'entity2' => ['transit1'],
                                ],
                                WorkflowConfiguration::NODE_INIT_ROUTES => [
                                    'route1' => ['transit1'],
                                    'route2' => ['transit1'],
                                ],
                            ]
                        )
                    ),
                ],
                'expected_acls' => [
                    [
                        'step' => 'first',
                        'attribute' => 'entity_attribute',
                        'permissions' => ['UPDATE' => false, 'DELETE' => false],
                    ],
                    [
                        'step' => 'test_step',
                        'attribute' => 'entity_attribute',
                        'permissions' => ['UPDATE' => true, 'DELETE' => false],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function filterConfiguration(array $configuration)
    {
        $configurationKeys = [
            WorkflowConfiguration::NODE_STEPS,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            WorkflowConfiguration::NODE_TRANSITIONS,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            WorkflowConfiguration::NODE_INIT_ENTITIES,
            WorkflowConfiguration::NODE_INIT_ROUTES,
        ];

        return array_intersect_key($configuration, array_flip($configurationKeys));
    }

    /**
     * @param string $expectedException
     * @param string $expectedMessage
     * @param array $inputData
     * @dataProvider buildFromConfigurationExceptionDataProvider
     */
    public function testBuildFromConfigurationException($expectedException, $expectedMessage, array $inputData)
    {
        $this->setExpectedException($expectedException, $expectedMessage);

        $this->builder->buildFromConfiguration($inputData);
    }

    /**
     * @return array
     */
    public function buildFromConfigurationExceptionDataProvider()
    {
        return [
            'no entity' => [
                'expectedException' => '\Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException',
                'expectedMessage' => 'Configuration option "entity" is required',
                'inputData' => [
                    'test_workflow' => [
                        'label' => 'My Entity'
                    ],
                ],
            ],
        ];
    }

    public function testAddExtension()
    {
        $firstExtension = $this->getMock(WorkflowDefinitionBuilderExtensionInterface::class);
        $interruptionExtension = $this->getMock(WorkflowDefinitionBuilderExtensionInterface::class);
        $this->builder->addExtension($firstExtension);
        $this->builder->addExtension($interruptionExtension);

        $name = 'workflow_name';
        $configuration = ['label' => 'Label'];

        $modifiedConfiguration = ['label' => 'Label Modified'];

        $firstExtension->expects($this->once())
            ->method('prepare')
            ->with($name, $configuration)
            ->willReturn($modifiedConfiguration);

        $interruptionExtension->expects($this->once())
            ->method('prepare')
            ->with($name, $modifiedConfiguration)
            ->willThrowException(new \Exception('interrupted by extension'));

        $this->setExpectedException(\Exception::class, 'interrupted by extension');
        $this->builder->buildOneFromConfiguration($name, $configuration);
    }

    /**
     * @param array $configuration
     *
     * @return Step[]
     */
    private function getSteps(array $configuration)
    {
        $steps = [];
        if (!empty($configuration[WorkflowConfiguration::NODE_STEPS])) {
            foreach ($configuration[WorkflowConfiguration::NODE_STEPS] as $stepData) {
                $step = new Step();
                $step->setName($stepData['name']);
                if (!empty($stepData['entity_acl'])) {
                    $step->setEntityAcls($stepData['entity_acl']);
                }
                if (array_key_exists('is_final', $stepData)) {
                    $step->setFinal($stepData['is_final']);
                }
                $steps[] = $step;
            }
        }
        if (!empty($configuration['start_step'])) {
            $step = new Step();
            $step->setName($configuration['start_step']);
            $steps[] = $step;
        }

        return $steps;
    }

    /**
     * @param array $configuration
     *
     * @return Attribute[]
     */
    private function getAttributes(array $configuration)
    {
        $attributes = [];
        if (!empty($configuration[WorkflowConfiguration::NODE_ATTRIBUTES])) {
            foreach ($configuration[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeData) {
                $attribute = new Attribute();
                $attribute->setName($attributeData['name'])
                    ->setType($attributeData['type']);
                if (!empty($attributeData['entity_acl'])) {
                    $attribute->setEntityAcl($attributeData['entity_acl']);
                }
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * @param array $configuration
     *
     * @return Transition[]
     */
    private function getTransitions(array $configuration)
    {
        $transitions = [];
        if (!empty($configuration[WorkflowConfiguration::NODE_TRANSITIONS])) {
            foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionData) {
                $transition = new Transition();
                $transition
                    ->setStart($this->getOption($transitionData, 'is_start', false))
                    ->setName($transitionData['name'])
                    ->setInitEntities($this->getOption($transitionData, WorkflowConfiguration::NODE_INIT_ENTITIES, []))
                    ->setInitRoutes($this->getOption($transitionData, WorkflowConfiguration::NODE_INIT_ROUTES, []))
                    ->setInitContextAttribute(
                        $this->getOption($transitionData, WorkflowConfiguration::NODE_INIT_CONTEXT_ATTRIBUTE, '')
                    );
                $transitions[] = $transition;
            }
        }

        return $transitions;
    }

    /**
     * @param array $data
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    private function getOption(array $data, $option, $default = null)
    {
        return isset($data[$option]) ? $data[$option] : $default;
    }
}
