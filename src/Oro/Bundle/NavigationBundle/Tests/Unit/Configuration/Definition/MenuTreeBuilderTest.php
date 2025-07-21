<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration\Definition;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuNodeDefinition;
use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;
use PHPUnit\Framework\TestCase;

class MenuTreeBuilderTest extends TestCase
{
    public function testConstructorSetsMenuMapping(): void
    {
        $builder = new MenuTreeBuilder();
        self::assertInstanceOf(MenuNodeDefinition::class, $builder->node('menu', 'menu'));
    }

    public function testMenuNode(): void
    {
        $nodeDefinition = (new MenuTreeBuilder())->menuNode('test');
        self::assertInstanceOf(MenuNodeDefinition::class, $nodeDefinition);
        self::assertEquals('test', $nodeDefinition->getNode()->getName());
    }
}
