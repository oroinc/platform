<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\SubresourceConfig;

class SubresourceConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmpty()
    {
        $config = new SubresourceConfig();
        $this->assertTrue($config->isEmpty());
    }

    public function testClone()
    {
        $config = new SubresourceConfig();
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
        $config = new SubresourceConfig();
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

    public function testTargetClass()
    {
        $config = new SubresourceConfig();
        $this->assertNull($config->getTargetClass());

        $config->setTargetClass('Test\Class');
        $this->assertEquals('Test\Class', $config->getTargetClass());
        $this->assertEquals(['target_class' => 'Test\Class'], $config->toArray());

        $config->setTargetClass(null);
        $this->assertNull($config->getTargetClass());
        $this->assertEquals([], $config->toArray());
    }

    public function testTargetType()
    {
        $config = new SubresourceConfig();
        $this->assertFalse($config->hasTargetType());
        $this->assertNull($config->getTargetType());
        $this->assertNull($config->isCollectionValuedAssociation());

        $config->setTargetType('to-one');
        $this->assertTrue($config->hasTargetType());
        $this->assertEquals('to-one', $config->getTargetType());
        $this->assertFalse($config->isCollectionValuedAssociation());
        $this->assertEquals(['target_type' => 'to-one'], $config->toArray());

        $config->setTargetType('to-many');
        $this->assertTrue($config->hasTargetType());
        $this->assertEquals('to-many', $config->getTargetType());
        $this->assertTrue($config->isCollectionValuedAssociation());
        $this->assertEquals(['target_type' => 'to-many'], $config->toArray());

        $config->setTargetType(null);
        $this->assertFalse($config->hasTargetType());
        $this->assertNull($config->getTargetType());
        $this->assertNull($config->isCollectionValuedAssociation());
        $this->assertEquals([], $config->toArray());
    }

    public function testActions()
    {
        $actionConfig = new ActionConfig();

        $config = new SubresourceConfig();
        $this->assertEmpty($config->getActions());

        $config->addAction('action1', $actionConfig);
        $this->assertNotEmpty($config->getActions());
        $this->assertCount(1, $config->getActions());

        $this->assertSame($actionConfig, $config->getAction('action1'));
        $this->assertNull($config->getAction('action2'));

        $config->addAction('action2');
        $this->assertEquals(new ActionConfig(), $config->getAction('action2'));
        $this->assertCount(2, $config->getActions());

        $config->removeAction('action1');
        $config->removeAction('action2');
        $this->assertTrue($config->isEmpty());
    }

    public function testExtraAttribute()
    {
        $attrName = 'test';

        $config = new SubresourceConfig();
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
}
