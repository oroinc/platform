<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Selenium;

use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\Email;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class ActivityListTest extends Selenium2TestCase
{
    public function testEmailActivity()
    {
        $subject = 'Subject_'.mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->runActionInGroup('Send email');
        /** @var Email $login */
        $login->openEmail('Oro\Bundle\EmailBundle')
            ->setSubject($subject)
            ->setTo('mailbox1@example.com')
            ->setBody('Body text for ' . $subject)
            ->send()
            ->assertMessage('The email was sent')
            ->verifyActivity('Email', $subject);
    }

    public function testCloseWidgetWindow()
    {
        $login = $this->login();
        $login->closeWidgetWindow();
    }
}
