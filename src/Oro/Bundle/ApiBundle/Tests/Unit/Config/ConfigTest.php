<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomAttribute()
    {
        $attrName = 'test';

        $config = new Config();
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

    public function testDefinition()
    {
        $config = new Config();
        self::assertFalse($config->hasDefinition());
        self::assertNull($config->getDefinition());

        $definition = new EntityDefinitionConfig();
        $config->setDefinition($definition);
        self::assertTrue($config->hasDefinition());
        self::assertSame($definition, $config->getDefinition());

        $config->setDefinition();
        self::assertFalse($config->hasDefinition());
        self::assertNull($config->getDefinition());
    }

    public function testFilters()
    {
        $config = new Config();
        self::assertFalse($config->hasFilters());
        self::assertNull($config->getFilters());

        $filters = new FiltersConfig();
        $config->setFilters($filters);
        self::assertTrue($config->hasFilters());
        self::assertSame($filters, $config->getFilters());

        $config->setFilters();
        self::assertFalse($config->hasFilters());
        self::assertNull($config->getFilters());
    }

    public function testSorters()
    {
        $config = new Config();
        self::assertFalse($config->hasSorters());
        self::assertNull($config->getSorters());

        $sorters = new SortersConfig();
        $config->setSorters($sorters);
        self::assertTrue($config->hasSorters());
        self::assertSame($sorters, $config->getSorters());

        $config->setSorters();
        self::assertFalse($config->hasSorters());
        self::assertNull($config->getSorters());
    }

    public function testActions()
    {
        $config = new Config();
        self::assertFalse($config->hasActions());
        self::assertNull($config->getActions());

        $actions = new ActionsConfig();
        $config->setActions($actions);
        self::assertTrue($config->hasActions());
        self::assertSame($actions, $config->getActions());

        $config->setActions();
        self::assertFalse($config->hasActions());
        self::assertNull($config->getActions());
    }

    public function testSubresources()
    {
        $config = new Config();
        self::assertFalse($config->hasSubresources());
        self::assertNull($config->getSubresources());

        $subresources = new SubresourcesConfig();
        $config->setSubresources($subresources);
        self::assertTrue($config->hasSubresources());
        self::assertSame($subresources, $config->getSubresources());

        $config->setSubresources();
        self::assertFalse($config->hasSubresources());
        self::assertNull($config->getSubresources());
    }

    public function testToArrayAndGetIterator()
    {
        $config = new Config();
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
        self::assertEmpty(iterator_to_array($config->getIterator()));

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

        self::assertFalse($config->isEmpty());
        self::assertEquals(
            [
                'definition'      => ['definition_attr' => 'definition_val'],
                'filters'         => ['filters_attr' => 'filters_val'],
                'sorters'         => ['sorters_attr' => 'sorters_val'],
                'another_section' => ['another_attr' => 'another_val'],
                'extra_attr'      => 'extra_val'
            ],
            $config->toArray()
        );
        self::assertEquals(
            [
                'definition'      => $definition,
                'filters'         => $filters,
                'sorters'         => $sorters,
                'another_section' => ['another_attr' => 'another_val'],
                'extra_attr'      => 'extra_val'
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
