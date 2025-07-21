<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MenuNodeDefinitionTest extends TestCase
{
    private MenuTreeBuilder $builder;
    private MenuNodeDefinition $definition;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new MenuTreeBuilder();

        $this->definition = new MenuNodeDefinition('test');
        $this->definition->setBuilder($this->builder);
    }

    public function testMenuNodeHierarchyZeroDepth(): void
    {
        $this->assertInstanceOf(
            MenuNodeDefinition::class,
            $this->definition->menuNodeHierarchy(0)
        );
    }

    public function testMenuNodeHierarchyNonZeroDepth(): void
    {
        $result = $this->definition->menuNodeHierarchy();

        $this->assertInstanceOf(ArrayNodeDefinition::class, $result);
    }
}
