<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;

class TriggersBagTest extends \PHPUnit_Framework_TestCase
{
    public function testBag()
    {
        $definition = new WorkflowDefinition();
        $triggers = [new TransitionTriggerEvent(), new TransitionTriggerCron()];

        $bag = new TriggersBag($definition, $triggers);

        $this->assertSame($definition, $bag->getDefinition());
        $this->assertEquals($triggers, $bag->getTriggers());
    }
}
