<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Selenium;

use Oro\Bundle\CalendarBundle\Tests\Selenium\Pages\Calendar;
use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class ActivityListTest extends Selenium2TestCase
{
    const USERNAME  = 'admin';

    /**
     * Test Notes functionality set On
     */
    public function testActivitiesOn()
    {
        $entityName = 'User';

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName)
            ->open([$entityName])
            ->edit()
            ->setActivitiesOn(array('Emails', 'Calendar events'))
            ->save()
            ->updateSchema()
            ->assertMessage('Schema updated');
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->checkActionInGroup(array('Send email', 'Add Event'), true);
    }

    /**
     * Test add new Note to User entity
     * @depends testActivitiesOn
     * @return string
     */
    public function testCalendarActivity()
    {
        $event = 'Some event_' . mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->runActionInGroup('Add Event');
        /* @var Calendar $login */
        $login->openCalendar('Oro\Bundle\CalendarBundle')
            ->setTitle($event)
            ->saveEvent();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->verifyActivity('Calendar event', $event);
    }
}
