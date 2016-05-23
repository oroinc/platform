<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $model;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $strategy;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();

        $this->strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $this->model = new Recurrence($this->validator, $this->strategy);
    }

    public function testValidateRecurrence()
    {
        $recurrence = new Entity\Recurrence();

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($recurrence);

        $this->model->validateRecurrence($recurrence);
    }
}
