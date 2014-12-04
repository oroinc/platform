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
    /**
     * @return string
     */
    public function testAddSerializedField()
    {
        $fields = array('BigInt', 'Boolean', 'Currency', 'Date', 'DateTime', 'Decimal', 'Float', 'Integer',
        'Percent', 'SmallInt', 'String', 'Text');

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login = $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', 'User', 'is equal to')
            ->open(array('User'));
        foreach($fields as $field) {
            /** @var ConfigEntity $login */
                $login->createField()
                ->setFieldName(strtolower($field).'_field')
                ->setStorageType('Serialized field')
                ->setType($field)
                ->proceed()
                ->save()
                ->assertMessage('Field saved');
        }
        return $fields;
    }

    /**
     * @depends testAddSerializedField
     * @param $fields
     */
    public function testEntityFieldsAvailability($fields)
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', 'admin')
            ->open(array('admin'))
            ->edit()
            /** @var ConfigEntities $login */
            ->openConfigEntity('Oro\Bundle\EntityConfigBundle');
        foreach($fields as $field) {
            /** @var ConfigEntity $login */
            $login->checkEntityField(strtolower($field).'_field');
        }
    }
}
