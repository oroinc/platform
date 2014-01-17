<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

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
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->add()
            ->assertTitle('New Entity - Entities - System')
            ->setName($entityName)
            ->setLabel($entityName)
            ->setPluralLabel($entityName)
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setFieldName('Test_field')
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
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
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            //->filterBy('Label', $entityName)
            ->open(array($entityName))
            ->edit()
            ->setLabel($newEntityName)
            ->save()
            ->assertMessage('Entity saved')
            ->assertTitle($newEntityName .' - Entities - System')
            ->createField()
            ->setFieldName('Test_field2')
            ->setType('Integer')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema();

        return $newEntityName;
    }

    /**
     * @depends testUpdateEntity
     * @param $entityName
     */
    public function testEntityFieldsAvailability($entityName)
    {
        $login = $this->login();
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entityName)
            ->open()
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->checkEntityField('Test_field')
            ->checkEntityField('Test_field2');
    }

    /**
     * @depends testUpdateEntity
     * @param $entityName
     */
    public function testDeleteEntity($entityName)
    {
        $login = $this->login();
        $entityExist = $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            //->filterBy('Label', $entityName)
            ->deleteEntity(array($entityName), 'Remove')
            ->assertMessage('Item deleted')
            ->open(array($entityName))
            ->updateSchema()
            ->close()
            ->entityExists(array($entityName));

        $this->assertFalse($entityExist);
    }
}
