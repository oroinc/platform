<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

class EntityDefinitionFieldConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));

        $config->set($attrName, null);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertEquals([], $config->toArray());

        $config->set($attrName, false);
        $this->assertTrue($config->has($attrName));
        $this->assertFalse($config->get($attrName));
        $this->assertEquals([$attrName => false], $config->toArray());

        $config->remove($attrName);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertEquals([], $config->toArray());
    }

    public function testExcluded()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasExcluded());
        $this->assertFalse($config->isExcluded());

        $config->setExcluded();
        $this->assertTrue($config->hasExcluded());
        $this->assertTrue($config->isExcluded());
        $this->assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        $this->assertTrue($config->hasExcluded());
        $this->assertFalse($config->isExcluded());
        $this->assertEquals([], $config->toArray());
    }

    public function testPropertyPath()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());

        $config->setPropertyPath('path');
        $this->assertTrue($config->hasPropertyPath());
        $this->assertEquals('path', $config->getPropertyPath());
        $this->assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals([], $config->toArray());
    }

    public function testLabel()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasLabel());
        $this->assertNull($config->getLabel());

        $config->setLabel('text');
        $this->assertTrue($config->hasLabel());
        $this->assertEquals('text', $config->getLabel());
        $this->assertEquals(['label' => 'text'], $config->toArray());

        $config->setLabel(null);
        $this->assertFalse($config->hasLabel());
        $this->assertNull($config->getLabel());
        $this->assertEquals([], $config->toArray());

        $config->setLabel('text');
        $config->setLabel('');
        $this->assertFalse($config->hasLabel());
        $this->assertNull($config->getLabel());
        $this->assertEquals([], $config->toArray());
    }

    public function testDescription()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasDescription());
        $this->assertNull($config->getDescription());

        $config->setDescription('text');
        $this->assertTrue($config->hasDescription());
        $this->assertEquals('text', $config->getDescription());
        $this->assertEquals(['description' => 'text'], $config->toArray());

        $config->setDescription(null);
        $this->assertFalse($config->hasDescription());
        $this->assertNull($config->getDescription());
        $this->assertEquals([], $config->toArray());

        $config->setDescription('text');
        $config->setDescription('');
        $this->assertFalse($config->hasDescription());
        $this->assertNull($config->getDescription());
        $this->assertEquals([], $config->toArray());
    }

    public function testGetOrCreateTargetEntity()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasTargetEntity());
        $this->assertNull($config->getTargetEntity());

        $targetEntity = $config->getOrCreateTargetEntity();
        $this->assertTrue($config->hasTargetEntity());
        $this->assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->getOrCreateTargetEntity();
        $this->assertSame($targetEntity, $targetEntity1);
        $this->assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testCreateAndSetTargetEntity()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasTargetEntity());
        $this->assertNull($config->getTargetEntity());

        $targetEntity = $config->createAndSetTargetEntity();
        $this->assertTrue($config->hasTargetEntity());
        $this->assertSame($targetEntity, $config->getTargetEntity());

        $targetEntity1 = $config->createAndSetTargetEntity();
        $this->assertNotSame($targetEntity, $targetEntity1);
        $this->assertSame($targetEntity1, $config->getTargetEntity());
    }

    public function testCollapsed()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasCollapsed());
        $this->assertFalse($config->isCollapsed());

        $config->setCollapsed();
        $this->assertTrue($config->hasCollapsed());
        $this->assertTrue($config->isCollapsed());
        $this->assertEquals(['collapse' => true], $config->toArray());

        $config->setCollapsed(false);
        $this->assertTrue($config->hasCollapsed());
        $this->assertFalse($config->isCollapsed());
        $this->assertEquals([], $config->toArray());
    }

    public function testSetDataTransformers()
    {
        $config = new EntityDefinitionFieldConfig();
        $this->assertFalse($config->hasDataTransformers());
        $this->assertEquals([], $config->getDataTransformers());

        $config->setDataTransformers('service_id');
        $this->assertTrue($config->hasDataTransformers());
        $this->assertEquals(['service_id'], $config->getDataTransformers());
        $this->assertEquals(['data_transformer' => ['service_id']], $config->toArray());

        $config->setDataTransformers(['service_id', ['class', 'method']]);
        $this->assertTrue($config->hasDataTransformers());
        $this->assertEquals(['service_id', ['class', 'method']], $config->getDataTransformers());
        $this->assertEquals(['data_transformer' => ['service_id', ['class', 'method']]], $config->toArray());

        $config->setDataTransformers([]);
        $this->assertFalse($config->hasDataTransformers());
        $this->assertEquals([], $config->getDataTransformers());
        $this->assertEquals([], $config->toArray());
    }
}
