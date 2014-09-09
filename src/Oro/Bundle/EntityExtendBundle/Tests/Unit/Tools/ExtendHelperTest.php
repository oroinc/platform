<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getReverseRelationTypeProvider
     */
    public function testGetReverseRelationType($type, $expectedType)
    {
        $this->assertEquals(
            $expectedType,
            ExtendHelper::getReverseRelationType($type)
        );
    }

    public static function getReverseRelationTypeProvider()
    {
        return [
            ['oneToMany', 'manyToOne'],
            ['manyToOne', 'oneToMany'],
            ['manyToMany', 'manyToMany'],
            ['other', 'other'],
        ];
    }

    /**
     * @dataProvider buildAssociationNameProvider
     */
    public function testBuildAssociationName($targetEntityClassName, $associationKind, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::buildAssociationName($targetEntityClassName, $associationKind)
        );
    }

    public static function buildAssociationNameProvider()
    {
        return [
            ['Oro\Bundle\TestBundle\Entity\Test', null, 'test_d2f667e'],
            ['Oro\Bundle\TestBundle\Entity\Test', 'test', 'test_9a6fc24b'],
            ['Oro\Bundle\TestBundle\Entity\OtherTest', null, 'other_test_f1fe376e'],
            ['Oro\Bundle\TestBundle\Entity\OtherTest', 'test', 'other_test_14ac1fd7'],
            ['Acme\Bundle\TestBundle\Entity\Test', null, 'test_77981b51'],
            ['Acme\Bundle\TestBundle\Entity\Test', 'test', 'test_21bc9fd6'],
            ['Acme\Bundle\TestBundle\Entity\OtherTest', null, 'other_test_3efb8e13'],
            ['Acme\Bundle\TestBundle\Entity\OtherTest', 'test', 'other_test_8ca3d713'],
            ['Test', null, 'test_784dd132'],
            ['Test', 'test', 'test_4c5b140f'],
            ['OtherTest', null, 'other_test_f54366f8'],
            ['OtherTest', 'test', 'other_test_4ee028ce'],
        ];
    }

    public function testBuildRelationKey()
    {
        $this->assertEquals(
            'manyToOne|Test\Entity|Test\TargetEntity|testField',
            ExtendHelper::buildRelationKey('Test\Entity', 'testField', 'manyToOne', 'Test\TargetEntity')
        );
    }

    /**
     * @dataProvider buildEnumCodeProvider
     */
    public function testBuildEnumCode($enumName, $expectedEnumCode)
    {
        $this->assertEquals(
            $expectedEnumCode,
            ExtendHelper::buildEnumCode($enumName)
        );
    }

    public static function buildEnumCodeProvider()
    {
        return [
            ['test', 'test'],
            ['Test', 'test'],
            ['tēstà', function_exists('iconv') ? 'testa' : 'tst'],
            ['test123', 'test123'],
            ['test 123', 'test_123'],
            [' test 123 ', 'test_123'],
            ['test_123', 'test_123'],
            ['test___123', 'test_123'],
            ['test-123', 'test_123'],
            ['test---123', 'test_123'],
            ['test---___123', 'test_123'],
            ['test- - - _ _ _ 123', 'test_123'],
            ['test \/()[]~!@#$%^&*_+`', 'test_'],
        ];
    }

    /**
     * @dataProvider buildEnumCodeForInvalidEnumNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testBuildEnumCodeForInvalidEnumName($enumName)
    {
        ExtendHelper::buildEnumCode($enumName);
    }

    /**
     * @dataProvider buildEnumCodeForInvalidEnumNameProvider
     */
    public function testBuildEnumCodeForInvalidEnumNameIgnoreException($enumValueName)
    {
        $this->assertSame(
            '',
            ExtendHelper::buildEnumCode($enumValueName, false)
        );
    }

    public static function buildEnumCodeForInvalidEnumNameProvider()
    {
        return [
            [''],
            ['_'],
            ['-'],
            ['__'],
            ['_ _'],
            [' \/()[]~!@#$%^&*+-'],
        ];
    }

    /**
     * @dataProvider generateEnumCodeProvider
     */
    public function testGenerateEnumCode($entityClassName, $fieldName, $expectedEnumCode)
    {
        $this->assertEquals(
            $expectedEnumCode,
            ExtendHelper::generateEnumCode($entityClassName, $fieldName)
        );
    }

    public static function generateEnumCodeProvider()
    {
        return [
            ['Test\Entity', 'field1', 'entity_field1_489d47b1'],
            ['Oro\Bundle\EntityExtendBundle\Entity\EnumValue', 'foreignKey', 'enum_value_foreign_key_5e7e84e3'],
            ['Oro\Bundle\EntityExtendBundle\Entity\EnumValue', 'foreign_key', 'enum_value_foreign_key_f1145bcc'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $entityClassName must not be empty.
     */
    public function testGenerateEnumCodeForEmptyClassName()
    {
        ExtendHelper::generateEnumCode('', 'testField');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldName must not be empty.
     */
    public function testGenerateEnumCodeForEmptyFieldName()
    {
        ExtendHelper::generateEnumCode('Test\Entity', '');
    }

    /**
     * @dataProvider buildEnumValueIdProvider
     */
    public function testBuildEnumValueId($enumValueName, $expectedEnumValueId)
    {
        $enumValueId = ExtendHelper::buildEnumValueId($enumValueName);
        $this->assertEquals(
            $expectedEnumValueId,
            $enumValueId
        );
        $this->assertTrue(
            strlen($enumValueId) <= ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH,
            sprintf(
                'The enum value id must be less or equal than %d characters',
                ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH
            )
        );
    }

    public static function buildEnumValueIdProvider()
    {
        return [
            ['test', 'test'],
            ['Test', 'test'],
            ['tēstà', function_exists('iconv') ? 'testa' : 'tst'],
            ['test123', 'test123'],
            ['test 123', 'test_123'],
            [' test 123 ', 'test_123'],
            ['test_123', 'test_123'],
            ['test___123', 'test_123'],
            ['test-123', 'test_123'],
            ['test---123', 'test_123'],
            ['test---___123', 'test_123'],
            ['test- - - _ _ _ 123', 'test_123'],
            ['test \/()[]~!@#$%^&*_+`', 'test_'],
            ['01234567890123456789012345678901', '01234567890123456789012345678901'],
            ['012345678901234567890123456789012', '012345678901234567890123_226f1a9'],
            ['sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 'sed_do_eiusmod_tempor_i_a5e72088'],
        ];
    }

    /**
     * @dataProvider buildEnumValueIdForInvalidEnumValueNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testBuildEnumValueIdForInvalidEnumValueName($enumValueName)
    {
        ExtendHelper::buildEnumValueId($enumValueName);
    }

    /**
     * @dataProvider buildEnumValueIdForInvalidEnumValueNameProvider
     */
    public function testBuildEnumValueIdForInvalidEnumValueNameIgnoreException($enumValueName)
    {
        $this->assertSame(
            '',
            ExtendHelper::buildEnumValueId($enumValueName, false)
        );
    }

    public static function buildEnumValueIdForInvalidEnumValueNameProvider()
    {
        return [
            [''],
            ['_'],
            ['-'],
            ['__'],
            ['_ _'],
            [' \/()[]~!@#$%^&*+-'],
        ];
    }

    /**
     * @dataProvider buildEnumValueClassNameProvider
     */
    public function testBuildEnumValueClassName($enumCode, $expectedClassName)
    {
        $this->assertEquals(
            $expectedClassName,
            ExtendHelper::buildEnumValueClassName($enumCode)
        );
    }

    public static function buildEnumValueClassNameProvider()
    {
        return [
            ['test', ExtendConfigDumper::ENTITY . 'EV_Test'],
            ['test_123', ExtendConfigDumper::ENTITY . 'EV_Test_123'],
            ['test_enum', ExtendConfigDumper::ENTITY . 'EV_Test_Enum'],
            ['testenum', ExtendConfigDumper::ENTITY . 'EV_Testenum'],
        ];
    }

    public function testGetMultiEnumSnapshotFieldName()
    {
        $this->assertEquals(
            'testFieldSnapshot',
            ExtendHelper::getMultiEnumSnapshotFieldName('testField')
        );
    }

    /**
     * @dataProvider getEnumTranslationKeyProvider
     */
    public function testGetEnumTranslationKey($propertyName, $enumCode, $fieldName, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::getEnumTranslationKey($propertyName, $enumCode, $fieldName)
        );
    }

    public static function getEnumTranslationKeyProvider()
    {
        return [
            ['label', 'test_enum', null, 'oro.entityextend.enums.test_enum.entity_label'],
            ['label', 'test_enum', 'testField', 'oro.entityextend.enumvalue.testField.label'],
        ];
    }

    /**
     * @dataProvider isCustomEntityProvider
     */
    public function testIsCustomEntity($className, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::isCustomEntity($className)
        );
    }

    public static function isCustomEntityProvider()
    {
        return [
            ['Extend\Entity\Test', true],
            ['Acme\Bundle\TestBundle\Entity\Test', false],
        ];
    }

    /**
     * @dataProvider getShortClassNameProvider
     */
    public function testGetShortClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::getShortClassName($className)
        );
    }

    public static function getShortClassNameProvider()
    {
        return [
            ['Oro\Bundle\TestBundle\Entity\Test', 'Test'],
            ['Acme\Bundle\TestBundle\Entity\Test', 'Test'],
            ['Test', 'Test'],
        ];
    }
}
