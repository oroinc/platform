<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 07.06.2016
 * Time: 16:26
 */

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName;

class ScheduledTransitionProcessNameTest extends \PHPUnit_Framework_TestCase
{
    public function testRestore()
    {
        $name = 'stpn__a_w__e-w';
        $result = ScheduledTransitionProcessName::restore($name);

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName',
            $result
        );

        $this->assertEquals($name, $result->getName());
        $this->assertEquals('a_w', $result->getWorkflow());
        $this->assertEquals('e-w', $result->getTransition());
    }

    public function testRestoreFailureException()
    {
        $name = 'stpn_a_w_e-w';
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not restore name object. Provided name `stpn_a' .
            '_w_e-w` is not valid `Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName`' .
            ' representation.'
        );
        ScheduledTransitionProcessName::restore($name);
    }

    public function testUnfilledNameException()
    {
        $nameInstance = new ScheduledTransitionProcessName('', '');

        $this->setExpectedException(
            'UnderflowException',
            'Cannot build valid string representation of scheduled transition process name without all parts.'
        );
        $nameInstance->getName();
    }
}
