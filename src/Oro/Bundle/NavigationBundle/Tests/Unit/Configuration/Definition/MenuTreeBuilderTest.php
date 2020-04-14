<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;

class MenuTreeBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MenuTreeBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new MenuTreeBuilder();
    }

    public function testConstructor()
    {
        $nodeMapping = $this->readAttribute($this->builder, 'nodeMapping');
        $this->assertArrayHasKey('menu', $nodeMapping);
        $this->assertEquals(
            MenuNodeDefinition::class,
            $nodeMapping['menu']
        );
    }

    public function testMenuNode()
    {
        $nodeDefinition = $this->builder->menuNode('test');
        $this->assertInstanceOf(
            MenuNodeDefinition::class,
            $nodeDefinition
        );
        $this->assertEquals('test', $nodeDefinition->getNode()->getName());
    }
}
