<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;

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
}
