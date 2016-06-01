<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class EntityDefinitionConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new EntityDefinitionConfig();
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

    public function testLabel()
    {
        $config = new EntityDefinitionConfig();
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

    public function testPluralLabel()
    {
        $config = new EntityDefinitionConfig();
        $this->assertFalse($config->hasPluralLabel());
        $this->assertNull($config->getPluralLabel());

        $config->setPluralLabel('text');
        $this->assertTrue($config->hasPluralLabel());
        $this->assertEquals('text', $config->getPluralLabel());
        $this->assertEquals(['plural_label' => 'text'], $config->toArray());

        $config->setPluralLabel(null);
        $this->assertFalse($config->hasPluralLabel());
        $this->assertNull($config->getPluralLabel());
        $this->assertEquals([], $config->toArray());

        $config->setPluralLabel('text');
        $config->setPluralLabel('');
        $this->assertFalse($config->hasPluralLabel());
        $this->assertNull($config->getPluralLabel());
        $this->assertEquals([], $config->toArray());
    }

    public function testDescription()
    {
        $config = new EntityDefinitionConfig();
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

    public function testFields()
    {
        $config = new EntityDefinitionConfig();
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

    public function testFindField()
    {
        $config = new EntityDefinitionConfig();

        $field1 = $config->addField('field1');
        $field2 = $config->addField('field2');
        $field2->setPropertyPath('realField2');
        $field3 = $config->addField('field3');
        $field3->setPropertyPath('field3');
        $swapField1 = $config->addField('swapField');
        $swapField1->setPropertyPath('realSwapField');
        $swapField2 = $config->addField('realSwapField');
        $swapField2->setPropertyPath('swapField');

        $this->assertNull($config->findFieldNameByPropertyPath('unknown'));
        $this->assertNull($config->findField('unknown'));
        $this->assertNull($config->findField('unknown', true));

        $this->assertSame('field1', $config->findFieldNameByPropertyPath('field1'));
        $this->assertSame($field1, $config->findField('field1'));
        $this->assertSame($field1, $config->findField('field1', true));

        $this->assertNull($config->findFieldNameByPropertyPath('field2'));
        $this->assertSame('field2', $config->findFieldNameByPropertyPath('realField2'));
        $this->assertSame($field2, $config->findField('field2'));
        $this->assertNull($config->findField('field2', true));
        $this->assertNull($config->findField('realField2'));
        $this->assertSame($field2, $config->findField('realField2', true));

        $this->assertSame('field3', $config->findFieldNameByPropertyPath('field3'));
        $this->assertSame($field3, $config->findField('field3'));
        $this->assertSame($field3, $config->findField('field3', true));

        $this->assertSame('realSwapField', $config->findFieldNameByPropertyPath('swapField'));
        $this->assertSame('swapField', $config->findFieldNameByPropertyPath('realSwapField'));
        $this->assertSame($swapField1, $config->findField('swapField'));
        $this->assertSame($swapField2, $config->findField('swapField', true));
        $this->assertSame($swapField2, $config->findField('realSwapField'));
        $this->assertSame($swapField1, $config->findField('realSwapField', true));
    }

    public function testGetOrAddField()
    {
        $config = new EntityDefinitionConfig();

        $field = $config->getOrAddField('field');
        $this->assertSame($field, $config->getField('field'));

        $field1 = $config->getOrAddField('field');
        $this->assertSame($field, $field1);
    }

    public function testAddField()
    {
        $config = new EntityDefinitionConfig();

        $field = $config->addField('field');
        $this->assertSame($field, $config->getField('field'));

        $field1 = new EntityDefinitionFieldConfig();
        $field1 = $config->addField('field', $field1);
        $this->assertSame($field1, $config->getField('field'));
        $this->assertNotSame($field, $field1);
    }

    public function testExclusionPolicy()
    {
        $config = new EntityDefinitionConfig();
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

    public function testCollapsed()
    {
        $config = new EntityDefinitionConfig();
        $this->assertFalse($config->isCollapsed());

        $config->setCollapsed();
        $this->assertTrue($config->isCollapsed());
        $this->assertEquals(['collapse' => true], $config->toArray());

        $config->setCollapsed(false);
        $this->assertFalse($config->isCollapsed());
        $this->assertEquals([], $config->toArray());
    }

    public function testPartialLoad()
    {
        $config = new EntityDefinitionConfig();
        $this->assertFalse($config->hasPartialLoad());
        $this->assertTrue($config->isPartialLoadEnabled());

        $config->disablePartialLoad();
        $this->assertTrue($config->hasPartialLoad());
        $this->assertFalse($config->isPartialLoadEnabled());
        $this->assertEquals(['disable_partial_load' => true], $config->toArray());

        $config->enablePartialLoad();
        $this->assertTrue($config->hasPartialLoad());
        $this->assertTrue($config->isPartialLoadEnabled());
        $this->assertEquals([], $config->toArray());
    }

    public function testMaxResults()
    {
        $config = new EntityDefinitionConfig();
        $this->assertFalse($config->hasMaxResults());
        $this->assertNull($config->getMaxResults());

        $config->setMaxResults(50);
        $this->assertTrue($config->hasMaxResults());
        $this->assertEquals(50, $config->getMaxResults());
        $this->assertEquals(['max_results' => 50], $config->toArray());

        $config->setMaxResults('100');
        $this->assertTrue($config->hasMaxResults());
        $this->assertSame(100, $config->getMaxResults());
        $this->assertSame(['max_results' => 100], $config->toArray());

        $config->setMaxResults(-1);
        $this->assertTrue($config->hasMaxResults());
        $this->assertEquals(-1, $config->getMaxResults());
        $this->assertEquals(['max_results' => -1], $config->toArray());

        $config->setMaxResults(null);
        $this->assertFalse($config->hasMaxResults());
        $this->assertNull($config->getMaxResults());
        $this->assertEquals([], $config->toArray());
    }

    public function testSetHints()
    {
        $config = new EntityDefinitionConfig();
        $this->assertEquals([], $config->getHints());

        $config->setHints(['hint1']);
        $this->assertEquals(['hint1'], $config->getHints());
        $this->assertEquals(['hints' => ['hint1']], $config->toArray());

        $config->setHints();
        $this->assertEquals([], $config->getHints());
        $this->assertEquals([], $config->toArray());

        $config->setHints(['hint1']);
        $config->setHints([]);
        $this->assertEquals([], $config->getHints());
        $this->assertEquals([], $config->toArray());
    }
}
