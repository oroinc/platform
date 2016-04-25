<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;

class FieldConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $fieldConfig = new FieldConfig();
        $this->assertEquals([], $fieldConfig->toArray());

        $fieldConfig->setExcluded();

        $targetEntity = new EntityConfig();
        $targetEntity->setExcludeAll();
        $fieldConfig->setTargetEntity($targetEntity);

        $this->assertEquals(
            [
                'exclude'          => true,
                'exclusion_policy' => 'all'
            ],
            $fieldConfig->toArray()
        );
    }

    public function testIsEmpty()
    {
        $fieldConfig = new FieldConfig();
        $this->assertTrue($fieldConfig->isEmpty());

        $fieldConfig->setExcluded();
        $this->assertFalse($fieldConfig->isEmpty());

        $fieldConfig->setExcluded(false);
        $this->assertTrue($fieldConfig->isEmpty());

        $targetEntity = new EntityConfig();
        $fieldConfig->setTargetEntity($targetEntity);
        $this->assertTrue($fieldConfig->isEmpty());

        $targetEntity->setExcludeAll();
        $this->assertFalse($fieldConfig->isEmpty());

        $fieldConfig->setTargetEntity(null);
        $this->assertTrue($fieldConfig->isEmpty());
    }

    public function testTargetEntity()
    {
        $fieldConfig = new FieldConfig();
        $this->assertNull($fieldConfig->getTargetEntity());

        $targetEntity = new EntityConfig();
        $this->assertSame($targetEntity, $fieldConfig->setTargetEntity($targetEntity));
        $this->assertSame($targetEntity, $fieldConfig->getTargetEntity());
    }

    public function testExcluded()
    {
        $fieldConfig = new FieldConfig();
        $this->assertFalse($fieldConfig->isExcluded());

        $fieldConfig->setExcluded();
        $this->assertTrue($fieldConfig->isExcluded());
        $this->assertEquals(['exclude' => true], $fieldConfig->toArray());

        $fieldConfig->setExcluded(false);
        $this->assertFalse($fieldConfig->isExcluded());
        $this->assertEquals([], $fieldConfig->toArray());
    }

    public function testCollapsed()
    {
        $fieldConfig = new FieldConfig();
        $this->assertFalse($fieldConfig->isCollapsed());

        $fieldConfig->setCollapsed();
        $this->assertTrue($fieldConfig->isCollapsed());
        $this->assertEquals(['collapse' => true], $fieldConfig->toArray());

        $fieldConfig->setCollapsed(false);
        $this->assertFalse($fieldConfig->isCollapsed());
        $this->assertEquals([], $fieldConfig->toArray());
    }

    public function testPropertyPath()
    {
        $fieldConfig = new FieldConfig();
        $this->assertNull($fieldConfig->getPropertyPath());

        $fieldConfig->setPropertyPath('test');
        $this->assertEquals('test', $fieldConfig->getPropertyPath());
        $this->assertEquals(['property_path' => 'test'], $fieldConfig->toArray());

        $fieldConfig->setPropertyPath();
        $this->assertNull($fieldConfig->getPropertyPath());
        $this->assertEquals([], $fieldConfig->toArray());
    }

    public function testDataTransformers()
    {
        $fieldConfig = new FieldConfig();
        $this->assertEquals([], $fieldConfig->getDataTransformers());

        $fieldConfig->addDataTransformer('data_transformer_service_id');
        $this->assertEquals(
            ['data_transformer_service_id'],
            $fieldConfig->getDataTransformers()
        );
        $this->assertEquals(
            ['data_transformer' => ['data_transformer_service_id']],
            $fieldConfig->toArray()
        );

        $fieldConfig->addDataTransformer('another_data_transformer_service_id');
        $this->assertEquals(
            ['data_transformer_service_id', 'another_data_transformer_service_id'],
            $fieldConfig->getDataTransformers()
        );
        $this->assertEquals(
            ['data_transformer' => ['data_transformer_service_id', 'another_data_transformer_service_id']],
            $fieldConfig->toArray()
        );
    }
}
