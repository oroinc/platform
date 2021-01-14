<?php

namespace Oro\Bundle\ReportBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;

/**
 * Removes existing duplicated calendar dates
 */
class RemoveDuplicatedCalendarDates extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $calendarDates = $manager->getRepository(CalendarDate::class)->findAll();
        $uniqueDates = [];

        foreach ($calendarDates as $calendarDate) {
            if (in_array($calendarDate->getDate()->getTimestamp(), $uniqueDates, true)) {
                $manager->remove($calendarDate);
                continue;
            }
            $uniqueDates[] = $calendarDate->getDate()->getTimestamp();
        }

        $manager->flush();
    }
}
