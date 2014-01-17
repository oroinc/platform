<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class BusinessUnitsTest
 *
 * @package Oro\Bundle\OrganizationBundle\Tests\Selenium
 */
class BusinessUnitsTest extends Selenium2TestCase
{
    /**
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
     * @depends testCreateBusinessUnit
     * @param $unitName
     * @return string
     */
    public function testUpdateBusinessUnit($unitName)
    {
        $newUnitName = 'Update_' . $unitName;
        $login = $this->login();
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->open(array($unitName))
            ->edit()
            ->setBusinessUnitName($newUnitName)
            ->save()
            ->assertMessage('Business Unit saved')
            ->toGrid()
            ->assertTitle('Business Units - Users Management - System');

        return $newUnitName;
    }

    /**
     * @depends testUpdateBusinessUnit
     * @param $unitName
     */
    public function testDeleteBusinessUnit($unitName)
    {
        $login = $this->login();
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->open(array($unitName))
            ->delete()
            ->assertTitle('Business Units - Users Management - System')
            ->assertMessage('Business Unit deleted');
    }
}
