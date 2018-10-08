<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;

class FilterFieldConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new FilterFieldConfig();
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
        $config = new FilterFieldConfig();
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
        $config = new FilterFieldConfig();
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

    public function testPropertyPath()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        self::assertTrue($config->hasPropertyPath());
        self::assertEquals('path', $config->getPropertyPath());
        self::assertEquals('path', $config->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));
        self::assertEquals([], $config->toArray());
    }

    public function testIsCollection()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasCollection());
        self::assertFalse($config->isCollection());

        $config->setIsCollection(true);
        self::assertTrue($config->hasCollection());
        self::assertTrue($config->isCollection());
        self::assertEquals(['collection' => true], $config->toArray());

        $config->setIsCollection(false);
        self::assertTrue($config->hasCollection());
        self::assertFalse($config->isCollection());
        self::assertEquals([], $config->toArray());
    }

    public function testDescription()
    {
        $config = new FilterFieldConfig();
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

    public function testDataType()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());

        $config->setDataType('string');
        self::assertTrue($config->hasDataType());
        self::assertEquals('string', $config->getDataType());

        $config->setDataType(null);
        self::assertFalse($config->hasDataType());
        self::assertNull($config->getDataType());
    }

    public function testArrayAllowed()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasArrayAllowed());
        self::assertFalse($config->isArrayAllowed());

        $config->setArrayAllowed();
        self::assertTrue($config->hasArrayAllowed());
        self::assertTrue($config->isArrayAllowed());
        self::assertEquals(['allow_array' => true], $config->toArray());

        $config->setArrayAllowed(false);
        self::assertTrue($config->hasArrayAllowed());
        self::assertFalse($config->isArrayAllowed());
        self::assertEquals([], $config->toArray());
    }

    public function testRangeAllowed()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasRangeAllowed());
        self::assertFalse($config->isRangeAllowed());

        $config->setRangeAllowed();
        self::assertTrue($config->hasRangeAllowed());
        self::assertTrue($config->isRangeAllowed());
        self::assertEquals(['allow_range' => true], $config->toArray());

        $config->setRangeAllowed(false);
        self::assertTrue($config->hasRangeAllowed());
        self::assertFalse($config->isRangeAllowed());
        self::assertEquals([], $config->toArray());
    }

    public function testType()
    {
        $config = new FilterFieldConfig();
        self::assertFalse($config->hasType());
        self::assertNull($config->getType());

        $config->setType('test');
        self::assertTrue($config->hasType());
        self::assertEquals('test', $config->getType());
        self::assertEquals(['type' => 'test'], $config->toArray());

        $config->setType(null);
        self::assertFalse($config->hasType());
        self::assertNull($config->getType());
        self::assertEquals([], $config->toArray());
    }

    public function testOptions()
    {
        $config = new FilterFieldConfig();
        self::assertNull($config->getOptions());

        $config->setOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getOptions());
        self::assertEquals(['options' => ['key' => 'val']], $config->toArray());

        $config->setOptions(null);
        self::assertNull($config->getOptions());
        self::assertEquals([], $config->toArray());
    }

    public function testOperators()
    {
        $config = new FilterFieldConfig();
        self::assertNull($config->getOperators());

        $config->setOperators(['=', '!=']);
        self::assertEquals(['=', '!='], $config->getOperators());
        self::assertEquals(['operators' => ['=', '!=']], $config->toArray());

        $config->setOperators(null);
        self::assertNull($config->getOperators());
        self::assertEquals([], $config->toArray());
    }
}
