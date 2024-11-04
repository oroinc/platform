<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MenuNodeDefinitionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MenuTreeBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var MenuNodeDefinition */
    private $definition;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new MenuTreeBuilder();

        $this->definition = new MenuNodeDefinition('test');
        $this->definition->setBuilder($this->builder);
    }

    public function testMenuNodeHierarchyZeroDepth()
    {
        $this->assertInstanceOf(
            MenuNodeDefinition::class,
            $this->definition->menuNodeHierarchy(0)
        );
    }

    public function testMenuNodeHierarchyNonZeroDepth()
    {
        $result = $this->definition->menuNodeHierarchy();

        $this->assertInstanceOf(ArrayNodeDefinition::class, $result);
    }
}
