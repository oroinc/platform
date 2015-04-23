<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class UserChangeEmailTest extends Selenium2TestCase
{
     /**
     * @return string
     */
    public function testCreateUser()
    {
        $username = 'User_'.mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($username)
            ->enable()
            ->setOwner('Main')
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array('Manager', 'Marketing Manager'), true)
            ->setOrganization('OroCRM')
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('Users - User Management - System');

        return $username;
    }

    /**
     * @depends testCreateUser
     * @param $username
     * @return string
     */
    public function testUpdateUserEmail($username)
    {
        $newEmail = 'Update_'.$username.'@mail.com';

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $username)
            ->open(array($username))
            ->checkEntityFieldData('Emails', $username.'@mail.com')
            ->edit()
            ->assertTitle('First_' . $username . ' Last_' . $username . ' - Edit - Users - User Management - System')
            ->setEmail($newEmail)
            ->save()
            ->assertMessage('User saved')
            ->checkEntityFieldData('Emails', $newEmail)
            ->toGrid()
            ->assertTitle('Users - User Management - System')
            ->close();
    }
}
