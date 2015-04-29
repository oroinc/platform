<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium;

use Oro\Bundle\NotificationBundle\Tests\Selenium\Pages\TransactionEmails;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class TransactionEmailsTest
 *
 * @package Oro\Bundle\NotificationBundle\Tests\Selenium
 */
class TransactionEmailsTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateTransactionEmail()
    {
        $email = 'Email'.mt_rand() . '@mail.com';

        /** @var TransactionEmails $login */
        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertTitle('Notification Rules - Emails - System')
            ->add()
            ->assertTitle('Add Notification Rule - Notification Rules - Emails - System')
            ->setEntityName('Calendar event')
            ->setEvent('Entity create')
            ->setTemplate('calendar_reminder')
            ->setUser('admin')
            ->setGroups(array('Marketing'))
            ->setEmail($email)
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('Notification Rules - Emails - System')
            ->close();

        return $email;
    }

    /**
     * @depends testCreateTransactionEmail
     * @param $email
     * @return string
     */
    public function testUpdateTransactionEmail($email)
    {
        $newEmail = 'Update_' . $email;
        /** @var TransactionEmails $login */
        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertTitle('Notification Rules - Emails - System')
            ->open([$email])
            ->setEmail($newEmail)
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('Notification Rules - Emails - System')
            ->close();

        return $newEmail;
    }

    /**
     * @depends testUpdateTransactionEmail
     * @param $email
     */
    public function testDeleteTransactionEmail($email)
    {
        /** @var TransactionEmails $login */
        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->delete('Recipient email', $email)
            ->assertTitle('Notification Rules - Emails - System')
            ->assertMessage('Item deleted');
    }
}
