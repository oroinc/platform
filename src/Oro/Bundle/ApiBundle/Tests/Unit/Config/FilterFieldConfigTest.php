<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;

class FilterFieldConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new FilterFieldConfig();
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
        $config = new FilterFieldConfig();
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
        $config = new FilterFieldConfig();
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

    public function testDescription()
    {
        $config = new FilterFieldConfig();
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

    public function testDataType()
    {
        $config = new FilterFieldConfig();
        $this->assertFalse($config->hasDataType());
        $this->assertNull($config->getDataType());

        $config->setDataType('string');
        $this->assertTrue($config->hasDataType());
        $this->assertEquals('string', $config->getDataType());
        $this->assertEquals(['data_type' => 'string'], $config->toArray());

        $config->setDataType(null);
        $this->assertFalse($config->hasDataType());
        $this->assertNull($config->getDataType());
        $this->assertEquals([], $config->toArray());

        $config->setDataType('string');
        $config->setDataType('');
        $this->assertFalse($config->hasDataType());
        $this->assertNull($config->getDataType());
        $this->assertEquals([], $config->toArray());
    }

    public function testArrayAllowed()
    {
        $config = new FilterFieldConfig();
        $this->assertFalse($config->hasArrayAllowed());
        $this->assertFalse($config->isArrayAllowed());

        $config->setArrayAllowed();
        $this->assertTrue($config->hasArrayAllowed());
        $this->assertTrue($config->isArrayAllowed());
        $this->assertEquals(['allow_array' => true], $config->toArray());

        $config->setArrayAllowed(false);
        $this->assertTrue($config->hasArrayAllowed());
        $this->assertFalse($config->isArrayAllowed());
        $this->assertEquals([], $config->toArray());
    }
}
