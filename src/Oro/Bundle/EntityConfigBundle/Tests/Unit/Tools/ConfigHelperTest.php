<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Gedmo\Translatable\Entity\Translation;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isConfigModelEntityProvider
     */
    public function testIsConfigModelEntity(string $className, bool $expected)
    {
        $result = ConfigHelper::isConfigModelEntity($className);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getTranslationKeyProvider
     */
    public function testGetTranslationKey(
        string $expected,
        string $scope,
        string $propertyName,
        string $className,
        ?string $fieldName
    ) {
        $result = ConfigHelper::getTranslationKey($scope, $propertyName, $className, $fieldName);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getModuleAndEntityNamesProvider
     */
    public function testGetModuleAndEntityNames(
        ?string $className,
        string $expectedModuleName,
        string $expectedEntityName
    ) {
        [$moduleName, $entityName] = ConfigHelper::getModuleAndEntityNames($className);
        $this->assertEquals($expectedModuleName, $moduleName);
        $this->assertEquals($expectedEntityName, $entityName);
    }

    public function isConfigModelEntityProvider(): array
    {
        return [
            [ConfigModel::class, true],
            [EntityConfigModel::class, true],
            [FieldConfigModel::class, true],
            [ConfigModelIndexValue::class, true],
            ['Test\Other', false],
        ];
    }

    public function getTranslationKeyProvider(): array
    {
        return [
            [
                'oro.some.someclass.entity_label',
                'entity',
                'label',
                'Oro\SomeBundle\SomeClass',
                null
            ],
            [
                'oro.some.someclass.entity_test_label',
                'test',
                'label',
                'Oro\SomeBundle\SomeClass',
                null
            ],
            [
                'oro.some.someclass.entity_plural_label',
                'entity',
                'plural_label',
                'Oro\SomeBundle\SomeClass',
                null
            ],
            [
                'oro.some.someclass.entity_test_plural_label',
                'test',
                'plural_label',
                'Oro\SomeBundle\SomeClass',
                null
            ],
            [
                'oro.some.someclass.some_field.label',
                'entity',
                'label',
                'Oro\SomeBundle\SomeClass',
                'someField'
            ],
            [
                'oro.some.someclass.some_field.test_label',
                'test',
                'label',
                'Oro\SomeBundle\SomeClass',
                'someField'
            ],
            [
                'oro.some.someclass.some_field.plural_label',
                'entity',
                'plural_label',
                'Oro\SomeBundle\SomeClass',
                'someField'
            ],
            [
                'oro.some.someclass.some_field.test_plural_label',
                'test',
                'plural_label',
                'Oro\SomeBundle\SomeClass',
                'someField'
            ],
        ];
    }

    public function getModuleAndEntityNamesProvider(): array
    {
        return [
            ['Oro\SomeBundle\SomeClass', 'OroSomeBundle', 'SomeClass'],
            ['Oro\Bundle\SomeBundle\Entity\SomeClass', 'OroSomeBundle', 'SomeClass'],
            ['Acme\SomeBundle\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundle\SomeBundle\Entity\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundles\SomeBundle\Entity\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundle\SomeBundle\Entities\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            ['Acme\Bundles\SomeBundle\Entities\SomeClass', 'AcmeSomeBundle', 'SomeClass'],
            [Translation::class, 'GedmoTranslatable', 'Translation'],
            [User::class, 'OroUserBundle', 'User'],
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
