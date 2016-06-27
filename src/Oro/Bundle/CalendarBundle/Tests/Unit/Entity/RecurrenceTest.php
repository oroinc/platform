<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;

class RecurrenceTest extends AbstractEntityTest
{
    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CalendarBundle\Entity\Recurrence';
    }

    /**
     * {@inheritDoc}
     */
    public function getSetDataProvider()
    {
        return [
            ['recurrence_type', 'daily', 'daily'],
            ['interval', 99, 99],
            ['instance', 3, 3],
            ['day_of_week', ['monday', 'wednesday'], ['monday', 'wednesday']],
            ['day_of_month', 28, 28],
            ['month_of_year', 8, 8],
            ['start_time', $start = new \DateTime(), $start],
            ['end_time', $end = new \DateTime(), $end],
            ['calculated_end_time', $cet = new \DateTime(), $cet],
            ['calendar_event', new CalendarEvent(), new CalendarEvent()],
            ['occurrences', 1, 1],
        ];
    }
}
