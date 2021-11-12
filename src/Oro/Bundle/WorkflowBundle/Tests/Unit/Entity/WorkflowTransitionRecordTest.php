<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Component\Testing\ReflectionUtil;

class WorkflowTransitionRecordTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowTransitionRecord */
    private $transitionRecord;

    protected function setUp(): void
    {
        $this->transitionRecord = new WorkflowTransitionRecord();
    }

    public function testGetId()
    {
        $this->assertNull($this->transitionRecord->getId());

        $value = 42;
        ReflectionUtil::setId($this->transitionRecord, $value);
        $this->assertSame($value, $this->transitionRecord->getId());
    }

    public function testGetSetWorkflowItem()
    {
        $this->assertNull($this->transitionRecord->getWorkflowItem());

        $value = new WorkflowItem();
        $this->assertEquals($this->transitionRecord, $this->transitionRecord->setWorkflowItem($value));
        $this->assertEquals($value, $this->transitionRecord->getWorkflowItem());
    }

    public function testGetSetTransitionName()
    {
        $this->assertNull($this->transitionRecord->getTransitionName());

        $value = 'transition_name';
        $this->assertEquals($this->transitionRecord, $this->transitionRecord->setTransitionName($value));
        $this->assertEquals($value, $this->transitionRecord->getTransitionName());
    }

    public function testGetSetStepFromName()
    {
        $this->assertNull($this->transitionRecord->getStepFrom());

        $value = $this->createMock(WorkflowStep::class);
        $this->assertEquals($this->transitionRecord, $this->transitionRecord->setStepFrom($value));
        $this->assertEquals($value, $this->transitionRecord->getStepFrom());
    }

    public function testGetSetStepToName()
    {
        $this->assertNull($this->transitionRecord->getStepTo());

        $value = $this->createMock(WorkflowStep::class);
        $this->assertEquals($this->transitionRecord, $this->transitionRecord->setStepTo($value));
        $this->assertEquals($value, $this->transitionRecord->getStepTo());
    }

    public function testGetTransitionDateAndPrePersist()
    {
        $this->assertNull($this->transitionRecord->getTransitionDate());
        $this->transitionRecord->prePersist();
        $this->assertInstanceOf(\DateTime::class, $this->transitionRecord->getTransitionDate());
        $this->assertEqualsWithDelta(time(), $this->transitionRecord->getTransitionDate()->getTimestamp(), 5);
    }
}
