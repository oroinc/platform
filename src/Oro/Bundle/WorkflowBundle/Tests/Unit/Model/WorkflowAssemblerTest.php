<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Model\AttributeAssembler;
use Oro\Bundle\WorkflowBundle\Model\StepAssembler;
use Oro\Bundle\WorkflowBundle\Model\TransitionAssembler;

class WorkflowAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $workflowParameters = array(
        'name' => 'test_name',
        'label' => 'Test Label'
    );

    protected $stepConfiguration = array(
        'label' => 'Test',
        'name' => 'test'
    );

    protected $transitionConfiguration = array(
        'label' => 'Test',
        'step_to' => 'test_step',
        'transition_definition' => 'test_transition_definition'
    );

    protected $transitionDefinition = array(
        'test_transition_definition' => array()
    );

    /**
     * @return Workflow
     */
    protected function createWorkflow()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        return new Workflow($doctrineHelper, $aclManager, $restrictionManager);
    }

    /**
     * @param array $configuration
     * @return WorkflowDefinition
     */
    protected function createWorkflowDefinition(array $configuration)
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition
            ->setName($this->workflowParameters['name'])
            ->setLabel($this->workflowParameters['label'])
            ->setConfiguration($configuration);

        return $workflowDefinition;
    }

    /**
     * @param Workflow $workflow
     * @param boolean $expectations
     * @return ContainerInterface
     */
    protected function createContainerMock(Workflow $workflow, $expectations = true)
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMockForAbstractClass();
        if ($expectations) {
            $container->expects($this->once())
                ->method('get')
                ->with('oro_workflow.prototype.workflow')
                ->will($this->returnValue($workflow));
        }

        return $container;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array $configuration
     * @param Collection $attributes
     * @param boolean $expectations
     * @return AttributeAssembler
     */
    protected function createAttributeAssemblerMock(
        WorkflowDefinition $definition,
        array $configuration,
        $attributes,
        $expectations = true
    ) {
        $attributeAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\AttributeAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        if ($expectations) {
            $expectedAttributeConfiguration = !empty($configuration[WorkflowConfiguration::NODE_ATTRIBUTES])
                ? $configuration[WorkflowConfiguration::NODE_ATTRIBUTES]
                : array();
            $attributeAssembler->expects($this->once())
                ->method('assemble')
                ->with($definition, $expectedAttributeConfiguration)
                ->will($this->returnValue($attributes));
        }

        return $attributeAssembler;
    }

    /**
     * @param array $configuration
     * @param Collection $attributes
     * @param Collection $steps
     * @param boolean $expectations
     * @return StepAssembler
     */
    protected function createStepAssemblerMock(
        array $configuration,
        $attributes,
        $steps,
        $expectations = true
    ) {
        $stepAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        if ($expectations) {
            $stepAssembler->expects($this->once())
                ->method('assemble')
                ->with($configuration[WorkflowConfiguration::NODE_STEPS], $attributes)
                ->will($this->returnValue($steps));
        }

        return $stepAssembler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTranslatorMock()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())->method('trans')
            ->with($this->isType('string'), $this->isType('array'))
            ->will(
                $this->returnCallback(
                    function ($id, array $parameters = array()) {
                        $this->assertEquals('oro.workflow.transition.start', $id);
                        $this->assertArrayHasKey('%workflow%', $parameters);
                        return $this->getStartTransitionLabel($parameters['%workflow%']);
                    }
                )
            );

        return $translator;
    }

    protected function getStartTransitionLabel($workflowLabel)
    {
        return 'Start ' . $workflowLabel;
    }

    protected function getStepMock($name)
    {
        $step = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->disableOriginalConstructor()
            ->getMock();
        $step->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        return $step;
    }

    protected function getTransitionMock($isStart, $name)
    {
        $transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $transition->expects($this->any())
            ->method('isStart')
            ->will($this->returnValue($isStart));
        $transition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        return $transition;
    }

    protected function getAttributeMock($name)
    {
        $attributeMock = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $attributeMock;
    }

    /**
     * @param array $configuration
     * @param Collection $steps
     * @param Collection $transitions
     * @param boolean $expectations
     * @param null|array $expectedTransitions
     * @param null|array $expectedDefinitions
     * @internal param null|string $startStepName
     * @return TransitionAssembler
     */
    protected function createTransitionAssemblerMock(
        array $configuration,
        $steps,
        $transitions,
        $expectations = true,
        $expectedTransitions = null,
        $expectedDefinitions = null
    ) {
        $transitionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        if ($expectations) {
            $expectedTransitions = $expectedTransitions ?
                $expectedTransitions :
                $configuration[WorkflowConfiguration::NODE_TRANSITIONS];
            $expectedDefinitions = $expectedDefinitions ?
                $expectedDefinitions :
                $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS];
            $transitionAssembler->expects($this->once())
                ->method('assemble')
                ->with($expectedTransitions, $expectedDefinitions, $steps)
                ->will($this->returnValue($transitions));
        }

        return $transitionAssembler;
    }

    /**
     * @param array $configuration
     * @param WorkflowStep $startStep
     * @param array $expectedTransitions
     * @param array $expectedDefinitions
     * @dataProvider assembleDataProvider
     */
    public function testAssemble(array $configuration, $startStep, $expectedTransitions, $expectedDefinitions)
    {
        // source data
        $workflow = $this->createWorkflow();
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        if ($startStep) {
            $workflowDefinition->addStep($startStep);
        }
        $attributes = new ArrayCollection(array('test' => $this->getAttributeMock('test')));
        $steps = new ArrayCollection(array('test_start_step' => $this->getStepMock('test_start_step')));

        $transitions = array('test_transition' => $this->getTransitionMock(false, 'test_transition'));
        if (!$startStep) {
            $transitions['test_start_transition'] = $this->getTransitionMock(true, 'test_start_transition');
        } else {
            $transitions['__start__'] = $this->getTransitionMock(true, '__start__');
            $workflowDefinition->setStartStep($startStep);
        }
        $transitions = new ArrayCollection($transitions);

        // mocks
        $container = $this->createContainerMock($workflow);
        $attributeAssembler = $this->createAttributeAssemblerMock($workflowDefinition, $configuration, $attributes);
        $stepAssembler = $this->createStepAssemblerMock($configuration, $attributes, $steps);
        $transitionAssembler = $this->createTransitionAssemblerMock(
            $configuration,
            $steps,
            $transitions,
            true,
            $expectedTransitions,
            $expectedDefinitions
        );
        $translator = $this->createTranslatorMock();
        $restrictionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\RestrictionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        // test
        $workflowAssembler = new WorkflowAssembler(
            $container,
            $attributeAssembler,
            $stepAssembler,
            $transitionAssembler,
            $restrictionAssembler,
            $translator
        );
        $actualWorkflow = $workflowAssembler->assemble($workflowDefinition);

        $this->assertEquals($workflow, $actualWorkflow);
        $this->assertEquals($workflowDefinition->getName(), $actualWorkflow->getName());
        $this->assertEquals($workflowDefinition->getLabel(), $actualWorkflow->getLabel());
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

        return array(
            'full configuration with start' => array(
                'configuration' => $fullConfig,
                'startStep' => $this->getStepEntity('test_start_step'),
                'expectedTransitions' => $transitions + $getDefaultTransition('test_start_step'),
                'expectedDefinitions' => $this->transitionDefinition + array('__start___definition' => array())
            ),
            'minimal configuration with start' => array(
                'configuration' => $minimalConfig,
                'startStep' => $this->getStepEntity('test_start_step'),
                'expectedTransitions' => $transitions + $getDefaultTransition('test_start_step'),
                'expectedDefinitions' => $this->transitionDefinition + array('__start___definition' => array())
            ),
            'full configuration without start' => array(
                'configuration' => $fullConfig,
                'startStep' => null,
                'expectedTransitions' => $transitions,
                'expectedDefinitions' => array()
            ),
            'minimal configuration without start' => array(
                'configuration' => $minimalConfig,
                'startStep' => null,
                'expectedTransitions' => $transitions,
                'expectedDefinitions' => array()
            ),
            'full configuration with start custom config' => array(
                'configuration' => $fullConfigWithCustomStart,
                'startStep' => $this->getStepEntity('test_start_step'),
                'expectedTransitions' => $transitions + $customStartTransition,
                'expectedDefinitions' => $this->transitionDefinition + $customStartDefinition
            ),
        );
    }

    protected function getStepEntity($name)
    {
        $step = new WorkflowStep();
        $step->setName($name);

        return $step;
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @expectedExceptionMessage Workflow "test_name" does not contains neither start step nor start transitions
     */
    public function testAssembleStartTransitionException()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_ATTRIBUTES => array('attributes_configuration'),
            WorkflowConfiguration::NODE_STEPS => array('test_step' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => $this->transitionConfiguration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => $this->transitionDefinition
        );

        // source data
        $workflow = $this->createWorkflow();
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        $attributes = new ArrayCollection(array('test' => $this->getAttributeMock('test')));
        $steps = new ArrayCollection(array('test_start_step' => $this->getStepMock('test_start_step')));

        $transitions = array('test_transition' => $this->getTransitionMock(false, 'test_transition'));

        // mocks
        $container = $this->createContainerMock($workflow);
        $attributeAssembler = $this->createAttributeAssemblerMock($workflowDefinition, $configuration, $attributes);
        $stepAssembler = $this->createStepAssemblerMock($configuration, $attributes, $steps);
        $transitionAssembler = $this->createTransitionAssemblerMock($configuration, $steps, $transitions);
        $translator = $this->createTranslatorMock();
        $restrictionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\RestrictionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        // test
        $workflowAssembler = new WorkflowAssembler(
            $container,
            $attributeAssembler,
            $stepAssembler,
            $transitionAssembler,
            $restrictionAssembler,
            $translator
        );
        $workflowAssembler->assemble($workflowDefinition);
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
        $workflow = $this->createWorkflow();
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        $attributes = new ArrayCollection(array('test' => $this->getAttributeMock('test')));
        $steps = new ArrayCollection(array('test_start_step' => $this->getStepMock('test_start_step')));
        $transitions = new ArrayCollection(
            array('test_transition' => $this->getTransitionMock(false, 'test_transition'))
        );

        // mocks
        $container = $this->createContainerMock($workflow);
        $attributeAssembler = $this->createAttributeAssemblerMock($workflowDefinition, $configuration, $attributes);
        $stepAssembler = $this->createStepAssemblerMock($configuration, $attributes, $steps);
        $transitionAssembler = $this->createTransitionAssemblerMock($configuration, $steps, $transitions);
        $translator = $this->createTranslatorMock();
        $restrictionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\RestrictionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        // test
        $workflowAssembler = new WorkflowAssembler(
            $container,
            $attributeAssembler,
            $stepAssembler,
            $transitionAssembler,
            $restrictionAssembler,
            $translator
        );
        $workflow = $workflowAssembler->assemble($workflowDefinition, false);

        $this->assertEquals($attributes->toArray(), $workflow->getAttributeManager()->getAttributes()->toArray());
        $this->assertEquals($steps->toArray(), $workflow->getStepManager()->getSteps()->toArray());
        $this->assertEquals($transitions->toArray(), $workflow->getTransitionManager()->getTransitions()->toArray());
    }

    /**
     * @param array $configuration
     */
    protected function assembleWorkflow(array $configuration)
    {
        $workflow = $this->createWorkflow();
        $workflowDefinition = $this->createWorkflowDefinition($configuration);
        $attributes = new ArrayCollection();
        $steps = new ArrayCollection();
        $transitions = new ArrayCollection();

        $container = $this->createContainerMock($workflow, false);
        $attributeAssembler = $this
            ->createAttributeAssemblerMock($workflowDefinition, $configuration, $attributes, false);
        $stepAssembler = $this->createStepAssemblerMock($configuration, $attributes, $steps, false);
        $transitionAssembler = $this->createTransitionAssemblerMock($configuration, $steps, $transitions, false);
        $translator = $this->createTranslatorMock();
        $restrictionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\RestrictionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowAssembler = new WorkflowAssembler(
            $container,
            $attributeAssembler,
            $stepAssembler,
            $transitionAssembler,
            $restrictionAssembler,
            $translator
        );
        $workflowAssembler->assemble($workflowDefinition);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @expectedExceptionMessage Option "steps" is required
     */
    public function testAssembleNoStepsConfigurationException()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_STEPS => array(),
            WorkflowConfiguration::NODE_TRANSITIONS => array('test_transition' => $this->transitionConfiguration),
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array($this->transitionDefinition)
        );
        $this->assembleWorkflow($configuration);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @expectedExceptionMessage Option "transitions" is required
     */
    public function testAssembleNoTransitionsConfigurationException()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_STEPS => array('step_one' => $this->stepConfiguration),
            WorkflowConfiguration::NODE_TRANSITIONS => array(),
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array($this->transitionDefinition)
        );
        $this->assembleWorkflow($configuration);
    }
}
