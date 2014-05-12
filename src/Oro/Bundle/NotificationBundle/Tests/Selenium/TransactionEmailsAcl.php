<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class TransactionEmailsAcl extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setOwner('Main')
            ->setEntity('Email Notification', array('Create', 'Edit', 'Delete', 'View'), 'System')
            ->save()
            ->assertMessage('Role saved')
            ->close();

        return ($randomPrefix);
    }

    /**
     * @depends testCreateRole
     * @param $role
     * @return string
     */
    public function testCreateUser($role)
    {
        $username = 'User_'.mt_rand();

        $login = $this->login();
        $login->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->assertTitle('Create User - Users - Users Management - System')
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array('Label_' . $role))
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('Users - Users Management - System');

        return $username;
    }

    /**
     * @depends testCreateUser
     * @return string
     */
    public function testCreateTransactionEmail()
    {
        $email = 'Email'.mt_rand() . '@mail.com';

        $login = $this->login();
        $login->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->add()
            ->assertTitle('Add Notification Rule - Notification Rules - Emails - System')
            ->setEmail($email)
            ->setEntityName('User')
            ->setEvent('Entity create')
            ->setTemplate('user')
            ->setUser('admin')
            ->setGroups(array('Marketing'))
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('Notification Rules - Emails - System')
            ->close();

        return $email;
    }


    /**
     * @param $aclCase
     * @param $username
     * @param $role
     * @param $email
     *
     * @dataProvider columnTitle
     */
    public function testTransactionEmailAcl($aclCase, $username, $role, $email)
    {
        $roleName = 'Label_' . $role;
        $login = $this->login();
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit();
        switch ($aclCase) {
            case 'delete':
                $this->deleteAcl($login, $roleName, $username, $email);
                break;
            case 'update':
                $this->updateAcl($login, $roleName, $username, $email);
                break;
            case 'create':
                $this->createAcl($login, $roleName, $username);
                break;
            case 'view':
                $this->viewListAcl($login, $roleName, $username);
                break;
        }
    }

    public function deleteAcl($login, $roleName, $username, $email)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Email Notification', array('Delete'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->checkContextMenu($email, 'Delete');
    }

    public function updateAcl($login, $roleName, $username, $email)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Email Notification', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->checkContextMenu($email, 'Update');
    }

    public function createAcl($login, $roleName, $username)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Email Notification', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertElementNotPresent("//div[@class = 'container-fluid']//a[contains(., 'Create Notification Rule')]");
    }

    public function viewListAcl($login, $roleName, $username)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Email Notification', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertTitle('403 - Forbidden');
    }

    /**
     * Data provider for Tags ACL test
     *
     * @return array
     */
    public function columnTitle()
    {
        return array(
            'delete' => array('delete'),
            'update' => array('update'),
            'create' => array('create'),
            'view list' => array('view'),
        );
    }
}
