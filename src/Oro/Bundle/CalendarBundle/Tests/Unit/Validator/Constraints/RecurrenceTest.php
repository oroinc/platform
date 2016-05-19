<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new Recurrence();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_calendar.recurrence_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
