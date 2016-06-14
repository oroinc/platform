<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName;

class ScheduledTransitionProcessNameTest extends \PHPUnit_Framework_TestCase
{
    public function testUnfilledNameException()
    {
        $nameInstance = new ScheduledTransitionProcessName('', '');

        $this->setExpectedException(
            'UnderflowException',
            'Cannot build valid string representation of scheduled transition process name without all parts.'
        );
        $nameInstance->getName();
    }

    public function testGetName()
    {
        $workflowName = 'workflow1';
        $transitionName = 'transition1';
        
        $n = new ScheduledTransitionProcessName($workflowName, $transitionName);
        
        $this->assertEquals($workflowName, $n->getWorkflowName());
        $this->assertEquals($transitionName, $n->getTransitionName());
        
        $this->assertEquals('stpn__workflow1__transition1', $n->getName());
    }
}
