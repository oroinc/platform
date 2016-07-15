<?php

namespace Oro\Bundle\TrackingBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\TrackingBundle\Tests\Selenium\Pages\TrackingWebsites;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class TrackingWebsiteAcl
 *
 * @package Oro\Bundle\TrackingBundle\Tests\Selenium
 * {@inheritdoc}
 */
class TrackingWebsiteAclTest extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        /** @var Roles $login */
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setEntity('Tracking Website', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->assertTitle('Create Role - Roles - User Management - System')
            ->save()
            ->assertMessage('Role saved')
            ->assertTitle('All - Roles - User Management - System');

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
            ->setOwner('Main')
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
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
     * @return string
     */
    public function testCreateTrackingWebsite()
    {
        $identifier = 'Website' . mt_rand();

        $login = $this->login();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->assertTitles('Tracking Websites', 'Tracking Websites - Marketing')
            ->add()
            ->assertTitles('Create Tracking Website', 'Create Tracking Website - Tracking Websites - Marketing')
            ->setName($identifier)
            ->setIdentifier($identifier)
            ->setUrl("http://{$identifier}.com")
            ->save()
            ->assertMessage('Tracking Website saved')
            ->assertTitles("{$identifier}", "{$identifier} - Tracking Websites - Marketing");

        return $identifier;
    }

    /**
     * @depends testCreateUser
     * @depends testCreateRole
     * @depends testCreateTrackingWebsite
     *
     * @param $aclCase
     * @param $username
     * @param $role
     * @param $identifier
     *
     * @dataProvider columnTitle
     */
    public function testCaseAcl($aclCase, $username, $role, $identifier)
    {
        $roleName = 'Label_' . $role;
        $login = $this->login();
        switch ($aclCase) {
            case 'delete':
                $this->deleteAcl($login, $roleName, $username, $identifier);
                break;
            case 'update':
                $this->updateAcl($login, $roleName, $username, $identifier);
                break;
            case 'create':
                $this->createAcl($login, $roleName, $username);
                break;
            case 'view':
                $this->viewAcl($login, $username, $roleName);
                break;
        }
    }

    /**
     * @param $login
     * @param $roleName
     * @param $username
     * @param $identifier
     */
    public function deleteAcl($login, $roleName, $username, $identifier)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Tracking Website', array('Delete'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->filterBy('Identifier', $identifier)
            ->assertNoActionMenu('Delete')
            ->open(array($identifier))
            ->assertElementNotPresent(
                "//div[@class='pull-left btn-group icons-holder']/a[@title='Delete Tracking Website']"
            );
    }

    public function updateAcl($login, $roleName, $username, $identifier)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Tracking Website', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->filterBy('Identifier', $identifier)
            ->assertNoActionMenu('Update')
            ->open(array($identifier))
            ->assertElementNotPresent(
                "//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit Tracking Website']"
            );
    }

    public function createAcl($login, $roleName, $username)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Tracking Website', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
            ->assertElementNotPresent(
                "//div[@class='pull-right title-buttons-container']//a[contains(., 'Create Tracking Website')]"
            );
    }

    public function viewAcl($login, $username, $roleName)
    {
        /** @var Roles $login */
        $login = $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Tracking Website', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        /** @var TrackingWebsites $login */
        $login->openTrackingWebsites('Oro\Bundle\TrackingBundle')
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
