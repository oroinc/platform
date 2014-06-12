<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Calendar
 *
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium\Pages
 * @method Calendar openCalendar() openCalendar(string)
 * {@inheritdoc}
 */
class Calendar extends AbstractPage
{
    const URL = 'calendar/default';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    public function open($entityData = array())
    {

    }

    public function addEvent()
    {
        $this->test->byXpath("//td[@class='fc-day fc-thu fc-widget-content fc-today fc-state-highlight']")->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix']".
            "/span[normalize-space(.)='Add New Event']"
        );

        return $this;
    }

    /**
     * @param string $event
     * @return object $this
     */
    public function editEvent($event)
    {
        $this->test->byXpath("//div[@class='fc-event-container']//span[normalize-space(.)='{$event}']")->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix']".
            "/span[normalize-space(.)='Edit Event']"
        );

        return $this;
    }

    /**
     * @param string $title
     * @return object $this
     */
    public function setTitle($title)
   {
       $this->$title = $this->test->byId('oro_calendar_event_form_title');
       $this->$title->clear();
       $this->$title->value($title);

       return $this;
   }

    public function saveEvent()
    {
        $this->test->byXpath("//button[@type='submit'][normalize-space(.)='Save']")->click();
        $this->waitForAjax();

        return $this;
    }

    public function deleteEvent()
    {
        $this->test->byXpath(
            "//div[@class='widget-actions-section']//a[@title[normalize-space(.)='Delete event']]"
        )->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $event
     * @return object $this
     */
    public function checkEventPresent($event)
    {
        $this->assertElementPresent(
            "//div[@class='fc-event-container']//span[normalize-space(.)='{$event}']",
            'Event not found at calendar');

        return $this;
    }

    /**
     * @param string $event
     * @return object $this
     */
    public function checkEventNotPresent($event)
    {
        $this->assertElementNotPresent(
            "//div[@class='fc-event-container']//span[normalize-space(.)='{$event}']",
            'Event is found at calendar');

        return $this;
    }
}
