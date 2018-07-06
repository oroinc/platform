<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\SubresourceConfig;

class SubresourceConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEmpty()
    {
        $config = new SubresourceConfig();
        self::assertTrue($config->isEmpty());
    }

    public function testClone()
    {
        $config = new SubresourceConfig();
        self::assertTrue($config->isEmpty());
        self::assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testExcluded()
    {
        $config = new SubresourceConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals(['exclude' => false], $config->toArray());
    }

    public function testTargetClass()
    {
        $config = new SubresourceConfig();
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
        $config = new SubresourceConfig();
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

    public function testActions()
    {
        $actionConfig = new ActionConfig();

        $config = new SubresourceConfig();
        self::assertEmpty($config->getActions());

        $config->addAction('action1', $actionConfig);
        self::assertNotEmpty($config->getActions());
        self::assertCount(1, $config->getActions());

        self::assertSame($actionConfig, $config->getAction('action1'));
        self::assertNull($config->getAction('action2'));

        $config->addAction('action2');
        self::assertEquals(new ActionConfig(), $config->getAction('action2'));
        self::assertCount(2, $config->getActions());

        $config->removeAction('action1');
        $config->removeAction('action2');
        self::assertTrue($config->isEmpty());
    }

    public function testExtraAttribute()
    {
        $attrName = 'test';

        $config = new SubresourceConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
    }
}
