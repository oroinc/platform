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
        $result = ScheduledTransitionProcessName::createFromName($name);

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName',
            $result
        );

        $this->assertEquals($name, $result->getName());
        $this->assertEquals('a_w', $result->getWorkflowName());
        $this->assertEquals('e-w', $result->getTransitionName());
    }

    /**
     * @dataProvider restoreExceptionCases
     * @param string $name
     */
    public function testRestoreFailureException($name)
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not restore name object. ' .
            sprintf('Provided name `%s` is not valid ', $name) .
            '`Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName` representation.'
        );
        ScheduledTransitionProcessName::createFromName($name);
    }

    public function restoreExceptionCases(){
        return [
            'wrong prefix'=>['asd - ewe qeq'],
            'wrong delimiters - prefix' => ['stpn_qwe_e_q'],
            'wrong delimiter1' => ['stpn_e__q'],
            'wrong delimiter2' => ['stpn__e_q'],
            'wrong workflow name' => ['stpn__0__q'],
            'wrong transition name' => ['stpn__e__0'],
        ];
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
