<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class RelatedEntityTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateRelatedEntity()
    {
        $entityData = array(
            'entityName' => 'onetomany'.mt_rand(),
            'stringField' => 'string_field',
            'relationField' => 'one_to_many_field'
        );

        $login = $this->login();
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->add()
            ->assertTitle('New Entity - Entities - System')
            ->setName($entityData['entityName'])
            ->setLabel($entityData['entityName'])
            ->setPluralLabel($entityData['entityName'])
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setFieldName($entityData['stringField'])
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->createField()
            ->setFieldName($entityData['relationField'])
            ->setType('Relation one to many')
            ->proceed()
            ->setTargetEntity('OroUserBundle:User')
            ->setRelation('Related entity data fields', array('First name', 'Last name'))
            ->setRelation('Related entity info title', array('First name', 'Last name'))
            ->setRelation('Related entity detailed', array('First name', 'Last name'))
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->close();

        return $entityData;
    }

    /**
     * @depends testCreateRelatedEntity
     * @param $entityData
     */
    public function testCreateNewEntityRecord($entityData)
    {
        $login = $this->login();
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entityData['entityName'])
            ->open()
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->setStringField($entityData['stringField'], 'Some test text')
            ->addRelation($entityData['relationField'])
            ->selectEntity(array('John', 'Doe'))
            ->confirmSelection()
            ->save()
            ->assertMessage('Entity saved');
    }
}
