<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\Tests\Unit\TestArrayObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertObjectsToArrayDoNotTreatEmptyAsNull(): void
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

    public function testConvertObjectsToArrayTreatEmptyAsNull(): void
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

    public function testBuildMetaPropertyName(): void
    {
        self::assertEquals('__name__', ConfigUtil::buildMetaPropertyName('name'));
    }

    public function testGetPropertyPathOfMetaPropertyForEmptyConfig(): void
    {
        $config = new EntityDefinitionConfig();

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('name', $config));
    }

    public function testGetPropertyPathOfMetaPropertyWhenMetaPropertyDoesNotExist(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__')->setMetaProperty(true);

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field2', $config));
    }

    public function testGetPropertyPathOfMetaPropertyWhenFoundItemIsFieldNotMetaProperty(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__');

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingMetaProperty(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('__field1__')->setMetaProperty(true);

        self::assertEquals('__field1__', ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingMetaPropertyWithPropertyPath(): void
    {
        $config = new EntityDefinitionConfig();
        $field = $config->addField('someField');
        $field->setMetaProperty(true);
        $field->setPropertyPath('__field1__');

        self::assertEquals('__field1__', ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetPropertyPathOfMetaPropertyForExistingExcludedMetaProperty(): void
    {
        $config = new EntityDefinitionConfig();
        $field = $config->addField('__field1__');
        $field->setMetaProperty(true);
        $field->setExcluded();

        self::assertNull(ConfigUtil::getPropertyPathOfMetaProperty('field1', $config));
    }

    public function testGetAssociationTargetTypeForSingleValuedAssociation(): void
    {
        self::assertEquals(ConfigUtil::TO_ONE, ConfigUtil::getAssociationTargetType(false));
    }

    public function testGetAssociationTargetTypeForCollectionValuedAssociation(): void
    {
        self::assertEquals(ConfigUtil::TO_MANY, ConfigUtil::getAssociationTargetType(true));
    }
}
