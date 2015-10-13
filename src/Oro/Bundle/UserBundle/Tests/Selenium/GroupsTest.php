<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Groups;

/**
 * Class GroupsTest
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium
 */
class GroupsTest extends Selenium2TestCase
{
    protected $newGroup = array('NAME' => 'NEW_GROUP_', 'ROLE' => 'Administrator');

    protected $defaultGroups = array(
        'header' => array('NAME' => 'NAME', 'ROLES' => 'ROLES', '' => 'ACTION'),
        'Administrators' => array(
            'Administrators' => 'Administrators',
            'Administrator'  => 'ROLES',
            "Edit\nDelete"   => 'ACTION'
        ),
        'Marketing' => array('Marketing' => 'Marketing', 'Manager' => 'ROLES', "Edit\nDelete" => 'ACTION'),
        'Sales' => array('Sales' => 'Sales', 'Manager' => 'ROLES', "Edit\nDelete" => 'ACTION')
    );

    public function testGroupsGrid()
    {
        $login = $this->login();
        /** @var Groups $login */
        $login->openGroups('Oro\Bundle\UserBundle')
            ->assertTitle('All - Groups - User Management - System');
    }

    public function testGroupsGridDefaultContent()
    {
        $login = $this->login();
        /** @var Groups $login */
        $groups = $login->openGroups('Oro\Bundle\UserBundle');
        //get grid content
        $records = $groups->getRows();
        $headers = $groups->getHeaders();

        foreach ($headers as $header) {
            /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $header */
            $content = $header->text();
            static::assertArrayHasKey($content, $this->defaultGroups['header']);
        }

        $checks = 0;
        foreach ($records as $row) {
            /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $row */
            $columns = $row->elements($this->using('xpath')->value("td[not(contains(@style, 'display: none;'))]"));
            $id = null;
            foreach ($columns as $column) {
                /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $column */
                $content = $column->text();
                if (is_null($id)) {
                    $id = $content;
                }
                if (array_key_exists($id, $this->defaultGroups)) {
                    static::assertArrayHasKey($content, $this->defaultGroups[$id]);
                }
            }
            $checks++;
        }
        static::assertGreaterThanOrEqual(count($this->defaultGroups)-1, $checks);
    }

    public function testGroupAdd()
    {
        $randomPrefix = WebTestCase::generateRandomString(5);

        $login = $this->login();
        /** @var Groups $login */
        $groups = $login->openGroups('Oro\Bundle\UserBundle')
            ->add()
            ->setName($this->newGroup['NAME'] . $randomPrefix)
            ->setOwner('Main')
            //->setRoles(array($this->newGroup['ROLE']))
            ->save()
            ->assertMessage('Group saved')
            ->close();

        static::assertTrue($groups->entityExists(array('name' => $this->newGroup['NAME'] . $randomPrefix)));

        return $randomPrefix;
    }

    /**
     * @depends testGroupAdd
     * @param $randomPrefix
     */
    public function testGroupDelete($randomPrefix)
    {
        $login = $this->login();
        /** @var Groups $login */
        $groups = $login->openGroups('Oro\Bundle\UserBundle');
        $groups->delete(array('name' => $this->newGroup['NAME'] . $randomPrefix));
        static::assertFalse($groups->entityExists(array('name' => $this->newGroup['NAME'] . $randomPrefix)));
    }
}
