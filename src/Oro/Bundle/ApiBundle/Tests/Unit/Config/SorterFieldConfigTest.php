<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;

class SorterFieldConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new SorterFieldConfig();
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertSame([], $config->keys());

        $config->set($attrName, null);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertEquals([], $config->toArray());
        $this->assertSame([], $config->keys());

        $config->set($attrName, false);
        $this->assertTrue($config->has($attrName));
        $this->assertFalse($config->get($attrName));
        $this->assertEquals([$attrName => false], $config->toArray());
        $this->assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        $this->assertFalse($config->has($attrName));
        $this->assertNull($config->get($attrName));
        $this->assertSame([], $config->toArray());
        $this->assertSame([], $config->keys());
    }

    public function testClone()
    {
        $config = new SorterFieldConfig();
        $this->assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        $this->assertEquals($config, $configClone);
        $this->assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testExcluded()
    {
        $config = new SorterFieldConfig();
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
        $config = new SorterFieldConfig();
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        $this->assertTrue($config->hasPropertyPath());
        $this->assertEquals('path', $config->getPropertyPath());
        $this->assertEquals('path', $config->getPropertyPath('default'));
        $this->assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        $this->assertFalse($config->hasPropertyPath());
        $this->assertNull($config->getPropertyPath());
        $this->assertEquals('default', $config->getPropertyPath('default'));
        $this->assertEquals([], $config->toArray());
    }
}
