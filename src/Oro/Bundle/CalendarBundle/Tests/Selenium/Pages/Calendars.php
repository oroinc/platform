<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Calendars
 *
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium\Pages
 * @method Calendars openCalendars(string $bundlePath)
 * {@inheritdoc}
 */
class Calendars extends AbstractPage
{
    const URL = 'calendar/default';

    /**
     * @return Calendar
     */
    public function addEvent()
    {
        $this->test->byXpath("//td[contains(@class,'fc-today fc-state-highlight')]")->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]".
            "/span[normalize-space(.)='Add New Event']"
        );
        return new Calendar($this->test, false);
    }

    /**
     * @param string $event
     * @return Calendar
     */
    public function editEvent($event)
    {
        $this->test->byXpath("//td[@class='fc-event-container']/a[contains(., '{$event}')]")->click();
        $this->waitForAjax();
        $this->test->byXpath("//button[@type='button'][normalize-space(.)='Edit']")->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]".
            "/span[normalize-space(.)='Edit Event']"
        );

        return new Calendar($this->test, false);
    }

    /**
     * @param string $event
     * @return $this
     */
    public function checkEventPresent($event)
    {
        $this->assertElementPresent(
            "//div[@class='fc-event-container']/a[contains(., '{$event}')]",
            'Event not found at calendar'
        );

        return $this;
    }

    public function checkReminderIcon($event)
    {
        $this->assertElementPresent(
            "//td[@class='fc-event-container']/a[contains(., '{$event}')]/i[@class='reminder-status icon-bell']",
            'Reminder icon for event not found at calendar'
        );

        return $this;
    }

    /**
     * @param string $event
     * @return $this
     */
    public function checkEventNotPresent($event)
    {
        $this->assertElementNotPresent(
            "//td[@class='fc-event-container']/a[contains(., '{$event}')]",
            'Event is found at calendar'
        );

        return $this;
    }
}
