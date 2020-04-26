<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;

class MenuTreeBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorSetsMenuMapping()
    {
        $builder = new MenuTreeBuilder();
        static::assertInstanceOf(MenuNodeDefinition::class, $builder->node('menu', 'menu'));
    }

    public function testMenuNode()
    {
        $nodeDefinition = (new MenuTreeBuilder())->menuNode('test');
        static::assertInstanceOf(MenuNodeDefinition::class, $nodeDefinition);
        static::assertEquals('test', $nodeDefinition->getNode()->getName());
    }
}
