<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection\Imagine\Factory;

use Liip\ImagineBundle\DependencyInjection\Factory\Resolver\ResolverFactoryInterface;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Imagine\Factory\GaufretteResolverFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GaufretteResolverFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var GaufretteResolverFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new GaufretteResolverFactory();
    }

    private function processConfigTree(TreeBuilder $treeBuilder, array $configs): array
    {
        $processor = new Processor();

        return $processor->process($treeBuilder->buildTree(), $configs);
    }

    public function testShouldImplementResolverFactoryInterface(): void
    {
        self::assertInstanceOf(ResolverFactoryInterface::class, $this->factory);
    }

    public function testGetName(): void
    {
        self::assertEquals('oro_gaufrette', $this->factory->getName());
    }

    public function testCreate(): void
    {
        $container = new ContainerBuilder();

        $this->factory->create($container, 'test_resolver', [
            'file_manager_service' => 'test_file_manager_service',
            'url_prefix'           => 'testUrlPrefix',
            'cache_prefix'         => 'testCachePrefix'
        ]);

        self::assertTrue($container->hasDefinition('liip_imagine.cache.resolver.test_resolver'));

        /** @var ChildDefinition $resolverDefinition */
        $resolverDefinition = $container->getDefinition('liip_imagine.cache.resolver.test_resolver');
        self::assertInstanceOf(ChildDefinition::class, $resolverDefinition);
        self::assertSame(
            'oro_attachment.liip_imagine.cache.resolver.prototype.gaufrette',
            $resolverDefinition->getParent()
        );

        /** @var Reference $fileManagerReference */
        $fileManagerReference = $resolverDefinition->getArgument(0);
        self::assertInstanceOf(Reference::class, $fileManagerReference);
        self::assertSame('test_file_manager_service', (string)$fileManagerReference);
        self::assertSame('testUrlPrefix', $resolverDefinition->getArgument(2));
        self::assertSame('testCachePrefix', $resolverDefinition->getArgument(3));
    }

    public function testAddConfigurationWithValidOptions(): void
    {
        $treeBuilder = new TreeBuilder('oro_gaufrette');
        $rootNode = $treeBuilder->getRootNode();

        $this->factory->addConfiguration($rootNode);

        $config = $this->processConfigTree($treeBuilder, [
            'oro_gaufrette' => [
                'file_manager_service' => 'test_file_manager_service',
                'url_prefix'           => 'testUrlPrefix',
                'cache_prefix'         => 'testCachePrefix'
            ]
        ]);

        self::assertSame(
            [
                'file_manager_service' => 'test_file_manager_service',
                'url_prefix'           => 'testUrlPrefix',
                'cache_prefix'         => 'testCachePrefix'
            ],
            $config
        );
    }

    public function testAddConfigurationWithDefaultOptions(): void
    {
        $treeBuilder = new TreeBuilder('oro_gaufrette');
        $rootNode = $treeBuilder->getRootNode();

        $this->factory->addConfiguration($rootNode);

        $config = $this->processConfigTree($treeBuilder, [
            'oro_gaufrette' => [
                'file_manager_service' => 'test_file_manager_service'
            ]
        ]);

        self::assertSame(
            [
                'file_manager_service' => 'test_file_manager_service',
                'url_prefix'           => 'media/cache',
                'cache_prefix'         => ''
            ],
            $config
        );
    }

    public function testAddConfigurationWithoutFileManagerServiceOption(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The child config "file_manager_service" under "oro_gaufrette" must be configured.'
        );

        $treeBuilder = new TreeBuilder('oro_gaufrette');
        $rootNode = $treeBuilder->getRootNode();

        $this->factory->addConfiguration($rootNode);

        $this->processConfigTree($treeBuilder, [
            'oro_gaufrette' => []
        ]);
    }

    public function testAddConfigurationWithEmptyValueForUrlPrefixOption(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "oro_gaufrette.url_prefix" cannot contain an empty value, but got "".'
        );

        $treeBuilder = new TreeBuilder('oro_gaufrette');
        $rootNode = $treeBuilder->getRootNode();

        $this->factory->addConfiguration($rootNode);

        $this->processConfigTree($treeBuilder, [
            'oro_gaufrette' => [
                'file_manager_service' => 'test_file_manager_service',
                'url_prefix'           => ''
            ]
        ]);
    }
}
