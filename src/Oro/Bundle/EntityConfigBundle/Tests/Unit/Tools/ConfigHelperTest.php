<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class ConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey($expected, $propertyName, $className, $fieldName)
    {
        $result = ConfigHelper::getTranslationKey($propertyName, $className, $fieldName);
        $this->assertEquals($expected, $result);
    }

    public function getTranslationKeyProvider()
    {
        return [
            ['oro.somesomeclass.entity_label', 'label', 'Oro\SomeBundle\SomeClass', null],
            ['oro.somesomeclass.entity_plural_label', 'plural_label', 'Oro\SomeBundle\SomeClass', null],
            ['oro.somesomeclass.some_field.label', 'label', 'Oro\SomeBundle\SomeClass', 'someField'],
            ['oro.somesomeclass.some_field.plural_label', 'plural_label', 'Oro\SomeBundle\SomeClass', 'someField'],
        ];
    }
}
