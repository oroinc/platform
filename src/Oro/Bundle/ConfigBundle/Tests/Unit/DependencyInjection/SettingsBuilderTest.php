<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class SettingsBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSettings(): void
    {
        $config = [
            'key1' => 'val1',
            'settings' => [
                'key1' => 'val1'
            ]
        ];

        self::assertEquals(['settings' => $config['settings']], SettingsBuilder::getSettings($config));
    }

    public function testGetSettingsShouldThrowExceptionWhenNoSettingsInConfig(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must contains "settings" section.');

        SettingsBuilder::getSettings(['key1' => 'val1']);
    }

    public function testAppendBoolean(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('greeting', ReflectionUtil::getPropertyValue($children['settings'], 'children'));
    }

    public function testAppendScalar(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('level', ReflectionUtil::getPropertyValue($children['settings'], 'children'));
    }

    public function testAppendScalarWhenTypeIsNotSpecified(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('level', ReflectionUtil::getPropertyValue($children['settings'], 'children'));
    }

    public function testAppendString(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('settings', $children);
        $this->assertArrayHasKey('name', ReflectionUtil::getPropertyValue($children['settings'], 'children'));
    }

    public function testAppendArray(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');
        $settings = ReflectionUtil::getPropertyValue($children['settings'], 'children');
        $list = ReflectionUtil::getPropertyValue($settings['list'], 'children');

        $this->assertCount(2, $children);
        $this->assertArrayHasKey('value', $list);
        $this->assertInstanceOf(ArrayNodeDefinition::class, $list['value']);
    }

    public function testAppendArrayWhenTypeIsNotSpecified(): void
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

        $children = ReflectionUtil::getPropertyValue($root, 'children');
        $settings = ReflectionUtil::getPropertyValue($children['settings'], 'children');
        $list = ReflectionUtil::getPropertyValue($settings['list'], 'children');

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
}
