<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\AttributeAssembler;
use Oro\Bundle\WorkflowBundle\Model\RestrictionAssembler;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepAssembler;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionAssembler;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Component\Action\Exception\AssemblerException;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $workflowParameters = [
        'name' => 'test_name',
        'label' => 'Test Label'
    ];

    /** @var array */
    private $stepConfiguration = [
        'label' => 'Test',
        'name' => 'test'
    ];

    /** @var array */
    private $transitionConfiguration = [
        'label' => 'Test',
        'step_to' => 'test_step',
        'transition_definition' => 'test_transition_definition'
    ];

    /** @var array */
    private $transitionDefinition = [
        'test_transition_definition' => []
    ];

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var AttributeAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeAssembler;

    /** @var StepAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $stepAssembler;

    /** @var TransitionAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionAssembler;

    /** @var RestrictionAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $restrictionAssembler;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var WorkflowAssembler */
    private $workflowAssembler;

    protected function setUp(): void
    {
        $this->workflow = $this->createWorkflow();
        $this->attributeAssembler = $this->createMock(AttributeAssembler::class);
        $this->stepAssembler = $this->createMock(StepAssembler::class);
        $this->transitionAssembler = $this->createMock(TransitionAssembler::class);
        $this->restrictionAssembler = $this->createMock(RestrictionAssembler::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [Workflow::class, $this->workflow],
                [AttributeAssembler::class, $this->attributeAssembler],
                [StepAssembler::class, $this->stepAssembler],
                [TransitionAssembler::class, $this->transitionAssembler],
                [RestrictionAssembler::class, $this->restrictionAssembler],
                [TranslatorInterface::class, $this->translator],
            ]);

        $this->workflowAssembler = new WorkflowAssembler($this->container);
    }

    protected function createWorkflow(): Workflow
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AclManager $aclManager */
        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var RestrictionManager $restrictionManager */
        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        return new Workflow($doctrineHelper, $aclManager, $restrictionManager);
    }

    /**
     * @param array $configuration
     * @param WorkflowStep $startStep
     * @dataProvider assembleDataProvider
     */
    public function testAssemble(array $configuration, $startStep)
    {
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        if ($startStep) {
            $workflowDefinition->addStep($startStep);
        }

        $attributes = $this->expectsAttributeAssembleCalls($workflowDefinition, $configuration);
        $steps = $this->expectStepAssemblerCalls($configuration, $attributes);

        // Assemble transition
        $transitions = ['test_transition' => $this->getTransitionMock(false, 'test_transition')];
        if (!$startStep) {
            $transitions['test_start_transition'] = $this->getTransitionMock(true, 'test_start_transition');
        } else {
            $transitions['__start__'] = $this->getTransitionMock(true, '__start__');
            $workflowDefinition->setStartStep($startStep);
        }

        $transitions = $this->expectTransitionAssemblerCalls($configuration, $steps, $transitions);

        $this->transitionAssembler->expects($this->once())
            ->method('assemble')
            ->with($configuration, $steps)
            ->willReturn($transitions);

        // Translator
        $this->expectTranslatorCalls();

        // test
        $actualWorkflow = $this->workflowAssembler->assemble($workflowDefinition);

        $this->assertSame($this->workflow, $actualWorkflow);
        $this->assertSame($workflowDefinition->getName(), $actualWorkflow->getName());
        $this->assertSame($workflowDefinition->getLabel(), $actualWorkflow->getLabel());
        $this->assertEquals(
            $attributes,
            $actualWorkflow->getAttributeManager()->getAttributes(),
            'Unexpected attributes'
        );
        $this->assertEquals(
            $steps,
            $actualWorkflow->getStepManager()->getSteps(),
            'Unexpected steps'
        );
        $this->assertEquals(
            $transitions->toArray(),
            $actualWorkflow->getTransitionManager()->getTransitions()->toArray(),
            'Unexpected transitions'
        );

        $this->assertEquals(!empty($startStep), $actualWorkflow->getStepManager()->hasStartStep());
    }

    /**
     * @return array
     */
    public function assembleDataProvider()
    {
        $transitions = array('test_transition' => $this->transitionConfiguration);
        $fullConfig = array(
            WorkflowConfiguration::NODE_ATTRIBUTES => array('attributes_configuration'),
            WorkflowConfiguration::NODE_STEPS => array('test_step' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => $transitions,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => $this->transitionDefinition
        );
        $minimalConfig = array(
            WorkflowConfiguration::NODE_STEPS => array('test_step' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => $transitions,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => $this->transitionDefinition
        );
        $customStartTransition = array(
            TransitionManager::DEFAULT_START_TRANSITION_NAME => array(
                'label' => 'My Label',
                'step_to' => 'custom_step',
                'is_start' => true,
                'transition_definition' => '__start___definition'
            )
        );
        $customStartDefinition = array('__start___definition' => array('conditions' => array()));
        $fullConfigWithCustomStart = $minimalConfig;
        $fullConfigWithCustomStart[WorkflowConfiguration::NODE_TRANSITIONS] += $customStartTransition;
        $fullConfigWithCustomStart[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS] += $customStartDefinition;

        $label = $this->getStartTransitionLabel($this->workflowParameters['label']);
        $getDefaultTransition = function ($stepName) use ($label) {
            return array(
                TransitionManager::DEFAULT_START_TRANSITION_NAME => array(
                    'label' => $label,
                    'step_to' => $stepName,
                    'is_start' => true,
                    'is_hidden' => true,
                    'is_unavailable_hidden' => true,
                    'transition_definition' => '__start___definition'
                )
            );
        };

        return [
            'full configuration with start' => [
                'configuration' => array_merge($fullConfig, [
                    WorkflowConfiguration::NODE_TRANSITIONS
                        => $transitions + $getDefaultTransition('test_start_step'),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS
                        => $this->transitionDefinition + ['__start___definition' => []],
                ]),
                'startStep' => (new WorkflowStep())->setName('test_start_step'),
            ],
            'minimal configuration with start' => [
                'configuration' => array_merge($minimalConfig, [
                    WorkflowConfiguration::NODE_TRANSITIONS
                        => $transitions + $getDefaultTransition('test_start_step'),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS
                        => $this->transitionDefinition + ['__start___definition' => []],
                ]),
                'startStep' => (new WorkflowStep())->setName('test_start_step'),
            ],
            'full configuration without start' => [
                'configuration' => array_merge($fullConfig, [
                    WorkflowConfiguration::NODE_TRANSITIONS => $transitions,
                ]),
                'startStep' => null,
            ],
            'minimal configuration without start' => [
                'configuration' => array_merge($minimalConfig, [
                    WorkflowConfiguration::NODE_TRANSITIONS => $transitions,
                ]),
                'startStep' => null,
            ],
            'full configuration with start custom config' => [
                'configuration' => array_merge($fullConfigWithCustomStart, [
                    WorkflowConfiguration::NODE_TRANSITIONS => $transitions + $customStartTransition,
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS
                        => $this->transitionDefinition + $customStartDefinition
                ]),
                'startStep' => (new WorkflowStep())->setName('test_start_step'),
            ],
        ];
    }

    public function testAssembleStartTransitionException()
    {
        $this->expectException(AssemblerException::class);
        $this->expectExceptionMessage(
            'Workflow "test_name" does not contains neither start step nor start transitions'
        );

        $configuration = array(
            WorkflowConfiguration::NODE_ATTRIBUTES => array('attributes_configuration'),
            WorkflowConfiguration::NODE_STEPS => array('test_step' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => $this->transitionConfiguration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => $this->transitionDefinition
        );

        // source data
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        $attributes = $this->expectsAttributeAssembleCalls($workflowDefinition, $configuration);
        $steps = $this->expectStepAssemblerCalls($configuration, $attributes);
        $this->expectTransitionAssemblerCalls($configuration, $steps);
        $this->expectTranslatorCalls();

        $this->workflowAssembler->assemble($workflowDefinition);
    }

    public function testAssembleWithoutValidation()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_ATTRIBUTES => array('attributes_configuration'),
            WorkflowConfiguration::NODE_STEPS => array('test_step' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => $this->transitionConfiguration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => $this->transitionDefinition
        );

        // source data
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        $attributes =$this->expectsAttributeAssembleCalls($workflowDefinition, $configuration);
        $steps = $this->expectStepAssemblerCalls($configuration, $attributes);
        $transitions = $this->expectTransitionAssemblerCalls($configuration, $steps);
        $this->expectTranslatorCalls();

        $workflow = $this->workflowAssembler->assemble($workflowDefinition, false);

        $this->assertEquals($attributes->toArray(), $workflow->getAttributeManager()->getAttributes()->toArray());
        $this->assertEquals($steps->toArray(), $workflow->getStepManager()->getSteps()->toArray());
        $this->assertEquals($transitions->toArray(), $workflow->getTransitionManager()->getTransitions()->toArray());
    }

    public function testAssembleNoStepsConfigurationException()
    {
        $configuration = [
            WorkflowConfiguration::NODE_STEPS => [],
            WorkflowConfiguration::NODE_TRANSITIONS => ['test_transition' => $this->transitionConfiguration],
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [$this->transitionDefinition]
        ];

        $this->expectException(AssemblerException::class);
        $this->expectExceptionMessage('Option "steps" is required');
        $this->workflowAssembler->assemble($this->createWorkflowDefinition($configuration));
    }

    public function testAssembleNoTransitionsConfigurationException()
    {
        $configuration = [
            WorkflowConfiguration::NODE_STEPS => ['step_one' => $this->stepConfiguration],
            WorkflowConfiguration::NODE_TRANSITIONS => [],
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [$this->transitionDefinition]
        ];

        $this->expectException(AssemblerException::class);
        $this->expectExceptionMessage('Option "transitions" is required');
        $this->workflowAssembler->assemble(
            $this->createWorkflowDefinition($configuration)
        );
    }

    private function createWorkflowDefinition(array $configuration): WorkflowDefinition
    {
        return (new WorkflowDefinition())
            ->setName($this->workflowParameters['name'])
            ->setLabel($this->workflowParameters['label'])
            ->setConfiguration($configuration);
    }

    private function expectsAttributeAssembleCalls(
        WorkflowDefinition $workflowDefinition,
        array $configuration
    ): ArrayCollection {
        $attributes = new ArrayCollection(['test' => $this->getAttributeMock('test')]);

        $this->attributeAssembler->expects($this->once())
            ->method('assemble')
            ->with($workflowDefinition, $configuration[WorkflowConfiguration::NODE_ATTRIBUTES] ?? [])
            ->willReturn($attributes);

        return $attributes;
    }

    /**
     * @param array $configuration
     * @param Collection $attributes
     * @return ArrayCollection
     */
    private function expectStepAssemblerCalls(array $configuration, $attributes): ArrayCollection
    {
        $step = $this->createMock(Step::class);
        $step->expects($this->any())
            ->method('getName')
            ->willReturn('test_start_step');

        $steps = new ArrayCollection(['test_start_step' => $step]);
        $this->stepAssembler->expects($this->once())
            ->method('assemble')
            ->with($configuration[WorkflowConfiguration::NODE_STEPS], $attributes)
            ->willReturn($steps);

        return $steps;
    }

    private function expectTranslatorCalls(): void
    {
        $this->translator->expects($this->any())->method('trans')
            ->with($this->isType('string'), $this->isType('array'))
            ->willReturnCallback(
                function ($id, array $parameters = []) {
                    if ($id === 'oro.workflow.transition.start') {
                        $this->assertArrayHasKey('%workflow%', $parameters);
                        return $this->getStartTransitionLabel($parameters['%workflow%']);
                    }

                    return $id;
                }
            );
    }

    /**
     * @param string $workflowLabel
     * @return string
     */
    private function getStartTransitionLabel($workflowLabel)
    {
        return 'Start ' . $workflowLabel;
    }

    /**
     * @param string $isStart
     * @param string $name
     * @return \PHPUnit\Framework\MockObject\MockObject|Transition
     */
    private function getTransitionMock($isStart, $name): Transition
    {
        $transition = $this->createMock('Oro\Bundle\WorkflowBundle\Model\Transition');
        $transition->expects($this->any())
            ->method('isStart')
            ->willReturn($isStart);
        $transition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $transition;
    }

    /**
     * @param string $name
     * @return Attribute|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getAttributeMock($name): Attribute
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $attributeMock;
    }

    /**
     * @param array $configuration
     * @param Collection $steps
     * @param array $transitions
     * @return ArrayCollection
     */
    private function expectTransitionAssemblerCalls(array $configuration, $steps, $transitions = []): ArrayCollection
    {
        $transitions = new ArrayCollection(
            $transitions
                ?: ['test_transition' => $this->getTransitionMock(false, 'test_transition')]
        );

        $this->transitionAssembler->expects($this->once())
            ->method('assemble')
            ->with($configuration, $steps)
            ->willReturn($transitions);

        return $transitions;
    }
}
