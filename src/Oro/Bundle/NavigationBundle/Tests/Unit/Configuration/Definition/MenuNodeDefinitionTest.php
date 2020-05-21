<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;

class MenuNodeDefinitionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var MenuNodeDefinition */
    private $definition;

    protected function setUp(): void
    {
        $this->builder = $this->getMockBuilder(MenuTreeBuilder::class)
            ->setMethods([
                'node', 'children', 'scalarNode', 'end', 'menuNode', 'menuNodeHierarchy', 'defaultValue'
            ])
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
            ->will($this->returnSelf());

        $this->builder->expects($this->any())
            ->method('children')
            ->will($this->returnSelf());

        $this->builder->expects($this->any())
            ->method('scalarNode')
            ->will($this->returnSelf());

        $this->builder->expects($this->any())
            ->method('end')
            ->will($this->returnSelf());

        $this->builder->expects($this->once())
            ->method('menuNode')
            ->with('children')
            ->will($this->returnSelf());

        $this->builder->expects($this->once())
            ->method('menuNodeHierarchy')
            ->with(9)
            ->will($this->returnSelf());
        $this->builder->expects($this->any())
            ->method('defaultValue')
            ->will($this->returnSelf());

        $this->definition->menuNodeHierarchy(10);
    }
}
