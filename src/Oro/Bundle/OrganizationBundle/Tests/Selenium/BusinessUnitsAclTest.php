<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium;

use Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages\BusinessUnits;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class BusinessUnitsAclTest extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        /** @var Roles $login */
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setEntity('Business Unit', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->assertTitle('Create Role - Roles - User Management - System')
            ->save()
            ->assertMessage('Role saved')
            ->assertTitle('All - Roles - User Management - System')
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
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->enable()
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array('Label_' . $role))
            ->setBusinessUnitOrganization(['OroCRM'])
            ->setBusinessUnit()
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('All - Users - User Management - System');

        return $username;
    }

    /**
     * @depends testCreateUser
     * @return string
     */
    public function testCreateBusinessUnit()
    {
        $unitName = 'Unit_'.mt_rand();

        $login = $this->login();
        /** @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->add()
            ->assertTitle('Create Business Unit - Business Units - User Management - System')
            ->setBusinessUnitName($unitName)
            ->setOrganization('OroCRM')
            ->save()
            ->assertMessage('Business Unit saved')
            ->toGrid()
            ->assertTitle('All - Business Units - User Management - System')
            ->close();

        return $unitName;
    }

    /**
     * @depends testCreateUser
     * @depends testCreateRole
     * @depends testCreateBusinessUnit
     *
     * @param $aclCase
     * @param $username
     * @param $role
     * @param $unitName
     *
     * @dataProvider columnTitle
     */
    public function testAccountAcl($aclCase, $username, $role, $unitName)
    {
        $roleName = 'Label_' . $role;
        $login = $this->login();
        switch ($aclCase) {
            case 'delete':
                $this->deleteAcl($login, $roleName, $username, $unitName);
                break;
            case 'update':
                $this->updateAcl($login, $roleName, $username, $unitName);
                break;
            case 'create':
                $this->createAcl($login, $roleName, $username);
                break;
            case 'view':
                $this->viewAcl($login, $username, $roleName, $unitName);
                break;
        }
    }

    public function deleteAcl($login, $roleName, $username, $unitName)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Delete'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->assertNoActionMenu('Delete')
            ->open(array($unitName))
            ->assertElementNotPresent("//div[@class='pull-left btn-group icons-holder']/a[@title='Delete Account']");
    }

    public function updateAcl($login, $roleName, $username, $unitName)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->assertNoActionMenu('Update')
            ->open(array($unitName))
            ->assertElementNotPresent(
                "//div[@class='pull-left btn-group icons-holder']/a[@title='Edit Business Unit']"
            );
    }

    public function createAcl($login, $roleName, $username)
    {
        /** @var Roles $login */
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->assertElementNotPresent(
                "//div[@class='pull-right title-buttons-container']//a[@title='Create Business Unit']"
            );
    }

    public function viewAcl($login, $username, $roleName)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->assertTitle('403 - Forbidden');
    }

    /**
     * Data provider for Business Unit ACL test
     *
     * @return array
     */
    public function columnTitle()
    {
        return array(
            'delete' => array('delete'),
            'update' => array('update'),
            'create' => array('create'),
            'view' => array('view'),
        );
    }
}
