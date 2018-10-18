<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\Tests\Unit\TestArrayObject;

class ConfigUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertObjectsToArrayDoNotTreatEmptyAsNull()
    {
        $objects = [
            'obj1' => new TestArrayObject([]),
            'obj2' => new TestArrayObject(['key' => 'val'])
        ];

        $expected = [
            'obj2' => ['key' => 'val']
        ];

        self::assertEquals($expected, ConfigUtil::convertObjectsToArray($objects));
    }

    public function testConvertObjectsToArrayTreatEmptyAsNull()
    {
        $objects = [
            'obj1' => new TestArrayObject([]),
            'obj2' => new TestArrayObject(['key' => 'val'])
        ];

        $expected = [
            'obj1' => null,
            'obj2' => ['key' => 'val']
        ];

        self::assertEquals($expected, ConfigUtil::convertObjectsToArray($objects, true));
    }

    public function testBuildMetaPropertyName()
    {
        self::assertEquals('__name__', ConfigUtil::buildMetaPropertyName('name'));
    }

    public function testGetPropertyPathOfMetaPropertyForEmptyConfig()
    {
        $config = new EntityDefinitionConfig();

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('name', $config));
    }

    public function testGetPropertyPathOfMetaPropertyWhenMetaPropertyDoesNotExist()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__')->setMetaProperty(true);

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field2', $config));
    }

    public function testGetPropertyPathOfMetaPropertyWhenFoundItemIsFieldNotMetaProperty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__');

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingMetaProperty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__')->setMetaProperty(true);

        self::assertEquals('__field1__', ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingMetaPropertyWithPropertyPath()
    {
        $config = new EntityDefinitionConfig();
        $field = $config->addField('someField');
        $field->setMetaProperty(true);
        $field->setPropertyPath('__field1__');

        self::assertEquals('__field1__', ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingExcludedMetaProperty()
    {
        $config = new EntityDefinitionConfig();
        $field = $config->addField('__field1__');
        $field->setMetaProperty(true);
        $field->setExcluded();

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }
}
