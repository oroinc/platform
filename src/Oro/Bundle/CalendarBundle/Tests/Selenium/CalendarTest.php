<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class CalendarTest
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium
 */
class CalendarTest extends Selenium2TestCase
{
    /**
     * Test creation of new calendar event
     * @return string
     */
    public function testAddEvent()
    {
        $eventName = 'Event_'.mt_rand();
        $login = $this->login();
        $login->openCalendar('Oro\Bundle\CalendarBundle')
            ->addEvent()
            ->setTitle($eventName)
            ->saveEvent()
            ->checkEventPresent($eventName);

        return $eventName;
    }

    /**
     * Test edit of existing event
     * @depends testAddEvent
     * @param string $eventName
     * @return string
     */
    public function testEditEvent($eventName)
    {
        $newEventTitle = 'Update_' . $eventName;
        $login = $this->login();
        $login->openCalendar('Oro\Bundle\CalendarBundle')
            ->editEvent($eventName)
            ->setTitle($newEventTitle)
            ->saveEvent()
            ->checkEventPresent($newEventTitle);

        return $newEventTitle;
    }

    /**
     * Test deletion of existing event
     * @depends testEditEvent
     * @param string $eventName
     */
    public function testDeleteEvent($eventName)
    {
        $login = $this->login();
        $login->openCalendar('Oro\Bundle\CalendarBundle')
            ->editEvent($eventName)
            ->deleteEvent()
            ->checkEventNotPresent($eventName);
    }
}
