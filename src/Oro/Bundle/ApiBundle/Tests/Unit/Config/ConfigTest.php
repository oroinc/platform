<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new Config();
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

    public function testDefinition()
    {
        $config = new Config();
        $this->assertFalse($config->hasDefinition());
        $this->assertNull($config->getDefinition());

        $definition = new EntityDefinitionConfig();
        $config->setDefinition($definition);
        $this->assertTrue($config->hasDefinition());
        $this->assertSame($definition, $config->getDefinition());

        $config->setDefinition();
        $this->assertFalse($config->hasDefinition());
        $this->assertNull($config->getDefinition());
    }

    public function testFilters()
    {
        $config = new Config();
        $this->assertFalse($config->hasFilters());
        $this->assertNull($config->getFilters());

        $filters = new FiltersConfig();
        $config->setFilters($filters);
        $this->assertTrue($config->hasFilters());
        $this->assertSame($filters, $config->getFilters());

        $config->setFilters();
        $this->assertFalse($config->hasFilters());
        $this->assertNull($config->getFilters());
    }

    public function testSorters()
    {
        $config = new Config();
        $this->assertFalse($config->hasSorters());
        $this->assertNull($config->getSorters());

        $sorters = new SortersConfig();
        $config->setSorters($sorters);
        $this->assertTrue($config->hasSorters());
        $this->assertSame($sorters, $config->getSorters());

        $config->setSorters();
        $this->assertFalse($config->hasSorters());
        $this->assertNull($config->getSorters());
    }

    public function testActions()
    {
        $config = new Config();
        $this->assertFalse($config->hasActions());
        $this->assertNull($config->getActions());

        $actions = new ActionsConfig();
        $config->setActions($actions);
        $this->assertTrue($config->hasActions());
        $this->assertSame($actions, $config->getActions());

        $config->setActions();
        $this->assertFalse($config->hasActions());
        $this->assertNull($config->getActions());
    }

    public function testSubresources()
    {
        $config = new Config();
        $this->assertFalse($config->hasSubresources());
        $this->assertNull($config->getSubresources());

        $subresources = new SubresourcesConfig();
        $config->setSubresources($subresources);
        $this->assertTrue($config->hasSubresources());
        $this->assertSame($subresources, $config->getSubresources());

        $config->setSubresources();
        $this->assertFalse($config->hasSubresources());
        $this->assertNull($config->getSubresources());
    }

    public function testToArrayAndGetIterator()
    {
        $config = new Config();
        $this->assertTrue($config->isEmpty());
        $this->assertEquals([], $config->toArray());
        $this->assertEmpty(iterator_to_array($config->getIterator()));

        $definition = new EntityDefinitionConfig();
        $definition->set('definition_attr', 'definition_val');
        $config->setDefinition($definition);

        $filters = new FiltersConfig();
        $filters->set('filters_attr', 'filters_val');
        $config->setFilters($filters);

        $sorters = new SortersConfig();
        $sorters->set('sorters_attr', 'sorters_val');
        $config->setSorters($sorters);

        $config->set('another_section', ['another_attr' => 'another_val']);
        $config->set('extra_attr', 'extra_val');

        $this->assertFalse($config->isEmpty());
        $this->assertEquals(
            [
                'definition'      => ['definition_attr' => 'definition_val'],
                'filters'         => ['filters_attr' => 'filters_val'],
                'sorters'         => ['sorters_attr' => 'sorters_val'],
                'another_section' => ['another_attr' => 'another_val'],
                'extra_attr'      => 'extra_val',
            ],
            $config->toArray()
        );
        $this->assertEquals(
            [
                'definition'      => $definition,
                'filters'         => $filters,
                'sorters'         => $sorters,
                'another_section' => ['another_attr' => 'another_val'],
                'extra_attr'      => 'extra_val',
            ],
            iterator_to_array($config->getIterator())
        );
    }

    public function testClone()
    {
        $config = new Config();
        $config->set('key', 'val');
        $definition = new EntityDefinitionConfig();
        $definition->setExcludeAll();
        $config->setDefinition($definition);
        $filters = new FiltersConfig();
        $filters->setExcludeAll();
        $config->setFilters($filters);
        $sorters = new SortersConfig();
        $sorters->setExcludeAll();
        $config->setSorters($sorters);
        $actions = new ActionsConfig();
        $actions->addAction('test');
        $config->setActions($actions);
        $subresources = new SubresourcesConfig();
        $subresources->addSubresource('test');
        $config->setSubresources($subresources);

        $configClone = clone $config;
        self::assertNotSame($config, $configClone);
        self::assertEquals($config, $configClone);
        self::assertNotSame($config->getDefinition(), $configClone->getDefinition());
        self::assertEquals($config->getDefinition(), $configClone->getDefinition());
        self::assertNotSame($config->getFilters(), $configClone->getFilters());
        self::assertEquals($config->getFilters(), $configClone->getFilters());
        self::assertNotSame($config->getSorters(), $configClone->getSorters());
        self::assertEquals($config->getSorters(), $configClone->getSorters());
        self::assertNotSame($config->getActions(), $configClone->getActions());
        self::assertEquals($config->getActions(), $configClone->getActions());
        self::assertNotSame($config->getSubresources(), $configClone->getSubresources());
        self::assertEquals($config->getSubresources(), $configClone->getSubresources());
    }
}
