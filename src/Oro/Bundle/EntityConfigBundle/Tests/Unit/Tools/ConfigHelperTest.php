<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class ConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isConfigModelEntityProvider
     */
    public function testIsConfigModelEntity($className, $expected)
    {
        $result = ConfigHelper::isConfigModelEntity($className);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey($expected, $propertyName, $className, $fieldName)
    {
        $result = ConfigHelper::getTranslationKey($propertyName, $className, $fieldName);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getModuleAndEntityNamesProvider
     */
    public function testGetModuleAndEntityNames($className, $expectedModuleName, $expectedEntityName)
    {
        list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($className);
        $this->assertEquals($expectedModuleName, $moduleName);
        $this->assertEquals($expectedEntityName, $entityName);
    }

    public function isConfigModelEntityProvider()
    {
        return [
            ['Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel', true],
            ['Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel', true],
            ['Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel', true],
            ['Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue', true],
            ['Test\Other', false],
        ];
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

    public function getModuleAndEntityNamesProvider()
    {
        return [
            ['Oro\SomeBundle\SomeClass', 'OroSomeBundle', 'SomeClass'],
            ['Oro\Bundle\SomeBundle\Entity\SomeClass', 'OroSomeBundle', 'SomeClass'],
            ['Acme\SomeBundle\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundle\SomeBundle\Entity\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundles\SomeBundle\Entity\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundle\SomeBundle\Entities\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundles\SomeBundle\Entities\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Gedmo\Translatable\Entity\Translation', 'GedmoTranslatable', 'Translation'],
            ['JMS\JobQueueBundle\Entity\Job', 'JMSJobQueueBundle', 'Job'],
            ['Extend\Entity\SomeClass', 'System', 'SomeClass'],
            ['Acme\Entity\SomeClass', 'System', 'SomeClass'],
            ['Acme\Entities\SomeClass', 'System', 'SomeClass'],
            ['Acme\SomeClass', 'System', 'SomeClass'],
            ['SomeClass', 'System', 'SomeClass'],
            ['\SomeClass', 'System', 'SomeClass'],
            ['', '', ''],
            [null, '', ''],
        ];
    }
}
