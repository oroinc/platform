<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
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
            'entity' => $definition->getRelatedEntity(),
            'configuration' => $definition->getConfiguration(),
        );

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
    public function testBuildFromConfiguration(array $inputData, array $expectedData, array $expectedAcls = array())
    {
        $workflowConfiguration = current($inputData);

        $steps = array();
        if (!empty($workflowConfiguration[WorkflowConfiguration::NODE_STEPS])) {
            foreach ($workflowConfiguration[WorkflowConfiguration::NODE_STEPS] as $stepData) {
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
        if (!empty($workflowConfiguration['start_step'])) {
            $step = new Step();
            $step->setName($workflowConfiguration['start_step']);
            $steps[] = $step;
        }
        $stepManager = new StepManager($steps);

        $attributes = array();
        if (!empty($workflowConfiguration[WorkflowConfiguration::NODE_ATTRIBUTES])) {
            foreach ($workflowConfiguration[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeData) {
                $attribute = new Attribute();
                $attribute->setName($attributeData['name']);
                $attribute->setType($attributeData['type']);
                if (!empty($attributeData['entity_acl'])) {
                    $attribute->setEntityAcl($attributeData['entity_acl']);
                }
                $attributes[] = $attribute;
            }
        }
        $attributeManager = new AttributeManager($attributes);

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(array('getStepManager', 'getAttributeManager', 'getRestrictions'))
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));
        $workflow->expects($this->any())
            ->method('getAttributeManager')
            ->will($this->returnValue($attributeManager));
        $workflow->expects($this->any())
            ->method('getRestrictions')
            ->will($this->returnValue([]));
        
        $workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        $workflowAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'), false)
            ->will($this->returnValue($workflow));

        $builder = new WorkflowDefinitionConfigurationBuilder($workflowAssembler);
        $workflowDefinitions = $builder->buildFromConfiguration($inputData);
        $this->assertCount(1, $workflowDefinitions);

        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = current($workflowDefinitions);
        $this->assertEquals($expectedData, $this->getDataAsArray($workflowDefinition));

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
        $minimumConfiguration = array(
            'label'  => 'Test Workflow',
            'entity' => 'My\Entity',
        );

        $maximumConfiguration = array(
            'label' => 'Test Workflow',
            'is_system' => true,
            'entity' => 'My\Entity',
            'start_step' => 'test_step',
            'entity_attribute' => 'my_entity',
            'steps_display_ordered' => true,
            WorkflowConfiguration::NODE_STEPS => array(
                array(
                    'name' => 'first',
                    'entity_acl' => array(
                        'entity_attribute' => array(
                            'update' => false,
                        )
                    ),
                    'is_final' => true,
                ),
            ),
            WorkflowConfiguration::NODE_ATTRIBUTES => array(
                array(
                    'name' => 'string_attribute',
                    'type' => 'string',
                ),
                array(
                    'name' => 'entity_attribute',
                    'type' => 'entity',
                    'entity_acl' => array(
                        'delete' => false,
                    ),
                    'options' => array(
                        'class' => 'TestClass',
                    ),
                ),
            ),
        );

        return array(
            'minimum configuration' => array(
                'inputData' => array(
                    'test_workflow' => $minimumConfiguration,
                ),
                'expectedData' => array(
                    'name'  => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity'     => 'My\Entity',
                    'configuration' => $this->filterConfiguration($minimumConfiguration),
                ),
            ),
            'maximum configuration' => array(
                'inputData' => array(
                    'test_workflow' => $maximumConfiguration,
                ),
                'expectedData' => array(
                    'name'  => 'test_workflow',
                    'label' => 'Test Workflow',
                    'start_step' => 'test_step',
                    'entity' => 'My\Entity',
                    'configuration' => $this->filterConfiguration($maximumConfiguration),
                ),
                'expected_acls' => array(
                    array(
                        'step' => 'first',
                        'attribute' => 'entity_attribute',
                        'permissions' => array('UPDATE' => false, 'DELETE' => false),
                    ),
                    array(
                        'step' => 'test_step',
                        'attribute' => 'entity_attribute',
                        'permissions' => array('UPDATE' => true, 'DELETE' => false),
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function filterConfiguration(array $configuration)
    {
        $configurationKeys = array(
            WorkflowConfiguration::NODE_STEPS,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            WorkflowConfiguration::NODE_TRANSITIONS,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
        );

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
