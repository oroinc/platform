<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;

class FiltersConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new FiltersConfig();
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

    public function testExclusionPolicy()
    {
        $config = new FiltersConfig();
        $this->assertFalse($config->hasExclusionPolicy());
        $this->assertEquals('none', $config->getExclusionPolicy());
        $this->assertFalse($config->isExcludeAll());

        $config->setExclusionPolicy('all');
        $this->assertTrue($config->hasExclusionPolicy());
        $this->assertEquals('all', $config->getExclusionPolicy());
        $this->assertTrue($config->isExcludeAll());
        $this->assertEquals(['exclusion_policy' => 'all'], $config->toArray());

        $config->setExclusionPolicy('none');
        $this->assertTrue($config->hasExclusionPolicy());
        $this->assertEquals('none', $config->getExclusionPolicy());
        $this->assertFalse($config->isExcludeAll());
        $this->assertEquals([], $config->toArray());

        $config->setExcludeAll();
        $this->assertTrue($config->hasExclusionPolicy());
        $this->assertEquals('all', $config->getExclusionPolicy());
        $this->assertTrue($config->isExcludeAll());
        $this->assertEquals(['exclusion_policy' => 'all'], $config->toArray());

        $config->setExcludeNone();
        $this->assertTrue($config->hasExclusionPolicy());
        $this->assertEquals('none', $config->getExclusionPolicy());
        $this->assertFalse($config->isExcludeAll());
        $this->assertEquals([], $config->toArray());
    }

    public function testFields()
    {
        $config = new FiltersConfig();
        $this->assertFalse($config->hasFields());
        $this->assertEquals([], $config->getFields());
        $this->assertTrue($config->isEmpty());
        $this->assertEquals([], $config->toArray());

        $field = $config->addField('field');
        $this->assertTrue($config->hasFields());
        $this->assertEquals(['field' => $field], $config->getFields());
        $this->assertSame($field, $config->getField('field'));
        $this->assertFalse($config->isEmpty());
        $this->assertEquals(['fields' => ['field' => null]], $config->toArray());

        $config->removeField('field');
        $this->assertFalse($config->hasFields());
        $this->assertEquals([], $config->getFields());
        $this->assertTrue($config->isEmpty());
        $this->assertEquals([], $config->toArray());
    }

    public function testGetOrAddField()
    {
        $config = new FiltersConfig();

        $field = $config->getOrAddField('field');
        $this->assertSame($field, $config->getField('field'));

        $field1 = $config->getOrAddField('field');
        $this->assertSame($field, $field1);
    }

    public function testAddField()
    {
        $config = new FiltersConfig();

        $field = $config->addField('field');
        $this->assertSame($field, $config->getField('field'));

        $field1 = new FilterFieldConfig();
        $field1 = $config->addField('field', $field1);
        $this->assertSame($field1, $config->getField('field'));
        $this->assertNotSame($field, $field1);
    }
}
