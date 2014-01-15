<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

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
        'Administrators' => array('Administrators' => 'Administrators', 'Administrator' => 'ROLES', '...' => 'ACTION'),
        'Marketing' => array('Marketing' => 'Marketing', 'Manager' => 'ROLES', '...' => 'ACTION'),
        'Sales' => array('Sales' => 'Sales', 'Manager' => 'ROLES', '...' => 'ACTION')
    );

    public function testGroupsGrid()
    {
        $login = $this->login();
        $login->openGroups('Oro\Bundle\UserBundle')
            ->assertTitle('Groups - Users Management - System');
    }

    public function testGroupsGridDefaultContent()
    {
        $login = $this->login();
        $groups = $login->openGroups('Oro\Bundle\UserBundle');
        //get grid content
        $records = $groups->getRows();
        $headers = $groups->getHeaders();

        foreach ($headers as $header) {
            /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $header */
            $content = $header->text();
            $this->assertArrayHasKey($content, $this->defaultGroups['header']);
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
                    $this->assertArrayHasKey($content, $this->defaultGroups[$id]);
                }
            }
            $checks = $checks + 1;
        }
        $this->assertGreaterThanOrEqual(count($this->defaultGroups)-1, $checks);
    }

    public function testGroupAdd()
    {
        $randomPrefix = ToolsAPI::randomGen(5);

        $login = $this->login();
        $groups = $login->openGroups('Oro\Bundle\UserBundle')
            ->add()
            ->setName($this->newGroup['NAME'] . $randomPrefix)
            ->setOwner('Main')
            //->setRoles(array($this->newGroup['ROLE']))
            ->save()
            ->assertMessage('Group saved')
            ->close();

        $this->assertTrue($groups->entityExists(array('name' => $this->newGroup['NAME'] . $randomPrefix)));

        return $randomPrefix;
    }

    /**
     * @depends testGroupAdd
     * @param $randomPrefix
     */
    public function testGroupDelete($randomPrefix)
    {
        $login = $this->login();
        $groups = $login->openGroups('Oro\Bundle\UserBundle');
        $groups->deleteEntity(array('name' => $this->newGroup['NAME'] . $randomPrefix));
        $this->assertFalse($groups->entityExists(array('name' => $this->newGroup['NAME'] . $randomPrefix)));
    }
}
