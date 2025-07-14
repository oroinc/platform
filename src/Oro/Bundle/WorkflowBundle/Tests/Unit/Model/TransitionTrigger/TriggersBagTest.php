<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;
use PHPUnit\Framework\TestCase;

class TriggersBagTest extends TestCase
{
    public function testBag(): void
    {
        $definition = new WorkflowDefinition();
        $triggers = [new TransitionEventTrigger(), new TransitionCronTrigger()];

        $bag = new TriggersBag($definition, $triggers);

        $this->assertSame($definition, $bag->getDefinition());
        $this->assertEquals($triggers, $bag->getTriggers());
    }
}
