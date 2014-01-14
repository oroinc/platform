<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class BusinessUnitsAclTest extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setOwner('Main')
            ->setEntity('Business Unit', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
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
    public function testCreateBusinessUnit()
    {
        $unitName = 'Unit_'.mt_rand();

        $login = $this->login();
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->add()
            ->assertTitle('Create Business Unit - Business Units - Users Management - System')
            ->setBusinessUnitName($unitName)
            ->setOwner('Main')
            ->save()
            ->assertMessage('Business Unit saved')
            ->toGrid()
            ->assertTitle('Business Units - Users Management - System')
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
    public function testBusinessUnitAcl($aclCase, $username, $role, $unitName)
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
            case 'view list':
                $this->viewListAcl($login, $roleName, $username);
                break;
        }
    }

    public function deleteAcl($login, $roleName, $username, $unitName)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Delete'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->checkContextMenu($unitName, 'Delete');
    }

    public function updateAcl($login, $roleName, $username, $unitName)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->checkContextMenu($unitName, 'Update');
    }

    public function createAcl($login, $roleName, $username)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->assertElementNotPresent("//div[@class = 'container-fluid']//a[contains(., 'Create business unit')]");
    }

    public function viewAcl($login, $username, $roleName)
    {
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Business Unit', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openBusinessUnits('Oro\Bundle\OrganizationBundle')
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
            'view' => array('view'),
        );
    }
}
