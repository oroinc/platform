<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium;

use Oro\Bundle\CalendarBundle\Tests\Selenium\Pages\Calendars;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class GuestCalendarTest
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium
 */
class GuestCalendarTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateUser()
    {
        $username = 'User_'.mt_rand();

        $page = $this->login()->openUsers('Oro\Bundle\UserBundle')->add();
        /** @var Users $login */
        $page->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(['Administrator']);
        if ($page->hasBusinessUnitOrganizationChoice()) {
            $page->setBusinessUnitOrganization(['OroCRM']);
        }
        $page->setBusinessUnit()
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->close();

        return $username;
    }

    /**
     * @depends testCreateUser
     * @param $username
     * @return string
     */
    public function testAddEventToGuestUser($username)
    {
        $eventName = 'Event_'.mt_rand();
        $login = $this->login();
        /* @var Calendars $login */
        $login->openCalendars('Oro\Bundle\CalendarBundle')
            ->assertTitle('My Calendar - John Doe')
            ->addEvent()
            ->setTitle($eventName)
            ->setAllDayEventOff()
            ->setGuestUser($username)
            ->setReminder('Flash message', '1', 'days')
            ->saveEvent()
            ->checkEventPresent($eventName)
            ->checkReminderIcon($eventName)
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openCalendars('Oro\Bundle\CalendarBundle')
            ->checkEventPresent($eventName);
    }

    public function testCloseWidgetWindow()
    {
        $login = $this->login();
        $login->closeWidgetWindow();
    }
}
