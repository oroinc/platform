<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;

class ActionConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmpty()
    {
        $config = new ActionConfig();
        $this->assertTrue($config->isEmpty());
    }

    public function testClone()
    {
        $config = new ActionConfig();
        $this->assertTrue($config->isEmpty());
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
        $config = new ActionConfig();
        $this->assertFalse($config->hasExcluded());
        $this->assertFalse($config->isExcluded());

        $config->setExcluded();
        $this->assertTrue($config->hasExcluded());
        $this->assertTrue($config->isExcluded());
        $this->assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        $this->assertTrue($config->hasExcluded());
        $this->assertFalse($config->isExcluded());
        $this->assertEquals(['exclude' => false], $config->toArray());
    }

    public function testWithAttribute()
    {
        $attrName = 'test';

        $config = new ActionConfig();
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

    public function testDescription()
    {
        $config = new ActionConfig();
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

    public function testDocumentation()
    {
        $config = new ActionConfig();
        $this->assertFalse($config->hasDocumentation());
        $this->assertNull($config->getDocumentation());

        $config->setDocumentation('text');
        $this->assertTrue($config->hasDocumentation());
        $this->assertEquals('text', $config->getDocumentation());
        $this->assertEquals(['documentation' => 'text'], $config->toArray());

        $config->setDocumentation(null);
        $this->assertFalse($config->hasDocumentation());
        $this->assertNull($config->getDocumentation());
        $this->assertEquals([], $config->toArray());

        $config->setDocumentation('text');
        $config->setDocumentation('');
        $this->assertFalse($config->hasDocumentation());
        $this->assertNull($config->getDocumentation());
        $this->assertEquals([], $config->toArray());
    }
}
