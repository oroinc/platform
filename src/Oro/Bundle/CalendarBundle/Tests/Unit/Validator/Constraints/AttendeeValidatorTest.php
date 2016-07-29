<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Validator\Constraints\AttendeeValidator;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Attendee;

class AttendeeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExecutionContextInterface */
    protected $context;

    /** @var AttendeeValidator */
    protected $validator;

    public function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new AttendeeValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValues($value)
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($value, new Attendee());
    }

    public function validValuesProvider()
    {
        return [
            [
                (new AttendeeEntity())
                    ->setEmail('email@example.com')
            ],
            [
                (new AttendeeEntity())
                    ->setDisplayName('name')
            ],
            [
                (new AttendeeEntity())
                    ->setDisplayName('name')
                    ->setEmail('email@example.com')
            ],
        ];
    }

    /**
     * @dataProvider testInvalidValuesProvider
     */
    public function testInvalidValues($value)
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Email or display name have to be specified.');

        $this->validator->validate($value, new Attendee());
    }

    public function testInvalidValuesProvider()
    {
        return [
            [(new AttendeeEntity())]
        ];
    }
}
