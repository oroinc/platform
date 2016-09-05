<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DelegateStrategy;

class DelegateStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var DelegateStrategy $strategy */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new DelegateStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_delegate');
    }

    public function testAdd()
    {
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $this->assertAttributeEquals(['bar' => $bar,'foo' => $foo], 'elements', $this->strategy);
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $this->assertFalse($this->strategy->supports($recurrence));

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));

        $this->assertTrue($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $now = new \DateTime();
        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));
        $bar->expects($this->once())
            ->method('getOccurrences')
            ->willReturn($now);

        $this->assertEquals($now, $this->strategy->getOccurrences($recurrence, new \DateTime(), new \DateTime()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetOccurrencesForNotExistingStrategy()
    {
        $this->strategy->getOccurrences(new Recurrence(), new \DateTime(), new \DateTime());
    }

    public function testGetTextValue()
    {
        $recurrence = new Recurrence();
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));
        $bar->expects($this->once())
            ->method('getTextValue')
            ->willReturn('val');

        $this->assertEquals('val', $this->strategy->getTextValue($recurrence));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetTextValueForNonExistingStrategy()
    {
        $this->strategy->getTextValue(new Recurrence());
    }

    public function testGetCalculatedEndTime()
    {
        $recurrence = new Recurrence();
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $endTime = new \DateTime();
        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));
        $bar->expects($this->once())
            ->method('getCalculatedEndTime')
            ->willReturn($endTime);

        $this->assertEquals($endTime, $this->strategy->getCalculatedEndTime($recurrence));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetCalculatedEndTimeForNonExistingStrategy()
    {
        $this->strategy->getCalculatedEndTime(new Recurrence());
    }

    public function testGetValidationErrorMessage()
    {
        $recurrence = new Recurrence();
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));
        $bar->expects($this->once())
            ->method('getValidationErrorMessage')
            ->willReturn('val');

        $this->assertEquals('val', $this->strategy->getValidationErrorMessage($recurrence));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetValidationErrorMessageForNonExistingStrategy()
    {
        $this->strategy->getValidationErrorMessage(new Recurrence());
    }

    /**
     * Creates mock object for StrategyInterface.
     *
     * @param string $name
     *
     * @return \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createStrategy($name)
    {
        $result = $this->getMock('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface');
        $result->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $result;
    }
}
