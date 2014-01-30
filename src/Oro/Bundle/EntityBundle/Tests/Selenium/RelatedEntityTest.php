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
        $entitydata = array(
            'entityName' => 'OneToMany'.mt_rand(),
            'stringField' => 'string_field',
            'relationField' => 'one_to_many_field'
        );

        $login = $this->login();
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->add()
            ->assertTitle('New Entity - Entities - System')
            ->setName($entitydata['entityName'])
            ->setLabel($entitydata['entityName'])
            ->setPluralLabel($entitydata['entityName'])
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setFieldName($entitydata['stringField'])
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->createField()
            ->setFieldName($entitydata['relationField'])
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

        return $entitydata;
    }

    /**
     * @depends testCreateRelatedEntity
     * @param $entitydata
     */
    public function testCreateNewEntityRecord($entitydata)
    {
        $login = $this->login();
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entitydata['entityName'])
            ->open()
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->setStringField($entitydata['stringField'], 'Some test text')
            ->addRelation($entitydata['relationField'])
            ->selectEntity(array('John', 'Doe'))
            ->confirmSelection()
            ->save()
            ->assertMessage('Entity saved');
    }
}
