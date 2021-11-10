<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\Command\DebugCommand;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ConfigurationPass();
    }

    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('oro_layout.theme_extension.configuration');
        $container->register('oro_layout.layout_factory_builder');
        $container->register('oro_layout.layout.service_locator')->setArguments([[]]);
        $container->register('oro_layout.extension')
            ->setArguments([
                new Reference('service_container'),
                [],
                [],
                [],
                [],
                []
            ]);
        $container->register(DebugCommand::class)
            ->setArguments([
                new Reference('oro_layout.layout_manager'),
                new Reference('oro_layout.method_phpdoc_extractor'),
                [],
                []
            ]);

        return $container;
    }

    public function testRegisterThemeConfigExtensions(): void
    {
        $container = $this->getContainer();

        $container->register('theme_config_extension1')
            ->addTag('layout.theme_config_extension');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addExtension', [new Reference('theme_config_extension1')]]
            ],
            $container->getDefinition('oro_layout.theme_extension.configuration')->getMethodCalls()
        );
    }

    public function testRegisterThemeConfigExtensionsWhenNoExtensions(): void
    {
        $container = $this->getContainer();

        $this->compiler->process($container);

        self::assertEquals(
            [],
            $container->getDefinition('oro_layout.theme_extension.configuration')->getMethodCalls()
        );
    }

    public function testConfigureLayoutExtension(): void
    {
        $container = $this->getContainer();

        $container->register('block1', 'Test\BlockType1')
            ->addTag('layout.block_type', ['alias' => 'test_block_name1']);
        $container->register('block2', 'Test\BlockType2')
            ->addTag('layout.block_type', ['alias' => 'test_block_name2']);

        $container->register('extension1', 'Test\BlockTypeExtension1')
            ->addTag('layout.block_type_extension', ['alias' => 'test_block_name1']);
        $container->register('extension2', 'Test\BlockTypeExtension1')
            ->addTag('layout.block_type_extension', ['alias' => 'test_block_name2']);
        $container->register('extension3', 'Test\BlockTypeExtension1')
            ->addTag('layout.block_type_extension', ['alias' => 'test_block_name1', 'priority' => -10]);

        $container->register('update1', 'Test\LayoutUpdate1')
            ->addTag('layout.layout_update', ['id' => 'test_block_id1']);
        $container->register('update2', 'Test\LayoutUpdate2')
            ->addTag('layout.layout_update', ['id' => 'test_block_id2']);
        $container->register('update3', 'Test\LayoutUpdate3')
            ->addTag('layout.layout_update', ['id' => 'test_block_id1', 'priority' => -10]);

        $container->register('contextConfigurator1', 'Test\ContextConfigurator1')
            ->addTag('layout.context_configurator');
        $container->register('contextConfigurator2', 'Test\ContextConfigurator3')
            ->addTag('layout.context_configurator', ['priority' => -10]);
        $container->register('contextConfigurator3', 'Test\ContextConfigurator3')
            ->addTag('layout.context_configurator');

        $container->register('dataProvider1', 'Test\DataProvider1')
            ->addTag('layout.data_provider', ['alias' => 'test_data_provider_name1'])
            ->addTag('layout.data_provider', ['alias' => 'test_data_provider_name1_alias']);
        $container->register('dataProvider2', 'Test\DataProvider2')
            ->addTag('layout.data_provider', ['alias' => 'test_data_provider_name2']);

        $this->compiler->process($container);

        $extensionDef = $container->getDefinition('oro_layout.extension');
        self::assertEquals(
            [
                'test_block_name1' => 'block1',
                'test_block_name2' => 'block2'
            ],
            $extensionDef->getArgument(1)
        );
        self::assertEquals(
            [
                'test_block_name1' => ['extension3', 'extension1'],
                'test_block_name2' => ['extension2']
            ],
            $extensionDef->getArgument(2)
        );
        self::assertEquals(
            [
                'test_block_id1' => ['update3', 'update1'],
                'test_block_id2' => ['update2']
            ],
            $extensionDef->getArgument(3)
        );
        self::assertEquals(
            [
                'contextConfigurator2',
                'contextConfigurator1',
                'contextConfigurator3'
            ],
            $extensionDef->getArgument(4)
        );
        self::assertEquals(
            [
                'test_data_provider_name1' => 'dataProvider1',
                'test_data_provider_name1_alias' => 'dataProvider1',
                'test_data_provider_name2' => 'dataProvider2'
            ],
            $extensionDef->getArgument(5)
        );

        $debugCommandDef = $container->getDefinition(DebugCommand::class);
        self::assertEquals(
            ['test_block_name1', 'test_block_name2'],
            $debugCommandDef->getArgument(2)
        );
        self::assertEquals(
            ['test_data_provider_name1', 'test_data_provider_name1_alias', 'test_data_provider_name2'],
            $debugCommandDef->getArgument(3)
        );
    }

    public function testRegisterRenderers(): void
    {
        $container = $this->getContainer();

        $container->register('oro_layout.twig.layout_renderer');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addRenderer', ['twig', new Reference('oro_layout.twig.layout_renderer')]]
            ],
            $container->getDefinition('oro_layout.layout_factory_builder')->getMethodCalls()
        );
    }

    public function testRegisterRenderersWhenNoRenderers(): void
    {
        $container = $this->getContainer();

        $this->compiler->process($container);

        self::assertEquals(
            [],
            $container->getDefinition('oro_layout.layout_factory_builder')->getMethodCalls()
        );
    }

    public function testBlockTypeWithoutAlias(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Tag attribute "alias" is required for "block1" service.');

        $container = $this->getContainer();

        $container->register('block1', 'Test\Class1')
            ->addTag('layout.block_type');

        $this->compiler->process($container);
    }

    public function testBlockTypeExtensionWithoutAlias(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Tag attribute "alias" is required for "extension1" service.');

        $container = $this->getContainer();

        $container->register('extension1', 'Test\Class1')
            ->addTag('layout.block_type_extension');

        $this->compiler->process($container);
    }

    public function testLayoutUpdateWithoutId(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Tag attribute "id" is required for "update1" service.');

        $container = $this->getContainer();

        $container->register('update1', 'Test\Class1')
            ->addTag('layout.layout_update');

        $this->compiler->process($container);
    }

    public function testDataProviderWithoutAlias(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Tag attribute "alias" is required for "dataProvider1" service.');

        $container = $this->getContainer();

        $container->register('dataProvider1', 'Test\DataProvider1')
            ->addTag('layout.data_provider');

        $this->compiler->process($container);
    }

    public function testServiceLocatorFilledWithServicesInCompilerPass(): void
    {
        $container = $this->getContainer();
        $serviceLocator = $container->getDefinition('oro_layout.layout.service_locator');
        $this->assertCount(0, $serviceLocator->getArgument(0));

        $container->register('block1', 'Test\BlockType1')
            ->addTag('layout.block_type', ['alias' => 'test_block_name1']);
        $container->register('block2', 'Test\BlockType2')
            ->addTag('layout.block_type', ['alias' => 'test_block_name2']);

        $container->register('extension1', 'Test\BlockTypeExtension1')
            ->addTag('layout.block_type_extension', ['alias' => 'test_block_name1']);
        $container->register('extension2', 'Test\BlockTypeExtension2')
            ->addTag('layout.block_type_extension', ['alias' => 'test_block_name2', 'priority' => -10]);

        $container->register('update1', 'Test\LayoutUpdate1')
            ->addTag('layout.layout_update', ['id' => 'test_block_id1']);
        $container->register('update2', 'Test\LayoutUpdate2')
            ->addTag('layout.layout_update', ['id' => 'test_block_id2', 'priority' => -10]);

        $container->register('contextConfigurator1', 'Test\ContextConfigurator1')
            ->addTag('layout.context_configurator');
        $container->register('contextConfigurator2', 'Test\ContextConfigurator2')
            ->addTag('layout.context_configurator', ['priority' => -10]);

        $container->register('dataProvider1', 'Test\DataProvider1')
            ->addTag('layout.data_provider', ['alias' => 'test_data_provider_name1']);
        $container->register('dataProvider2', 'Test\DataProvider2')
            ->addTag('layout.data_provider', ['alias' => 'test_data_provider_name2']);

        $this->compiler->process($container);

        $this->assertCount(10, $serviceLocator->getArgument(0));
        $this->assertEquals(
            [
                'block1' =>  new Reference('block1'),
                'block2' =>  new Reference('block2'),
                'extension1' =>  new Reference('extension1'),
                'extension2' =>  new Reference('extension2'),
                'update1' =>  new Reference('update1'),
                'update2' =>  new Reference('update2'),
                'contextConfigurator1' =>  new Reference('contextConfigurator1'),
                'contextConfigurator2' =>  new Reference('contextConfigurator2'),
                'dataProvider1' =>  new Reference('dataProvider1'),
                'dataProvider2' =>  new Reference('dataProvider2'),
            ],
            $serviceLocator->getArgument(0)
        );
    }
}
