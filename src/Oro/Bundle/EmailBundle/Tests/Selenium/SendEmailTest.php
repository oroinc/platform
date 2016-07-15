<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium;

use Oro\Bundle\ConfigBundle\Tests\Selenium\Pages\UserEmailSettings;
use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\Emails;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class SendEmailTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Selenium
 */
class SendEmailTest extends Selenium2TestCase
{
    /**
     * Test to check that email can be sent
     * @return string
     */
    public function testSendEmail()
    {
        $subject = 'Subject_'.mt_rand();

        $login = $this->login();
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->add()
            ->setSubject($subject)
            ->setTo('mailbox1@example.com')
            ->setBody('Body text for ' . $subject)
            ->send()
            ->assertMessage('The email was sent');
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->filterBy('Subject', $subject)
            ->entityExists([$subject]);

        return $subject;
    }

    /**
     * Test to check that user IMAP sync works
     * @return string
     */
    public function testUserImapSync()
    {
        $imapSetting = array(
            'host' => PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST,
            'port' => '143',
            'encryption' => '',
            'user' => 'mailbox1',
            'password' => 'eF3ar4ic'
        );

        $username = 'user_' . mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('All - Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->setOwner('Main')
            ->enable()
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@example.com')
            ->setRoles(['Administrator'], true)
            ->setOrganizationOnForm(['OroCRM'])
            ->setBusinessUnit(['Main'])
            ->uncheckInviteUser()
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var UserEmailSettings $login */
        $login->openUserEmailSettings('Oro\Bundle\ConfigBundle')->setImap($imapSetting);
        exec("app/console oro:cron:imap-sync --env prod");
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->assertElementNotPresent(
                "//div[@class='no-data-visible floatThead-fixed floatThead']//span[contains(., 'No records found')]",
                'No emails were synced'
            );

        return $username;
    }

    /**
     * Test to check that sent email received by user
     * @depends testUserImap
     * @param $username
     */
    public function testEmailReceive($username)
    {
        $subject = 'Email for ' . $username;

        $login = $this->login();
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->add()
            ->setSubject($subject)
            ->setTo('mailbox1@example.com')
            ->setBody('Email body text for ' . $username)
            ->send()
            ->assertMessage('The email was sent')
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        exec("app/console oro:cron:imap-sync --env prod");
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->filterBy('Subject', $subject)
            ->entityExists([$subject]);
    }

    /**
     * Test to check that select2 drop-down returns correct first name and username
     */
    public function testSuggestionsList()
    {
        $firstName = 'First name_'.mt_rand();
        $lastName = 'Last name_'.mt_rand();
        $username = 'username_'.mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('All - Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->setOwner('Main')
            ->enable()
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($username.'@example.com')
            ->setRoles(['Administrator'], true)
            ->setOrganizationOnForm(['OroCRM'])
            ->setBusinessUnit(['Main'])
            ->uncheckInviteUser()
            ->save();
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->add()
            ->checkSendToList($firstName)
            ->checkContextSuggestionList($username);
    }

    public function testCloseWidgetWindow()
    {
        $login = $this->login();
        $login->closeWidgetWindow();
    }
}
