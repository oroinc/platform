<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config\Tree;

use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;

class GroupNodeDefinitionTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NAME  = 'testNodeName';

    private static function getTestGroup(): GroupNodeDefinition
    {
        $node1 = new GroupNodeDefinition('node1', ['priority' => 255], []);
        $node3 = new GroupNodeDefinition('node3', ['priority' => 250], []);

        return new GroupNodeDefinition('node4', [], [$node1, $node3]);
    }

    public function testGetName()
    {
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertEquals(self::TEST_NAME, $node->getName());
    }

    public function testPriority()
    {
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertSame(0, $node->getPriority());

        $priority = 100;
        $node = new GroupNodeDefinition(self::TEST_NAME, ['priority' => $priority]);
        $this->assertSame($priority, $node->getPriority());

        $priority = 255;
        $node->setPriority($priority);
        $this->assertSame($priority, $node->getPriority());
    }

    public function testGetSetLevel()
    {
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertSame(0, $node->getLevel());

        $level = 2;
        $node->setLevel($level);
        $this->assertSame($level, $node->getLevel());
    }

    public function testCount()
    {
        // empty node
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertEquals(0, $node->count());

        // not empty node
        $node = self::getTestGroup();
        $this->assertEquals(2, $node->count());
    }

    public function testIsEmpty()
    {
        // empty node
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertTrue($node->isEmpty());

        // not empty node
        $node = self::getTestGroup();
        $this->assertFalse($node->isEmpty());
    }

    public function testFirst()
    {
        // empty node
        $node = new GroupNodeDefinition(self::TEST_NAME);
        $this->assertFalse($node->first());

        // not empty node
        $node = self::getTestGroup();
        $this->assertEquals('node1', $node->first()->getName());
    }

    /**
     * @dataProvider nodeDefinitionProvider
     */
    public function testToBlockConfig(GroupNodeDefinition $node)
    {
        $result = $node->toBlockConfig();

        $this->assertArrayHasKey($node->getName(), $result);
        $result = $result[$node->getName()];

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('priority', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('description_style', $result);
        $this->assertArrayHasKey('page_reload', $result);
        $this->assertArrayHasKey('configurator', $result);
        $this->assertArrayHasKey('handler', $result);
        $this->assertArrayHasKey('tooltip', $result);
        $this->assertArrayNotHasKey('some_another', $result);
        $this->assertArrayNotHasKey('icon', $result);
        $this->assertCount(8, $result);
    }

    /**
     * @dataProvider nodeDefinitionProvider
     */
    public function testToViewData(GroupNodeDefinition $node)
    {
        $result = $node->toViewData();

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('priority', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('description_style', $result);
        $this->assertArrayHasKey('icon', $result);
        $this->assertArrayHasKey('tooltip', $result);
        $this->assertArrayNotHasKey('some_another', $result);
        $this->assertArrayNotHasKey('page_reload', $result);
        $this->assertArrayNotHasKey('configurator', $result);
        $this->assertArrayNotHasKey('handler', $result);
        $this->assertCount(6, $result);
    }

    public function nodeDefinitionProvider(): array
    {
        $node = new GroupNodeDefinition(
            self::TEST_NAME,
            [
                'title'        => 'some title',
                'priority'     => 123,
                'description'  => 'some desc',
                'description_style'  => 'class_style',
                'icon'         => 'real icon',
                'page_reload'  => true,
                'configurator' => ['Test\Class::method'],
                'handler'      => ['Test\Class::method'],
                'some_another' => '',
                'tooltip'      => 'some tooltip'
            ]
        );

        return [
            [$node]
        ];
    }
}
