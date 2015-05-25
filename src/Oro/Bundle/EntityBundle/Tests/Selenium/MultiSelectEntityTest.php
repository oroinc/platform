<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntity;
use Oro\Bundle\NavigationBundle\Tests\Selenium\Pages\Navigation;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class MultiSelectEntityTest extends Selenium2TestCase
{
    /**
     * @return array
     */
    public function testCreateMultiSelectEntity()
    {
        $entityData = array(
            'entityName' => 'multiSelect'.mt_rand(),
            'stringField' => 'string_field',
            'multiSelectField' => 'multiSelect',
            'options' =>  array('first','second','third','fourth','fifth')
        );

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->add()
            ->assertTitle('New Entity - Entity Management - Entities - System')
            ->setName($entityData['entityName'])
            ->setLabel($entityData['entityName'])
            ->setPluralLabel($entityData['entityName'])
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setFieldName($entityData['stringField'])
            ->setStorageType('Table column')
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->createField()
            ->setFieldName($entityData['multiSelectField'])
            ->setStorageType('Table column')
            ->setType('Multi-Select')
            ->proceed()
            ->addMultiSelectOptions($entityData['options'])
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->close();

        return $entityData;
    }

    /**
     * @depends testCreateMultiSelectEntity
     * @param $entityData
     */
    public function testCreateNewMultiSelectEntityRecord($entityData)
    {
        $login = $this->login();
        /** @var Navigation $login */
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entityData['entityName']);
        /** @var ConfigEntity $login */
        $login->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->setStringField($entityData['stringField'], 'Some test text')
            ->setMultiSelectField($entityData['multiSelectField'], $entityData['options'])
            ->save()
            ->assertMessage('Entity saved');
    }
}
