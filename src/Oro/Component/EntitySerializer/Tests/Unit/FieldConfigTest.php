<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;
use PHPUnit\Framework\TestCase;

class FieldConfigTest extends TestCase
{
    public function testCustomAttribute(): void
    {
        $attrName = 'test';

        $fieldConfig = new FieldConfig();
        self::assertFalse($fieldConfig->has($attrName));
        self::assertNull($fieldConfig->get($attrName));

        $fieldConfig->set($attrName, null);
        self::assertTrue($fieldConfig->has($attrName));
        self::assertNull($fieldConfig->get($attrName));
        self::assertEquals([$attrName => null], $fieldConfig->toArray());

        $fieldConfig->set($attrName, false);
        self::assertTrue($fieldConfig->has($attrName));
        self::assertFalse($fieldConfig->get($attrName));
        self::assertEquals([$attrName => false], $fieldConfig->toArray());

        $fieldConfig->remove($attrName);
        self::assertFalse($fieldConfig->has($attrName));
        self::assertNull($fieldConfig->get($attrName));
        self::assertSame([], $fieldConfig->toArray());
    }

    public function testToArray(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertEquals([], $fieldConfig->toArray());

        $fieldConfig->setExcluded();

        $targetEntity = new EntityConfig();
        $targetEntity->setExcludeAll();
        $fieldConfig->setTargetEntity($targetEntity);

        self::assertEquals(
            [
                'exclude'          => true,
                'exclusion_policy' => 'all'
            ],
            $fieldConfig->toArray()
        );
    }

    public function testIsEmpty(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertTrue($fieldConfig->isEmpty());

        $fieldConfig->setExcluded();
        self::assertFalse($fieldConfig->isEmpty());

        $fieldConfig->setExcluded(false);
        self::assertFalse($fieldConfig->isEmpty());

        $fieldConfig->setExcluded(null);
        self::assertTrue($fieldConfig->isEmpty());

        $targetEntity = new EntityConfig();
        $fieldConfig->setTargetEntity($targetEntity);
        self::assertTrue($fieldConfig->isEmpty());

        $targetEntity->setExcludeAll();
        self::assertFalse($fieldConfig->isEmpty());

        $fieldConfig->setTargetEntity(null);
        self::assertTrue($fieldConfig->isEmpty());
    }

    public function testTargetEntity(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertNull($fieldConfig->getTargetEntity());

        $targetEntity = new EntityConfig();
        self::assertSame($targetEntity, $fieldConfig->setTargetEntity($targetEntity));
        self::assertSame($targetEntity, $fieldConfig->getTargetEntity());
    }

    public function testExcluded(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertFalse($fieldConfig->isExcluded());

        $fieldConfig->setExcluded();
        self::assertTrue($fieldConfig->isExcluded());
        self::assertEquals(['exclude' => true], $fieldConfig->toArray());

        $fieldConfig->setExcluded(false);
        self::assertFalse($fieldConfig->isExcluded());
        self::assertEquals([], $fieldConfig->toArray());
    }

    public function testCollapsed(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertFalse($fieldConfig->isCollapsed());

        $fieldConfig->setCollapsed();
        self::assertTrue($fieldConfig->isCollapsed());
        self::assertEquals(['collapse' => true], $fieldConfig->toArray());

        $fieldConfig->setCollapsed(false);
        self::assertFalse($fieldConfig->isCollapsed());
        self::assertEquals([], $fieldConfig->toArray());
    }

    public function testPropertyPath(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertNull($fieldConfig->getPropertyPath());
        self::assertEquals('default', $fieldConfig->getPropertyPath('default'));

        $fieldConfig->setPropertyPath('test');
        self::assertEquals('test', $fieldConfig->getPropertyPath());
        self::assertEquals('test', $fieldConfig->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'test'], $fieldConfig->toArray());

        $fieldConfig->setPropertyPath();
        self::assertNull($fieldConfig->getPropertyPath());
        self::assertEquals('default', $fieldConfig->getPropertyPath('default'));
        self::assertEquals([], $fieldConfig->toArray());
    }

    public function testDataTransformers(): void
    {
        $fieldConfig = new FieldConfig();
        self::assertEquals([], $fieldConfig->getDataTransformers());

        $fieldConfig->addDataTransformer('data_transformer_service_id');
        self::assertEquals(
            ['data_transformer_service_id'],
            $fieldConfig->getDataTransformers()
        );
        self::assertEquals(
            ['data_transformer' => ['data_transformer_service_id']],
            $fieldConfig->toArray()
        );

        $fieldConfig->addDataTransformer('another_data_transformer_service_id');
        self::assertEquals(
            ['data_transformer_service_id', 'another_data_transformer_service_id'],
            $fieldConfig->getDataTransformers()
        );
        self::assertEquals(
            ['data_transformer' => ['data_transformer_service_id', 'another_data_transformer_service_id']],
            $fieldConfig->toArray()
        );
    }
}
