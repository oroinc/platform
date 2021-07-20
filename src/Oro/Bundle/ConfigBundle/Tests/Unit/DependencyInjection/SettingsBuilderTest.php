<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class SettingsBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testAppendBoolean()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'greeting' => [
                    'value' => true,
                    'type'  => 'boolean'
                ]
            ]
        );

        $children = $this->getField($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('greeting', $this->getField($children['settings'], 'children'));
    }

    public function testAppendScalar()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'level' => [
                    'value' => 10,
                    'type'  => 'scalar'
                ]
            ]
        );

        $children = $this->getField($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('level', $this->getField($children['settings'], 'children'));
    }

    public function testAppendScalarWhenTypeIsNotSpecified()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'level' => [
                    'value' => 10
                ]
            ]
        );

        $children = $this->getField($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('level', $this->getField($children['settings'], 'children'));
    }

    public function testAppendString()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'name' => [
                    'value' => 'test',
                    'type'  => 'string'
                ]
            ]
        );

        $children = $this->getField($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('name', $this->getField($children['settings'], 'children'));
    }

    public function testAppendArray()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'list' => [
                    'value' => [1, 2, 3],
                    'type'  => 'array'
                ]
            ]
        );

        $children = $this->getField($root, 'children');
        $settings = $this->getField($children['settings'], 'children');
        $list = $this->getField($settings['list'], 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('value', $list);
        $this->assertInstanceOf(ArrayNodeDefinition::class, $list['value']);
    }

    public function testAppendArrayWhenTypeIsNotSpecified()
    {
        $root = $this->getRootNode();

        SettingsBuilder::append(
            $root,
            [
                'list' => [
                    'value' => [1, 2, 3]
                ]
            ]
        );

        $children = $this->getField($root, 'children');
        $settings = $this->getField($children['settings'], 'children');
        $list = $this->getField($settings['list'], 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('value', $list);
        $this->assertInstanceOf(ArrayNodeDefinition::class, $list['value']);
    }

    private function getRootNode(): ArrayNodeDefinition
    {
        $root = new ArrayNodeDefinition('root');
        $root
            ->children()
            ->scalarNode('foo')->end()
            ->end();

        return $root;
    }

    /**
     * @param object $object
     * @param string $field
     *
     * @return mixed
     */
    private function getField($object, $field)
    {
        $reflection = new \ReflectionProperty($object, $field);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
