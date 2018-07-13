<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;

class StatusCodeConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new StatusCodeConfig();
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

    public function testClone()
    {
        $config = new StatusCodeConfig();
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
        $config = new StatusCodeConfig();
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

    public function testDescription()
    {
        $config = new StatusCodeConfig();
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
}
