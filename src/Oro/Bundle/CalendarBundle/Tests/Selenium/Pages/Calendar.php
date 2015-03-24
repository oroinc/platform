<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium\Pages;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Calendar
 *
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium\Pages
 * @method Calendar openCalendar() openCalendar(string)
 * {@inheritdoc}
 */
class Calendar extends Calendars
{
    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->$title = $this->test->byId('oro_calendar_event_form_title');
        $this->$title->clear();
        $this->$title->value($title);

        return $this;
    }

    /**
     * @param $date string Start date
     * @return $this
     * @throws \Exception
     */
    public function setStartDate($date)
    {
        $startDate = $this->test->byId('date_selector_oro_calendar_event_form_start');
        $startTime = $this->test->byId('time_selector_oro_calendar_event_form_start');
        $startDate->clear();
        $startTime->clear();
        if (preg_match('/^(.+)\s(\d{2}\:\d{2}\s\w{2})$/', $date, $date)) {
            $this->test->execute(
                array(
                    'script' => "$('#date_selector_oro_calendar_event_form_start').val('$date[1]');" .
                        "$('#date_selector_oro_calendar_event_form_start').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
            $this->test->execute(
                array(
                    'script' => "$('#time_selector_oro_calendar_event_form_start').val('$date[2]');" .
                        "$('#time_selector_oro_calendar_event_form_start').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
        } else {
            throw new Exception("Value {$date} is not a valid date");
        }

        return $this;
    }

    /**
     * @param $date string End date
     * @return $this
     * @throws \Exception
     */
    public function setEndDate($date)
    {
        $startDate = $this->test->byId('date_selector_oro_calendar_event_form_end');
        $startTime = $this->test->byId('time_selector_oro_calendar_event_form_end');
        $startDate->clear();
        $startTime->clear();
        if (preg_match('/^(.+)\s(\d{2}\:\d{2}\s\w{2})$/', $date, $date)) {
            $this->test->execute(
                array(
                    'script' => "$('#date_selector_oro_calendar_event_form_end').val('$date[1]');" .
                        "$('#date_selector_oro_calendar_event_form_end').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
            $this->test->execute(
                array(
                    'script' => "$('#time_selector_oro_calendar_event_form_end').val('$date[2]');" .
                        "$('#time_selector_oro_calendar_event_form_end').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
        } else {
            throw new \Exception("Value {$date} is not a valid date");
        }

        return $this;
    }

    /**
     * This method adds existing user as a guest to calendar event
     * @param $username
     * @return $this
     */
    public function setGuestUser($username)
    {
        $this->test->byXpath(
            "//div[starts-with(@id,'s2id_oro_calendar_event_form_invitedUsers')]//input"
        )->value($username);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$username}')]",
            "Guest user not found"
        );
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$username}')]")->click();

        return $this;
    }

    /**
     * This method sets reminder and it parameters
     * @param string $reminderMethod
     * @param string $reminderInterval
     * @param string $intervalUnit
     * @return $this
     */
    public function setReminder($reminderMethod = 'Email', $reminderInterval = '10', $intervalUnit = 'minutes')
    {
        $this->test->byXPath("//a[@class='btn add-list-item'][contains(., 'Add')]")->click();
        $this->waitForAjax();
        $method = $this->test->select($this->test->byXPath("(//select[@id[contains(., 'method')]])[last()]"));
        $method->selectOptionByLabel($reminderMethod);
        $interval = $this->test->byXPath("(//input[@id[contains(., 'interval_number')]])[last()]");
        $interval->value($reminderInterval);
        $unit = $this->test->select($this->test->byXPath("(//select[@id[contains(., 'interval_unit')]])[last()]"));
        $unit->selectOptionByLabel($intervalUnit);

        return $this;
    }

    /**
     * This methods switch off all day long option in calendar event
     * @return $this
     */
    public function setAllDayEventOff()
    {
        if ($this->isElementPresent("//input[@id='oro_calendar_event_form_allDay'][@value='1']")) {
            $this->test->byXPath("//input[@id='oro_calendar_event_form_allDay']")->click();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function saveEvent()
    {
        $this->test->byXpath("//button[@type='submit'][normalize-space(.)='Save']")->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]",
            'Event window is still open'
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteEvent()
    {
        $this->test->byXpath(
            "//div[@class='widget-actions-section']//a[@title[normalize-space(.)='Delete event']]"
        )->click();
        $this->test->byXpath(
            "//div[@class='modal oro-modal-danger in']//a[normalize-space(.)='Yes, Delete']"
        )->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]",
            'Event window is still open'
        );

        return $this;
    }
}
