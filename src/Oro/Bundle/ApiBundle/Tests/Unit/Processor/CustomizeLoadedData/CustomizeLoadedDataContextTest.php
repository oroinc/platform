<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;

class CustomizeLoadedDataContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomizeLoadedDataContext */
    private $context;

    protected function setUp()
    {
        $this->context = new CustomizeLoadedDataContext();
    }

    public function testRootClassName()
    {
        self::assertNull($this->context->getRootClassName());

        $className = 'Test\Class';
        $this->context->setRootClassName($className);
        self::assertEquals($className, $this->context->getRootClassName());
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $className = 'Test\Class';
        $this->context->setClassName($className);
        self::assertEquals($className, $this->context->getClassName());
    }

    public function testPropertyPath()
    {
        self::assertNull($this->context->getPropertyPath());

        $propertyPath = 'field1.field11';
        $this->context->setPropertyPath($propertyPath);
        self::assertEquals($propertyPath, $this->context->getPropertyPath());
    }

    public function testRootConfig()
    {
        self::assertNull($this->context->getRootConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setRootConfig($config);
        self::assertSame($config, $this->context->getRootConfig());

        $this->context->setRootConfig(null);
        self::assertNull($this->context->getRootConfig());
    }

    public function testConfig()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setConfig(null);
        self::assertNull($this->context->getConfig());
    }

    public function testIdentifierOnly()
    {
        self::assertFalse($this->context->isIdentifierOnly());

        $this->context->setIdentifierOnly(true);
        self::assertTrue($this->context->isIdentifierOnly());

        $this->context->setIdentifierOnly(false);
        self::assertFalse($this->context->isIdentifierOnly());
    }

    public function testGetResultFieldNameWithoutConfig()
    {
        $propertyPath = 'test';
        self::assertNull($this->context->getResultFieldName($propertyPath));
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath, true));
    }

    public function testGetResultFieldNameWhenFieldDoesNotExist()
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertNull($this->context->getResultFieldName($propertyPath));
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath, true));
    }

    public function testGetResultFieldNameForNotRenamedField()
    {
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($propertyPath);
        $this->context->setConfig($config);
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath));
        self::assertEquals($propertyPath, $this->context->getResultFieldName($propertyPath, true));
    }

    public function testGetResultFieldNameForRenamedField()
    {
        $fieldName = 'renamedTest';
        $propertyPath = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyPath);
        $this->context->setConfig($config);
        self::assertEquals($fieldName, $this->context->getResultFieldName($propertyPath));
        self::assertEquals($fieldName, $this->context->getResultFieldName($propertyPath, true));
    }

    public function testGetResultFieldValueWithoutConfig()
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        self::assertNull($this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueWhenFieldDoesNotExist()
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertNull($this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueForNotRenamedField()
    {
        $propertyName = 'test';
        $data = [$propertyName => 'test value'];
        $config = new EntityDefinitionConfig();
        $config->addField($propertyName);
        $this->context->setConfig($config);
        self::assertEquals($data[$propertyName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueForRenamedField()
    {
        $fieldName = 'renamedTest';
        $propertyName = 'test';
        $data = [$fieldName => 'test value'];
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyName);
        $this->context->setConfig($config);
        self::assertEquals($data[$fieldName], $this->context->getResultFieldValue($propertyName, $data));
    }

    public function testGetResultFieldValueWhenDataDoesNotHaveFieldValue()
    {
        $fieldName = 'renamedTest';
        $propertyName = 'test';
        $data = [];
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($propertyName);
        $this->context->setConfig($config);
        self::assertNull($this->context->getResultFieldValue($propertyName, $data));
    }

    public function testIsFieldRequestedWithoutConfig()
    {
        $fieldName = 'test';
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldDoesNotExist()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldExists()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertTrue($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertTrue($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsFieldRequestedWhenFieldExcluded()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $this->context->setConfig($config);
        self::assertFalse($this->context->isFieldRequested($fieldName));
        self::assertFalse($this->context->isFieldRequested($fieldName, [$fieldName => 'value']));
        self::assertFalse($this->context->isFieldRequested($fieldName, ['another' => 'value']));
    }

    public function testIsAtLeastOneFieldRequestedWithoutConfig()
    {
        $fieldName = 'test';
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], ['test1' => 'value'])
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenFieldsDoNotExist()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(['anotherField', $fieldName], ['test1' => 'value'])
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenFieldsExists()
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $config->addField($anotherFieldName);
        $this->context->setConfig($config);
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenAllFieldsExcluded()
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $config->addField($anotherFieldName)->setExcluded();
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedWhenOneFieldExcluded()
    {
        $fieldName = 'test';
        $anotherFieldName = 'anotherField';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setExcluded();
        $config->addField($anotherFieldName);
        $this->context->setConfig($config);
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$fieldName => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], [$anotherFieldName => 'value'])
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequested([$anotherFieldName, $fieldName], ['test1' => 'value'])
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequested(
                [$anotherFieldName, $fieldName],
                [$anotherFieldName => 'value', $fieldName => 'value']
            )
        );
    }

    public function testIsFieldRequestedForCollection()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                []
            )
        );
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value']
                ]
            )
        );
        self::assertFalse(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value1'],
                    [$fieldName => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    ['another' => 'value']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    ['another' => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isFieldRequestedForCollection(
                $fieldName,
                [
                    [$fieldName => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
    }

    public function testIsAtLeastOneFieldRequestedForCollection()
    {
        $fieldName = 'test';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);
        $this->context->setConfig($config);
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                []
            )
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value']
                ]
            )
        );
        self::assertFalse(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value1'],
                    [$fieldName => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    ['another' => 'value']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    ['another' => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
        self::assertTrue(
            $this->context->isAtLeastOneFieldRequestedForCollection(
                [$fieldName],
                [
                    [$fieldName => 'value1'],
                    ['another' => 'value2']
                ]
            )
        );
    }

    public function testGetIdentifierValues()
    {
        self::assertEquals(
            [],
            $this->context->getIdentifierValues(
                [],
                'id'
            )
        );
        self::assertEquals(
            [1, 2],
            $this->context->getIdentifierValues(
                [
                    ['id' => 1, 'name' => 'value1'],
                    ['id' => 2, 'name' => 'value2']
                ],
                'id'
            )
        );
    }
}
