<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;

class TransitionIsAllowedTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transitionName = 'test_transition';

        $constraint = new TransitionIsAllowed($workflowItem, $transitionName);
        $this->assertEquals($workflowItem, $constraint->getWorkflowItem());
        $this->assertEquals($transitionName, $constraint->getTransitionName());
    }

    public function testGetTargets()
    {
        $constraint = new TransitionIsAllowed($this->createMock(WorkflowItem::class), 'test_transition');
        $this->assertEquals(TransitionIsAllowed::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
