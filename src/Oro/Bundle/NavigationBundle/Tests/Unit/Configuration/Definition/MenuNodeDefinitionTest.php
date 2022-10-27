<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;

class MenuNodeDefinitionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MenuTreeBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var MenuNodeDefinition */
    private $definition;

    protected function setUp(): void
    {
        $this->builder = $this->getMockBuilder(MenuTreeBuilder::class)
            ->onlyMethods(['node', 'scalarNode', 'end', 'menuNode'])
            ->addMethods(['children', 'menuNodeHierarchy', 'defaultValue'])
            ->getMock();

        $this->definition = new MenuNodeDefinition('test');
        $this->definition->setBuilder($this->builder);
    }

    public function testMenuNodeHierarchyZeroDepth()
    {
        $this->builder->expects($this->never())
            ->method('node');

        $this->assertInstanceOf(
            MenuNodeDefinition::class,
            $this->definition->menuNodeHierarchy(0)
        );
    }

    public function testMenuNodeHierarchyNonZeroDepth()
    {
        $this->builder->expects($this->any())
            ->method('node')
            ->willReturnSelf();
        $this->builder->expects($this->any())
            ->method('children')
            ->willReturnSelf();
        $this->builder->expects($this->any())
            ->method('scalarNode')
            ->willReturnSelf();
        $this->builder->expects($this->any())
            ->method('end')
            ->willReturnSelf();
        $this->builder->expects($this->once())
            ->method('menuNode')
            ->with('children')
            ->willReturnSelf();
        $this->builder->expects($this->once())
            ->method('menuNodeHierarchy')
            ->with(9)
            ->willReturnSelf();
        $this->builder->expects($this->any())
            ->method('defaultValue')
            ->willReturnSelf();

        $this->definition->menuNodeHierarchy(10);
    }
}
