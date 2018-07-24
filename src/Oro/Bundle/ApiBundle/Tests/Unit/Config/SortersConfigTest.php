<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;

class SortersConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new SortersConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertTrue($config->isEmpty());
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertFalse($config->isEmpty());
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertTrue($config->isEmpty());
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testExclusionPolicy()
    {
        $config = new SortersConfig();
        self::assertFalse($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertTrue($config->isEmpty());

        $config->setExclusionPolicy('all');
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('all', $config->getExclusionPolicy());
        self::assertTrue($config->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExclusionPolicy('none');
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExcludeAll();
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('all', $config->getExclusionPolicy());
        self::assertTrue($config->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExcludeNone();
        self::assertTrue($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertFalse($config->isEmpty());

        $config->setExclusionPolicy(null);
        self::assertFalse($config->hasExclusionPolicy());
        self::assertEquals('none', $config->getExclusionPolicy());
        self::assertFalse($config->isExcludeAll());
        self::assertEquals([], $config->toArray());
        self::assertTrue($config->isEmpty());
    }

    public function testFields()
    {
        $config = new SortersConfig();
        self::assertFalse($config->hasFields());
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());

        $field = $config->addField('field');
        self::assertTrue($config->hasFields());
        self::assertEquals(['field' => $field], $config->getFields());
        self::assertSame($field, $config->getField('field'));
        self::assertFalse($config->isEmpty());
        self::assertEquals(['fields' => ['field' => null]], $config->toArray());

        $config->removeField('field');
        self::assertFalse($config->hasFields());
        self::assertEquals([], $config->getFields());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
    }

    public function testFindField()
    {
        $config = new SortersConfig();

        $field1 = $config->addField('field1');
        $field2 = $config->addField('field2');
        $field2->setPropertyPath('realField2');
        $field3 = $config->addField('field3');
        $field3->setPropertyPath('field3');
        $swapField1 = $config->addField('swapField');
        $swapField1->setPropertyPath('realSwapField');
        $swapField2 = $config->addField('realSwapField');
        $swapField2->setPropertyPath('swapField');

        self::assertNull($config->findFieldNameByPropertyPath('unknown'));
        self::assertNull($config->findField('unknown'));
        self::assertNull($config->findField('unknown', true));

        self::assertSame('field1', $config->findFieldNameByPropertyPath('field1'));
        self::assertSame($field1, $config->findField('field1'));
        self::assertSame($field1, $config->findField('field1', true));

        self::assertNull($config->findFieldNameByPropertyPath('field2'));
        self::assertSame('field2', $config->findFieldNameByPropertyPath('realField2'));
        self::assertSame($field2, $config->findField('field2'));
        self::assertNull($config->findField('field2', true));
        self::assertNull($config->findField('realField2'));
        self::assertSame($field2, $config->findField('realField2', true));

        self::assertSame('field3', $config->findFieldNameByPropertyPath('field3'));
        self::assertSame($field3, $config->findField('field3'));
        self::assertSame($field3, $config->findField('field3', true));

        self::assertSame('realSwapField', $config->findFieldNameByPropertyPath('swapField'));
        self::assertSame('swapField', $config->findFieldNameByPropertyPath('realSwapField'));
        self::assertSame($swapField1, $config->findField('swapField'));
        self::assertSame($swapField2, $config->findField('swapField', true));
        self::assertSame($swapField2, $config->findField('realSwapField'));
        self::assertSame($swapField1, $config->findField('realSwapField', true));
    }

    public function testGetOrAddField()
    {
        $config = new SortersConfig();

        $field = $config->getOrAddField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = $config->getOrAddField('field');
        self::assertSame($field, $field1);
    }

    public function testAddField()
    {
        $config = new SortersConfig();

        $field = $config->addField('field');
        self::assertSame($field, $config->getField('field'));

        $field1 = new SorterFieldConfig();
        $field1 = $config->addField('field', $field1);
        self::assertSame($field1, $config->getField('field'));
        self::assertNotSame($field, $field1);
    }
}
