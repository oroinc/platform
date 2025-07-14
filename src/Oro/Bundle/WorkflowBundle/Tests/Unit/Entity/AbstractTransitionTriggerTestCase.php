<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractTransitionTriggerTestCase extends TestCase
{
    use EntityTestCaseTrait;

    protected BaseTransitionTrigger $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = $this->getEntity();
    }

    abstract protected function getEntity(): BaseTransitionTrigger;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors($this->entity, [
            ['id', 1],
            ['queued', false, true],
            ['workflowDefinition', new WorkflowDefinition()],
        ]);
    }

    public function testGetWorkflowName(): void
    {
        self::assertNull($this->entity->getWorkflowName());

        $definition = new WorkflowDefinition();
        $definition->setName('test name');

        $this->entity->setWorkflowDefinition($definition);

        self::assertEquals($definition->getName(), $this->entity->getWorkflowName());
    }

    protected function setDataToTrigger(BaseTransitionTrigger $trigger): BaseTransitionTrigger
    {
        return $trigger->setTransitionName('test_transition')
            ->setQueued(false)
            ->setWorkflowDefinition(new WorkflowDefinition());
    }

    protected function assertImportData(): void
    {
        $trigger = $this->getEntity();
        $this->setDataToTrigger($trigger);
        self::assertEquals($trigger->getTransitionName(), $this->entity->getTransitionName());
        self::assertEquals($trigger->getWorkflowDefinition(), $this->entity->getWorkflowDefinition());
        self::assertEquals($trigger->isQueued(), $this->entity->isQueued());
    }
}
