<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class ConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey($expected, $className, $fieldName, $propertyName)
    {
        $result = ConfigHelper::getTranslationKey($className, $fieldName, $propertyName);
        $this->assertEquals($expected, $result);
    }

    public function getTranslationKeyProvider()
    {
        return [
            [null, null, null, null],
            ['oro.somesomeclass', 'Oro\SomeBundle\SomeClass', null, null],
            ['oro.somesomeclass.entity_label', 'Oro\SomeBundle\SomeClass', null, 'label'],
            ['oro.somesomeclass.entity_plural_label', 'Oro\SomeBundle\SomeClass', null, 'plural_label'],
            ['oro.somesomeclass.some_field', 'Oro\SomeBundle\SomeClass', 'someField', null],
            ['oro.somesomeclass.some_field.label', 'Oro\SomeBundle\SomeClass', 'someField', 'label'],
            ['oro.somesomeclass.some_field.plural_label', 'Oro\SomeBundle\SomeClass', 'someField', 'plural_label'],
        ];
    }
}
