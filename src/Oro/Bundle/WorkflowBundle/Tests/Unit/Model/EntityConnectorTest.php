<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityWithWorkflow;

class EntityConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    protected function setUp()
    {
        $this->entityConnector = new EntityConnector();
    }

    protected function tearDown()
    {
        unset($this->entityConnector);
    }

    public function testSetWorkflowItem()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($entity->getWorkflowItem());

        $workflowItem = new WorkflowItem();
        $this->entityConnector->setWorkflowItem($entity, $workflowItem);
        $this->assertEquals($workflowItem, $entity->getWorkflowItem());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't set property "workflowItem" to entity
     */
    public function testSetWorkflowItemException()
    {
        $this->entityConnector->setWorkflowItem(new \DateTime(), new WorkflowItem());
    }

    public function testSetWorkflowStep()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($entity->getWorkflowItem());

        $workflowStep = new WorkflowStep();
        $this->entityConnector->setWorkflowStep($entity, $workflowStep);
        $this->assertEquals($workflowStep, $entity->getWorkflowStep());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't set property "workflowStep" to entity
     */
    public function testSetWorkflowStepException()
    {
        $this->entityConnector->setWorkflowStep(new \DateTime(), new WorkflowStep());
    }

    public function testGetWorkflowItem()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($this->entityConnector->getWorkflowItem($entity));

        $workflowItem = new WorkflowItem();
        $entity->setWorkflowItem($workflowItem);
        $this->assertEquals($workflowItem, $this->entityConnector->getWorkflowItem($entity));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get property "workflowItem" from entity
     */
    public function testGetWorkflowItemException()
    {
        $this->entityConnector->getWorkflowItem(new \DateTime());
    }

    public function testGetWorkflowStep()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($this->entityConnector->getWorkflowStep($entity));

        $workflowStep = new WorkflowStep();
        $entity->setWorkflowStep($workflowStep);
        $this->assertEquals($workflowStep, $this->entityConnector->getWorkflowStep($entity));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get property "workflowStep" from entity
     */
    public function testGetWorkflowStepException()
    {
        $this->entityConnector->getWorkflowStep(new \DateTime());
    }
}
