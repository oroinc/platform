<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium\Pages;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Calendar
 *
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium\Pages
 * @method Calendar openCalendar(string $bundlePath)
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
        $this->$title = $this->test->byXpath("//*[@data-ftid='oro_calendar_event_form_title']");
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
        $startDate = $this->test->byXpath("//*[@data-ftid='oro_calendar_event_form_start']/..".
            "/following-sibling::*//input[contains(@class,'datepicker-input')]");
        $startTime = $this->test->byXpath("//*[@data-ftid='oro_calendar_event_form_start']/..".
            "/following-sibling::*//input[contains(@class,'timepicker-input')]");
        $startDate->clear();
        $startTime->clear();
        if (preg_match('/^(.+\d{4}),?\s(\d{1,2}\:\d{2}\s\w{2})$/', $date, $dateParts)) {
            $startDate->click(); // focus
            $startDate->value($dateParts[1]);
            $startTime->click(); // focus
            $startTime->clear();
            $startTime->value($dateParts[2]);
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
        $endDate = $this->test->byXpath("//*[@data-ftid='oro_calendar_event_form_end']/..".
            "/following-sibling::*//input[contains(@class,'datepicker-input')]");
        $endTime = $this->test->byXpath("//*[@data-ftid='oro_calendar_event_form_end']/..".
            "/following-sibling::*//input[contains(@class,'timepicker-input')]");
        $endDate->clear();
        $endTime->clear();
        if (preg_match('/^(.+\d{4}),?\s(\d{1,2}\:\d{2}\s\w{2})$/', $date, $dateParts)) {
            $endDate->click(); // focus
            $endDate->value($dateParts[1]);
            $endTime->click(); // focus
            $endTime->clear();
            $endTime->value($dateParts[2]);
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
            "//div[starts-with(@id,'s2id_oro_calendar_event_form_attendees')]//input"
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
        if ($this->isElementPresent("//input[@data-ftid='oro_calendar_event_form_allDay'][@value='1']")) {
            $this->test->byXPath("//input[@data-ftid='oro_calendar_event_form_allDay']")->click();
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
