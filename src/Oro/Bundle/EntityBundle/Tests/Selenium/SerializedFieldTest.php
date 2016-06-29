<?php

namespace Oro\Bundle\EntityBundle\Tests\Selenium;

use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntity;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class SerializedFieldTest
 *
 * @package Oro\Bundle\EntityBundle\Tests\Selenium
 */
class SerializedFieldTest extends Selenium2TestCase
{
    protected $fields = array(
        array('type' => 'BigInt', 'value' => '123456789'),
        array('type' => 'Boolean', 'value' => 'Yes'),
        array('type' => 'Currency', 'value' => '$100.00'),
        array('type' => 'Date', 'value' => 'Apr 9, 2014'),
        array('type' => 'DateTime', 'value' => 'Dec 25, 2014, 12:15 AM'),
        array('type' => 'Decimal', 'value' => '0.55'),
        array('type' => 'Float', 'value' => '500.1'),
        array('type' => 'Integer', 'value' => '100500'),
        array('type' => 'Percent', 'value' => '50%'),
        array('type' => 'SmallInt', 'value' => '1'),
        array('type' => 'String', 'value' => 'Some string value'),
        array('type' => 'Text', 'value' => 'Some text value')
    );

    public function testAddSerializedField()
    {
        $login = $this->login();
        /** @var ConfigEntities $login */
        $login = $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', 'User', 'is equal to')
            ->open(array('User'));
        foreach ($this->fields as $field) {
            /** @var ConfigEntity $login */
            $login->createField()
            ->setFieldName(strtolower($field['type']).'_serialized')
            ->setStorageType('Serialized field')
            ->setType($field['type'])
            ->proceed()
            ->save()
            ->assertMessage('Field saved');
        }
    }

    /**
     * @depends testAddSerializedField
     */
    public function testEntityFieldsAvailability()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', 'admin')
            ->open(array('admin'))
            ->edit();
        /** @var ConfigEntity $login */
        $login = $login->openConfigEntity('Oro\Bundle\EntityConfigBundle');
        foreach ($this->fields as $field) {
            $login->checkEntityField(strtolower($field['type']).'_serialized');
        }
    }

    /**
     * @depends testEntityFieldsAvailability
     */
    public function testEntityFieldDataSave()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', 'admin')
            ->open(array('admin'))
            ->edit();
        /** @var ConfigEntity $login */
        $login = $login->openConfigEntity('Oro\Bundle\EntityConfigBundle');
        foreach ($this->fields as $field) {
            $login->setCustomField(strtolower($field['type']).'_serialized', trim($field['value'], '$%'));
        }
        $login->save()
            ->assertMessage('User saved');
        foreach ($this->fields as $field) {
            $login->checkEntityFieldData(strtolower($field['type']).'_serialized', $field['value'], $field['type']);
        }
    }
}
