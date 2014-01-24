<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;

class WorkflowDefinitionConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param WorkflowDefinition $definition
     * @return array
     */
    protected function getDataAsArray(WorkflowDefinition $definition)
    {
        $data = array(
            'name' => $definition->getName(),
            'label' => $definition->getLabel(),
            'enabled' => $definition->isEnabled(),
            'entity' => $definition->getRelatedEntity(),
            'configuration' => $definition->getConfiguration(),
        );

        if ($definition->getStartStep()) {
            $data['start_step'] = $definition->getStartStep()->getName();
        }

        return $data;
    }

    /**
     * @param array $expectedData
     * @param array $inputData
     * @dataProvider buildFromConfigurationDataProvider
     */
    public function testBuildFromConfiguration(array $expectedData, array $inputData)
    {
        $stepManager = new StepManager();

        $workflowConfiguration = current($inputData);
        if (!empty($workflowConfiguration['start_step'])) {
            $step = new Step();
            $step->setName($workflowConfiguration['start_step']);
            $stepManager->setSteps(array($step));
        }

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(array('getStepManager'))
            ->getMock();
        $workflow->expects($this->once())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        $workflowAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'))
            ->will($this->returnValue($workflow));

        $builder = new WorkflowDefinitionConfigurationBuilder($workflowAssembler);
        $workflowDefinitions = $builder->buildFromConfiguration($inputData);
        $this->assertCount(1, $workflowDefinitions);

        $workflowDefinition = current($workflowDefinitions);
        $this->assertEquals($expectedData, $this->getDataAsArray($workflowDefinition));
    }

    /**
     * @return array
     */
    public function buildFromConfigurationDataProvider()
    {
        $minimumConfiguration = array(
            'label'  => 'Test Workflow',
            'entity' => 'My\Entity',
        );

        $maximumConfiguration = array(
            'label' => 'Test Workflow',
            'enabled' => false,
            'entity' => 'My\Entity',
            'start_step' => 'test_step',
            'entity_attribute' => 'my_entity',
            'steps_display_ordered' => true,
            WorkflowConfiguration::NODE_ATTRIBUTES => array(
                array(
                    'name' => 'string_attribute',
                    'type' => 'string',
                ),
                array(
                    'name' => 'entity_attribute',
                    'type' => 'entity',
                    'options' => array(
                        'class' => 'TestClass',
                    ),
                ),
            )
        );

        return array(
            'minimum configuration' => array(
                'expectedData' => array(
                    'name'  => 'test_workflow',
                    'label' => 'Test Workflow',
                    'enabled' => true,
                    'entity'     => 'My\Entity',
                    'configuration' => $minimumConfiguration,
                ),
                'inputData' => array(
                    'test_workflow' => $minimumConfiguration,
                ),
            ),
            'maximum configuration' => array(
                'expectedData' => array(
                    'name'  => 'test_workflow',
                    'label' => 'Test Workflow',
                    'enabled' => false,
                    'start_step' => 'test_step',
                    'entity' => 'My\Entity',
                    'configuration' => $maximumConfiguration,
                ),
                'inputData' => array(
                    'test_workflow' => $maximumConfiguration,
                ),
            ),
        );
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

        $workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new WorkflowDefinitionConfigurationBuilder($workflowAssembler);
        $builder->buildFromConfiguration($inputData);
    }

    /**
     * @return array
     */
    public function buildFromConfigurationExceptionDataProvider()
    {
        return array(
            'no label' => array(
                'expectedException' => '\Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException',
                'expectedMessage' => 'Configuration option "label" is required',
                'inputData' => array(
                    'test_workflow' => array(),
                ),
            ),
            'no entity' => array(
                'expectedException' => '\Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException',
                'expectedMessage' => 'Configuration option "entity" is required',
                'inputData' => array(
                    'test_workflow' => array(
                        'label' => 'My Entity'
                    ),
                ),
            ),
        );
    }
}
