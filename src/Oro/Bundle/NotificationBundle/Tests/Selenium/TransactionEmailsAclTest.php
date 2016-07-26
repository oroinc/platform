<?php

namespace Oro\Bundle\NotificationBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class TransactionEmailsAclTest extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        /** @var Roles $login */
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setEntity('Notification Rule', array('Create', 'Edit', 'Delete', 'View'), 'System')
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

        $page = $this->login()->openUsers('Oro\Bundle\UserBundle')->add();
        $page->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array('Label_' . $role));
        if ($page->hasBusinessUnitOrganizationChoice()) {
            $page->setBusinessUnitOrganization(['OroCRM']);
        }
        $page->setBusinessUnit(['Main'])
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('All - Users - User Management - System');

        return $username;
    }

    /**
     * @return string
     */
    public function testCreateEmailTemplateForCreateUser()
    {
        $templateName = 'CreateUser_EmailTemplate_'.mt_rand();

        $login = $this->login();
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->assertTitle('All - Templates - Emails - System')
            ->add()
            ->assertTitle('Create Email Template - Templates - Emails - System')
            ->setEntityName('User')
            ->setType('Html')
            ->setName($templateName)
            ->setSubject('Subject')
            ->setContent('Template content')
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('All - Templates - Emails - System')
            ->close();

        return $templateName;
    }

    /**
     * @depends testCreateUser
     * @depends testCreateEmailTemplateForCreateUser
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
            ->setTemplate('CreateUser_EmailTemplate')
            ->setUser('admin')
            ->setGroups(array('Marketing'))
            ->save()
            ->assertMessage('Email notification rule saved')
            ->assertTitle('All - Notification Rules - Emails - System')
            ->close();

        return $email;
    }


    /**
     * @depends testCreateUser
     * @depends testCreateRole
     * @depends testCreateTransactionEmail
     *
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
            ->setEntity('Notification Rule', array('Delete'), 'None')
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
            ->setEntity('Notification Rule', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->checkContextMenu($email, 'Edit');
    }

    public function createAcl($login, $roleName, $username)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Notification Rule', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertElementNotPresent(
                "//div[@class='pull-right title-buttons-container']".
                "//a[contains(., 'Create Notification Rule')]"
            );
    }

    public function viewListAcl($login, $roleName, $username)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Notification Rule', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTransactionEmails('Oro\Bundle\NotificationBundle')
            ->assertTitle('403 - Forbidden');
    }

    /**
     * Data provider for Transaction Emails ACL test
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
