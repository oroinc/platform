<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class WorkflowDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowDefinition
     */
    protected $workflowDefinition;

    protected function setUp()
    {
        $this->workflowDefinition = new WorkflowDefinition();
    }

    protected function tearDown()
    {
        unset($this->workflowDefinition);
    }

    public function testName()
    {
        $this->assertNull($this->workflowDefinition->getName());
        $value = 'example_workflow';
        $this->workflowDefinition->setName($value);
        $this->assertEquals($value, $this->workflowDefinition->getName());
    }

    public function testLabel()
    {
        $this->assertNull($this->workflowDefinition->getLabel());
        $value = 'Example Workflow';
        $this->workflowDefinition->setLabel($value);
        $this->assertEquals($value, $this->workflowDefinition->getLabel());
    }

    public function testEnabled()
    {
        $this->assertFalse($this->workflowDefinition->isEnabled());
        $this->workflowDefinition->setEnabled(true);
        $this->assertTrue($this->workflowDefinition->isEnabled());
    }

    public function testStartStep()
    {
        $this->assertNull($this->workflowDefinition->getStartStep());
        $startStep = new WorkflowStep();
        $startStep->setName('start_step');
        $this->workflowDefinition->setSteps(array($startStep));
        $this->workflowDefinition->setStartStep($startStep);
        $this->assertEquals($startStep, $this->workflowDefinition->getStartStep());
    }

    public function testConfiguration()
    {
        $this->assertEmpty($this->workflowDefinition->getConfiguration());
        $value = array('some', 'configuration', 'array');
        $this->workflowDefinition->setConfiguration($value);
        $this->assertEquals($value, $this->workflowDefinition->getConfiguration());
    }

    public function testImport()
    {
        $startStep = new WorkflowStep();
        $startStep->setName('start');
        $expectedData = array(
            'name' => 'test_name',
            'label' => 'test_label',
            'enabled' => false,
            'steps' => new ArrayCollection(array($startStep)),
            'start_step' => $startStep,
            'configuration' => array('test', 'configuration'),
        );

        $this->assertNotEquals($expectedData, $this->getDefinitionAsArray($this->workflowDefinition));

        $newDefinition = new WorkflowDefinition();
        $newDefinition->setName($expectedData['name'])
            ->setSteps($expectedData['steps'])
            ->setLabel($expectedData['label'])
            ->setStartStep($expectedData['start_step'])
            ->setConfiguration($expectedData['configuration']);

        $this->workflowDefinition->import($newDefinition);
        $this->assertEquals($expectedData, $this->getDefinitionAsArray($this->workflowDefinition));
    }

    /**
     * @param WorkflowDefinition $definition
     * @return array
     */
    protected function getDefinitionAsArray(WorkflowDefinition $definition)
    {
        return array(
            'name' => $definition->getName(),
            'label' => $definition->getLabel(),
            'enabled' => $definition->isEnabled(),
            'steps' => $definition->getSteps(),
            'start_step' => $definition->getStartStep(),
            'configuration' => $definition->getConfiguration(),
        );
    }
}
