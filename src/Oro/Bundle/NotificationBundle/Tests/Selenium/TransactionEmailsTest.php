<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium;

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

        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->add()
            ->assertTitle('Add Notification Rule - Notifications - Emails - System')
            ->setEntityName('User')
            ->setEvent('Entity create')
            ->setTemplate('user')
            ->setUser('admin')
            ->setGroups(array('Marketing'))
            ->setEmail($email)
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('Notifications - Emails - System')
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
        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->open(array($email))
            ->setEmail($newEmail)
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('Notifications - Emails - System')
            ->close();

        return $newEmail;
    }

    /**
     * @depends testUpdateTransactionEmail
     * @param $email
     */
    public function testDeleteTransactionEmail($email)
    {
        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->delete('Recipient email', $email)
            ->assertTitle('Notifications - Emails - System')
            ->assertMessage('Item deleted');
    }
}
