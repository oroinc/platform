<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles;

/**
 * Class RolesTest
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium
 */
class RolesTest extends Selenium2TestCase
{
    protected $newRole = array('LABEL' => 'NEW_LABEL_', 'ROLE_NAME' => 'NEW_ROLE_');

    protected $defaultRoles = array(
        'header' => array(
            'ROLE' => 'ROLE',
            'LABEL' => 'LABEL',
            '' => 'ACTION'
        ),
        'ROLE_MANAGER' => array(
            'ROLE_MANAGER' => 'ROLE_MANAGER',
            'Manager' => 'Manager',
            '...' => 'ACTION'
        ),
        'ROLE_ADMINISTRATOR' => array(
            'ROLE_ADMINISTRATOR' => 'ROLE_ADMINISTRATOR',
            'Administrator' => 'Administrator',
            '...' => 'ACTION'
        ),
        'ROLE_USER' => array(
            'ROLE_USER' => 'ROLE_USER',
            'User' => 'User',
            '...' => 'ACTION'
        )
    );

    public function testRolesGrid()
    {
        $login = $this->login();
        /** @var Roles $login */
        $login->openRoles('Oro\Bundle\UserBundle')
            ->assertTitle('All - Roles - User Management - System');
    }

    public function testRolesGridDefaultContent()
    {
        $login = $this->login();
        /** @var Roles $login */
        $roles = $login->openRoles('Oro\Bundle\UserBundle');
        //get grid content
        $records = $roles->getRows();
        $headers = $roles->getHeaders();

        foreach ($headers as $header) {
            /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $header */
            $content = $header->text();
            static::assertArrayHasKey($content, $this->defaultRoles['header']);
        }

        $checks = 0;
        foreach ($records as $row) {
            /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $row */
            $columns = $row->elements($this->using('xpath')->value("td[not(contains(@style, 'display: none;'))]"));
            $id = null;
            foreach ($columns as $column) {
                /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $column */
                $content = $column->text();
                if (is_null($id)) {
                    $id = trim($content);
                }
                if (array_key_exists($id, $this->defaultRoles)) {
                    static::assertArrayHasKey($content, $this->defaultRoles[$id]);
                }
            }
            $checks++;
        }
        static::assertGreaterThanOrEqual(count($this->defaultRoles)-1, $checks);
    }

    public function testRolesAddSaveAndClose()
    {
        $randomPrefix = WebTestCase::generateRandomString(5);

        $login = $this->login();
        /** @var Roles $login */
        $roles = $login->openRoles('Oro\Bundle\UserBundle')
            ->assertTitle('All - Roles - User Management - System')
            ->add()
            ->assertTitle('Create Role - Roles - User Management - System')
            ->setLabel($this->newRole['LABEL'] . $randomPrefix)
            ->save()
            ->assertMessage('Role saved')
            ->close();

        //verify new Role
        $roles->refresh();

        static::assertTrue($roles->entityExists(array('name' => $this->newRole['LABEL'] . $randomPrefix)));

        return $randomPrefix;
    }

    /**
     * @depends testRolesAddSaveAndClose
     * @param $randomPrefix
     */
    public function testRoleDelete($randomPrefix)
    {
        $login = $this->login();
        /** @var Roles $login */
        $roles = $login->openRoles('Oro\Bundle\UserBundle');
        $roles->delete(array('name' => $this->newRole['LABEL'] . $randomPrefix));
        static::assertFalse($roles->entityExists(array('name' => $this->newRole['LABEL'] . $randomPrefix)));
    }
}
