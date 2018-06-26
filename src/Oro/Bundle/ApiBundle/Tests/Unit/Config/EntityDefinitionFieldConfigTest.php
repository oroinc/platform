<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class EntityDefinitionFieldConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testExcluded()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals([], $config->toArray());
    }

    public function testPropertyPath()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        self::assertTrue($config->hasPropertyPath());
        self::assertEquals('path', $config->getPropertyPath());
        self::assertEquals('path', $config->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));
        self::assertEquals([], $config->toArray());
    }

    public function testDescription()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());

        $config->setDescription('text');
        self::assertTrue($config->hasDescription());
        self::assertEquals('text', $config->getDescription());
        self::assertEquals(['description' => 'text'], $config->toArray());

        $config->setDescription(null);
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());

        $config->setDescription('text');
        $config->setDescription('');
        self::assertFalse($config->hasDescription());
        self::assertNull($config->getDescription());
        self::assertEquals([], $config->toArray());
    }

    public function testDataType()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());
        self::assertTrue($config->isEmpty());

        $config->setDataType('string');
        self::assertTrue($config->hasDataType());
        self::assertEquals('string', $config->getDataType());
        self::assertFalse($config->isEmpty());

        $config->setDataType(null);
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());
        self::assertTrue($config->isEmpty());
    }

    public function testDirection()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());

        $config->setDirection('input-only');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertFalse($config->isOutput());
        self::assertEquals(['direction' => 'input-only'], $config->toArray());

        $config->setDirection('output-only');
        self::assertTrue($config->hasDirection());
        self::assertFalse($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'output-only'], $config->toArray());

        $config->setDirection('bidirectional');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'bidirectional'], $config->toArray());

        $config->setDirection(null);
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals([], $config->toArray());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The possible values for the direction are "input-only", "output-only" or "bidirectional".
     */
    // @codingStandardsIgnoreEnd
    public function testSetInvalidDirection()
    {
        $config = new EntityDefinitionFieldConfig();

        $config->setDirection('another');
    }

    public function testMetaProperty()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->isMetaProperty());

        $config->setMetaProperty(true);
        self::assertTrue($config->isMetaProperty());
        self::assertEquals(['meta_property' => true], $config->toArray());

        $config->setMetaProperty(false);
        self::assertFalse($config->isMetaProperty());
        self::assertEquals([], $config->toArray());
    }

    public function testGetOrCreateTargetEntity()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetEntity());
        self::assertNull($config->getTargetEntity());

        $targetEntity = $config->getOrCreateTargetEntity();
        self::assertTrue($config->hasTargetEntity());
        self::assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->getOrCreateTargetEntity();
        self::assertSame($targetEntity, $targetEntity1);
        self::assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testCreateAndSetTargetEntity()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetEntity());
        self::assertNull($config->getTargetEntity());

        $targetEntity = $config->createAndSetTargetEntity();
        self::assertTrue($config->hasTargetEntity());
        self::assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->createAndSetTargetEntity();
        self::assertNotSame($targetEntity, $targetEntity1);
        self::assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testTargetClass()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getTargetClass());

        $config->setTargetClass('Test\Class');
        self::assertEquals('Test\Class', $config->getTargetClass());
        self::assertEquals(['target_class' => 'Test\Class'], $config->toArray());

        $config->setTargetClass(null);
        self::assertNull($config->getTargetClass());
        self::assertEquals([], $config->toArray());
    }

    public function testTargetType()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasTargetType());
        self::assertNull($config->getTargetType());
        self::assertNull($config->isCollectionValuedAssociation());

        $config->setTargetType('to-one');
        self::assertTrue($config->hasTargetType());
        self::assertEquals('to-one', $config->getTargetType());
        self::assertFalse($config->isCollectionValuedAssociation());
        self::assertEquals(['target_type' => 'to-one'], $config->toArray());

        $config->setTargetType('to-many');
        self::assertTrue($config->hasTargetType());
        self::assertEquals('to-many', $config->getTargetType());
        self::assertTrue($config->isCollectionValuedAssociation());
        self::assertEquals(['target_type' => 'to-many'], $config->toArray());

        $config->setTargetType(null);
        self::assertFalse($config->hasTargetType());
        self::assertNull($config->getTargetType());
        self::assertNull($config->isCollectionValuedAssociation());
        self::assertEquals([], $config->toArray());
    }

    public function testCollapsed()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasCollapsed());
        self::assertFalse($config->isCollapsed());

        $config->setCollapsed();
        self::assertTrue($config->hasCollapsed());
        self::assertTrue($config->isCollapsed());
        self::assertEquals(['collapse' => true], $config->toArray());

        $config->setCollapsed(false);
        self::assertTrue($config->hasCollapsed());
        self::assertFalse($config->isCollapsed());
        self::assertEquals([], $config->toArray());
    }

    public function testFormType()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getFormOptions());

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testSetFormOption()
    {
        $config = new EntityDefinitionFieldConfig();

        $config->setFormOption('option1', 'value1');
        $config->setFormOption('option2', 'value2');
        self::assertEquals(
            ['option1' => 'value1', 'option2' => 'value2'],
            $config->getFormOptions()
        );

        $config->setFormOption('option1', 'newValue');
        self::assertEquals(
            ['option1' => 'newValue', 'option2' => 'value2'],
            $config->getFormOptions()
        );
    }

    public function testFormConstraints()
    {
        $config = new EntityDefinitionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }

    public function testSetDataTransformers()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertFalse($config->hasDataTransformers());
        self::assertEquals([], $config->getDataTransformers());

        $config->setDataTransformers('service_id');
        self::assertTrue($config->hasDataTransformers());
        self::assertEquals(['service_id'], $config->getDataTransformers());
        self::assertEquals(['data_transformer' => ['service_id']], $config->toArray());

        $config->setDataTransformers(['service_id', ['class', 'method']]);
        self::assertTrue($config->hasDataTransformers());
        self::assertEquals(['service_id', ['class', 'method']], $config->getDataTransformers());
        self::assertEquals(['data_transformer' => ['service_id', ['class', 'method']]], $config->toArray());

        $config->setDataTransformers([]);
        self::assertFalse($config->hasDataTransformers());
        self::assertEquals([], $config->getDataTransformers());
        self::assertEquals([], $config->toArray());
    }

    public function testDependsOn()
    {
        $config = new EntityDefinitionFieldConfig();
        self::assertNull($config->getDependsOn());

        $config->setDependsOn(['field1']);
        self::assertEquals(['field1'], $config->getDependsOn());
        self::assertEquals(['depends_on' => ['field1']], $config->toArray());

        $config->setDependsOn([]);
        self::assertNull($config->getDependsOn());
        self::assertEquals([], $config->toArray());
    }
}
