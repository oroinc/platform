<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntity;
use Oro\Bundle\NavigationBundle\Tests\Selenium\Pages\Navigation;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class EntityTest
 *
 * @package Oro\Bundle\EntityBundle\Tests\Selenium
 */
class EntityTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateEntity()
    {
        $entityName = 'Entity'.mt_rand();

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->assertTitle('All - Entity Management - Entities - System')
            ->add()
            ->assertTitle('New Entity - Entity Management - Entities - System')
            ->setName($entityName)
            ->setLabel($entityName)
            ->setPluralLabel($entityName)
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setStorageType('Table column')
            ->setFieldName('test_field')
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->close();

        return $entityName;
    }

    /**
     * @depends testCreateEntity
     * @param $entityName
     * @return string
     */
    public function testUpdateEntity($entityName)
    {
        $newEntityName = 'Update' . $entityName;
        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName)
            ->open(array($entityName))
            ->edit()
            ->setLabel($newEntityName)
            ->save()
            ->assertMessage('Entity saved')
            ->assertTitle($newEntityName .' - Entity Management - Entities - System')
            ->createField()
            ->setStorageType('Table column')
            ->setFieldName('test_field2')
            ->setType('Integer')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated');

        return $newEntityName;
    }

    /**
     * @depends testUpdateEntity
     * @param $entityName
     */
    public function testEntityFieldsAvailability($entityName)
    {
        $login = $this->login();
        /** @var Navigation $login */
        $login = $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entityName);
            /** @var ConfigEntity $login */
        $login->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->checkEntityField('test_field')
            ->checkEntityField('test_field2');
    }

    /**
     * @depends testCreateEntity
     * @depends testUpdateEntity
     * @param $entityName
     * @param $entityUpdateName
     */
    public function testDeleteEntity($entityName, $entityUpdateName)
    {
        $login = $this->login();
        /** @var ConfigEntities $login */
        $entityExist = $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName)
            ->delete(array($entityName), 'Remove')
            ->assertMessage('Item deleted')
            ->open(array($entityName))
            ->assertTitle($entityUpdateName . ' - Entity Management - Entities - System')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->assertTitle('All - Entity Management - Entities - System')
            ->close()
            ->entityExists(array($entityName));

        static::assertFalse($entityExist);
    }
}
