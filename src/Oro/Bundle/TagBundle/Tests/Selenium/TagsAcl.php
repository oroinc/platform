<?php

namespace Oro\Bundle\TagBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class TagsAcl extends Selenium2TestCase
{
    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = $this->login();
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->add()
            ->setLabel('Label_' . $randomPrefix)
            ->setOwner('Main')
            ->setEntity('Tag', array('Create', 'Edit', 'Delete', 'View'), 'System')
            ->setEntity('User', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->setEntity('Group', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->setEntity('Role', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->setCapability(
                array(
                    'Tag assign/unassign',
                    'Unassign all tags from entities',
                    'View tag cloud'),
                'System'
            )
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
        $userName = 'User_'.mt_rand();

        $login = $this->login();
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users $login*/
        $login->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->assertTitle('Create User - Users - Users Management - System')
            ->setUsername($userName)
            ->setOwner('Main')
            ->enable()
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$userName)
            ->setLastName('Last_'.$userName)
            ->setEmail($userName.'@mail.com')
            ->setRoles(array('Label_' . $role))
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('Users - Users Management - System');

        return $userName;
    }

    /**
     * @depends testCreateUser
     * @return string
     */
    public function testCreateTag()
    {
        $tagName = 'Tag_'.mt_rand();

        $login = $this->login();
        /** @var \Oro\Bundle\TagBundle\Tests\Selenium\Pages\Tags $login*/
        $login->openTags('Oro\Bundle\TagBundle')
            ->add()
            ->assertTitle('Create Tag - Tags - System')
            ->setTagname($tagName)
            ->setOwner('admin')
            ->save()
            ->assertMessage('Tag saved')
            ->assertTitle('Tags - System')
            ->close();

        return $tagName;
    }

    /**
     * @depends testCreateUser
     * @depends testCreateRole
     * @depends testCreateTag
     * @param $username
     * @param $role
     * @param $tagName
     * @param string $aclCase
     * @dataProvider columnTitle
     */
    public function testTagAcl($aclCase, $username, $role, $tagName)
    {
        $roleName = 'Label_' .  $role;
        $login = $this->login();
        switch ($aclCase) {
            case 'delete':
                $this->deleteAcl($login, $roleName, $username, $tagName);
                break;
            case 'update':
                $this->updateAcl($login, $roleName, $username, $tagName);
                break;
            case 'create':
                $this->createAcl($login, $roleName, $username);
                break;
            case 'view list':
                $this->viewListAcl($login, $roleName, $username);
                break;
            case 'unassign global':
                $this->unassignGlobalAcl($login, $roleName, $tagName);
                break;
            case 'assign unassign':
                $this->assignAcl($login, $roleName, $username);
                break;
        }
    }

    public function deleteAcl($login, $role, $username, $tagName)
    {
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $role)
            ->open(array($role))
            ->setEntity('Tag', array('Delete'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTags('Oro\Bundle\TagBundle')
            ->checkContextMenu($tagName, 'Delete');
    }

    public function updateAcl($login, $role, $username, $tagName)
    {
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $role)
            ->open(array($role))
            ->setEntity('Tag', array('Edit'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTags('Oro\Bundle\TagBundle')
            ->checkContextMenu($tagName, 'Update');
    }

    public function createAcl($login, $role, $username)
    {
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $role)
            ->open(array($role))
            ->setEntity('Tag', array('Create'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTags('Oro\Bundle\TagBundle')
            ->assertElementNotPresent("//div[@class = 'container-fluid']//a[contains(., 'Create tag')]");
    }

    public function viewListAcl($login, $role, $username)
    {
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $role)
            ->open(array($role))
            ->setEntity('Tag', array('View'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openTags('Oro\Bundle\TagBundle')
            ->assertTitle('403 - Forbidden');
    }

    public function unassignGlobalAcl($login, $roleName, $tagName)
    {
        $username = 'user' . mt_rand();
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setCapability(array('Unassign all tags from entities'), 'None')
            ->save()
            ->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array($roleName))
            ->setTag($tagName)
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $username)
            ->open(array($username))
            ->edit()
            ->assertElementNotPresent(
                "//div[@id='s2id_oro_user_user_form_tags']//li[contains(., '{$tagName}')]" .
                "/a[@class='select2-search-choice-close']"
            );
    }

    public function assignAcl($login, $role, $username)
    {
        /** @var \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles $login*/
        $login->openRoles('Oro\Bundle\UserBundle')
            ->filterBy('Label', $role)
            ->open(array($role))
            ->setCapability(array('Tag assign/unassign'), 'None')
            ->save()
            ->logout()
            ->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->assertElementNotPresent(
                "//div[@class='select2-container select2-container-multi select2-container-disabled']"
            );
    }

    /**
     * Data provider for Tags ACL test
     *
     * @return array
     */
    public function columnTitle()
    {
        return array(
            'unassign global' => array('unassign global'),
            'assign unassign' => array('assign unassign'),
            'delete' => array('delete'),
            'update' => array('update'),
            'create' => array('create'),
            'view list' => array('view list'),
        );
    }
}
