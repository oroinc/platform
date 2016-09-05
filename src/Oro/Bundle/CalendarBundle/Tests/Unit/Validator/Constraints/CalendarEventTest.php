<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Oro\Bundle\CalendarBundle\Validator\Constraints\CalendarEvent;

class CalendarEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEvent */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new CalendarEvent();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_calendar.calendar_event_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
