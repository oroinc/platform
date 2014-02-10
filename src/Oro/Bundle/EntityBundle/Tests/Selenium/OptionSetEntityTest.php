<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class OptionSetEntityTest extends Selenium2TestCase
{
    /**
     * @return array
     */
    public function testCreateOptionSetEntity()
    {
        $this->markTestIncomplete('Due to BAP-2966');
        $entityData = array(
            'entityName' => 'optionset'.mt_rand(),
            'stringField' => 'string_field',
            'optionSetField' => 'option_set'
        );

        $options = array('1','2','3','4','5','6','7','8','9');

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
            ->setFieldName($entityData['optionSetField'])
            ->setType('Option set')
            ->proceed()
            ->addOptions($options)
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->close();

        return $entityData;
    }

    /**
     * @depends testCreateOptionSetEntity
     * @param $entityData
     */
    public function testCreateNewOptionSetEntityRecord($entityData)
    {
        //TODO: complete test when BAP-2966 will be fixed
        $this->markTestIncomplete('Due to BAP-2966');
        $login = $this->login();
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entityData['entityName'])
            ->open()
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->setStringField($entityData['stringField'], 'Some test text')
            ->setOptionSetField()
            ->save()
            ->assertMessage('Entity saved');
    }
}
