<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium;

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
     * Test to check that
     * @depends testSendEmail
     * @param $subject
     */
    public function testEmailReceive($subject)
    {
        $username = 'user_' . mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('All - Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail('mailbox1@example.com')
            ->setRoles(array('Manager', 'Marketing Manager'), true)
            ->setOrganization('OroCRM')
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->filterBy('Subject', $subject)
            ->entityExists([$subject]);
    }

    public function testUserImap()
    {
        $imapSetting = array(
            'host' => 'localhost.com',
            'port' => '143',
            'user' => 'mailbox1@example.com',
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
            ->setEmail($username.'@mail.com')
            ->setRoles(array('Administrator'), true)
            ->setBusinessUnit(array ('OroCRM'))
            ->uncheckInviteUser()
            ->setImap($imapSetting)
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        exec("app/console oro:cron:imap-sync --env prod");
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->assertElementNotPresent(
                "//div[@class='no-data-visible floatThead-fixed floatThead']//span[contains(., 'No records found')]"
            );
    }
}
