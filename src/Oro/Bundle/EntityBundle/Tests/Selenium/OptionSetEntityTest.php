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
        $entitydata = array(
            'entityName' => 'OptionSet'.mt_rand(),
            'stringField' => 'String_field',
            'optionSetField' => 'Option_set'
        );

        $options = array('1','2','3','4','5','6','7','8','9');

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
            ->setFieldName($entitydata['optionSetField'])
            ->setType('Option set')
            ->proceed()
            ->addOptions($options)
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->assertMessage('Schema updated')
            ->close();

        return $entitydata;
    }

    /**
     * @depends testCreateOptionSetEntity
     * @param $entitydata
     */
    public function testCreateNewOptionSetEntityRecord($entitydata)
    {
        //TODO: complete test when BAP-2966 will be fixed
        $this->markTestIncomplete('Due to BAP-2966');
        $login = $this->login();
        $login->openNavigation('Oro\Bundle\NavigationBundle')
            ->tab('System')
            ->menu('Entities')
            ->menu($entitydata['entityName'])
            ->open()
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle')
            ->newCustomEntityAdd()
            ->setStringField($entitydata['stringField'], 'Some test text')
            ->setOptionSetField()
            ->save()
            ->assertMessage('Entity saved');
    }
}
