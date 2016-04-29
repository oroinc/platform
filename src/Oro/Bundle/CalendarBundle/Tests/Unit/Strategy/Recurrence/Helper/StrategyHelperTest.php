<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence\Helper;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;

class StrategyHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var StrategyHelper */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new StrategyHelper();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Interval should be an integer with min_rage >= 1.
     */
    public function testValidateRecurrenceWithWrongInterval()
    {
        $recurrence = new Recurrence();
        $recurrence->setInterval('-1.5');
        $this->helper->validateRecurrence($recurrence);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage StartTime should be an instance of \DateTime
     */
    public function testValidateRecurrenceWithWrongStartTime()
    {
        $recurrence = new Recurrence();
        $recurrence->setInterval(1);
        $this->helper->validateRecurrence($recurrence);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage EndTime should be an instance of \DateTime
     */
    public function testValidateRecurrenceWithWrongEndTime()
    {
        $recurrence = new Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setStartTime(new \DateTime());
        $this->helper->validateRecurrence($recurrence);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown instance
     */
    public function testValidateRecurrenceWithUnknownInstance()
    {
        $recurrence = new Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setStartTime(new \DateTime());
        $recurrence->setEndTime(new \DateTime());
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH);
        $recurrence->setInstance(999);
        $this->helper->validateRecurrence($recurrence);
    }
}
