<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Selenium;

use Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Selenium\Pages\BusinessUnits;
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
        /* @var BusinessUnits $login */
        $login = $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->assertTitle('All - Business Units - User Management - System')
            ->add()
            ->assertTitle('Create Business Unit - Business Units - User Management - System');
        /* @var BusinessUnit $login */
        $login->setBusinessUnitName($unitName)
            ->setOrganization('OroCRM')
            ->save()
            ->assertMessage('Business Unit saved')
            ->toGrid()
            ->assertTitle('All - Business Units - User Management - System')
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
        /* @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->open(array($unitName))
            ->assertTitle("{$unitName} - Business Units - User Management - System")
            ->edit()
            ->setBusinessUnitName($newUnitName)
            ->save()
            ->assertMessage('Business Unit saved')
            ->toGrid()
            ->assertTitle('All - Business Units - User Management - System');

        return $newUnitName;
    }

    /**
     * @depends testUpdateBusinessUnit
     * @param $unitName
     */
    public function testDeleteBusinessUnit($unitName)
    {
        $login = $this->login();
        /* @var BusinessUnits $login */
        $login->openBusinessUnits('Oro\Bundle\OrganizationBundle')
            ->filterBy('Name', $unitName)
            ->open(array($unitName))
            ->assertTitle("{$unitName} - Business Units - User Management - System")
            ->delete()
            ->assertTitle('All - Business Units - User Management - System')
            ->assertMessage('Business Unit deleted');
    }
}
