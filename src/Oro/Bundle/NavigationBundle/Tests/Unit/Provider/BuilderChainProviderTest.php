<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\MenuFactory;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuItemStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuilderChainProviderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private FactoryInterface|\PHPUnit\Framework\MockObject\MockObject $factory;

    private ArrayLoader|\PHPUnit\Framework\MockObject\MockObject $loader;

    private MenuManipulator|\PHPUnit\Framework\MockObject\MockObject $manipulator;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(MenuFactory::class);
        $this->loader = $this->createMock(ArrayLoader::class);
        $this->manipulator = $this->createMock(MenuManipulator::class);
    }

    public function testHas(): void
    {
        $options = ['param' => 'value'];

        $topMenu = $this->createMock(ItemInterface::class);
        $existingMenuName = 'test';
        $notExistingMenuName = 'unknown';

        $this->factory->expects(self::once())
            ->method('createItem')
            ->with($notExistingMenuName, $options)
            ->willReturn($topMenu);

        $chainProvider = $this->getBuilderChainProvider(
            [$existingMenuName => ['builder1']],
            ['builder1' => $this->createMock(BuilderInterface::class)]
        );
        self::assertTrue($chainProvider->has($existingMenuName, $options));
        self::assertFalse($chainProvider->has($notExistingMenuName, $options));
    }

    public function testGetWhenMenuAliasIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu alias was not set.');

        $chainProvider = $this->getBuilderChainProvider([], []);
        $chainProvider->get('');
    }

    public function testHasWhenMenuAliasIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu alias was not set.');

        $chainProvider = $this->getBuilderChainProvider([], []);
        $chainProvider->has('');
    }

    /**
     * @dataProvider aliasDataProvider
     */
    public function testGet(string $alias, string $menuName): void
    {
        $options = ['param' => 'value'];

        $item = new MenuItemStub();
        $item->setName('item');

        $menu = new MenuItemStub();
        $menu->setName('menu');
        $menu->addChild($item);

        $this->factory->expects(self::once())
            ->method('createItem')
            ->with($menuName, $options)
            ->willReturn($menu);

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects(self::once())
            ->method('build')
            ->with($menu, $options, $menuName);

        $chainProvider = $this->getBuilderChainProvider(
            [$alias => ['builder1']],
            ['builder1' => $builder]
        );
        self::assertInstanceOf(ItemInterface::class, $chainProvider->get($menuName, $options));
        self::assertInstanceOf(ItemInterface::class, $chainProvider->get($menuName, $options));
    }

    public function testGetOneMenuWithDifferentLocalCachePrefixes(): void
    {
        $options = ['param' => 'value'];
        $menuName = 'menu_name';

        $item = new MenuItemStub();
        $item->setName('item');

        $menu = new MenuItemStub();
        $menu->setName('menu');
        $menu->addChild($item);

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects(self::exactly(2))
            ->method('createItem')
            ->withConsecutive(
                [$menuName, $options],
                [$menuName, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_']]
            )
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, $options, $menuName],
                [$rebuildMenu, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'], $menuName],
            ]);

        $chainProvider = $this->getBuilderChainProvider(
            [BuilderChainProvider::COMMON_BUILDER_ALIAS => ['builder1']],
            ['builder1' => $builder]
        );
        self::assertSame($menu, $chainProvider->get($menuName, $options));
        self::assertSame(
            $rebuildMenu,
            $chainProvider->get($menuName, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'])
        );
    }

    public function testGetOneMenuWithDifferentOptions(): void
    {
        $options = ['param' => 'value'];
        $menuName = 'menu_name';

        $item = new MenuItemStub();
        $item->setName('item');

        $menu = new MenuItemStub();
        $menu->setName('menu');
        $menu->addChild($item);

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects(self::exactly(2))
            ->method('createItem')
            ->withConsecutive([$menuName, $options], [$menuName, ['foo' => 'bar']])
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, $options, $menuName],
                [$rebuildMenu, ['foo' => 'bar'], $menuName],
            ]);

        $chainProvider = $this->getBuilderChainProvider(
            [BuilderChainProvider::COMMON_BUILDER_ALIAS => ['builder1']],
            ['builder1' => $builder]
        );
        self::assertSame($menu, $chainProvider->get($menuName, $options));
        self::assertSame($rebuildMenu, $chainProvider->get($menuName, ['foo' => 'bar']));
    }

    public function testGetCached(): void
    {
        $options = ['param' => 'value'];

        $alias = 'test_menu';
        $items = ['name' => $alias];
        $menu = $this->createMock(ItemInterface::class);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects(self::once())
            ->method('getItem')
            ->with($alias)
            ->willReturn($cacheItem);
        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($items);

        $this->loader->expects(self::once())
            ->method('load')
            ->with($items)
            ->willReturn($menu);

        $this->factory->expects(self::never())
            ->method('createItem');

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects(self::never())
            ->method('build');

        $chainProvider = $this->getBuilderChainProvider(
            [$alias => ['builder1']],
            ['builder1' => $builder]
        );
        $chainProvider->setCache($cache);

        self::assertInstanceOf(ItemInterface::class, $chainProvider->get($alias, $options));
    }

    public function aliasDataProvider(): array
    {
        return [
            'custom' => ['test', 'test'],
            'global' => ['_common_builder', 'test']
        ];
    }

    public function testSorting(): void
    {
        $menuName = 'test_menu';
        $options = ['param' => 'value'];

        $topMenu = $this->createMock(ItemInterface::class);
        $topMenu->expects(self::any())
            ->method('count')
            ->willReturn(4);
        $topMenu->expects(self::any())
            ->method('getDisplayChildren')
            ->willReturn(true);

        $menu = $this->createMock(ItemInterface::class);
        $menu->expects(self::any())
            ->method('count')
            ->willReturn(1);
        $menu->expects(self::any())
            ->method('getDisplayChildren')
            ->willReturn(true);

        $childOne = $this->getChildItem('child1');
        $childTwo = $this->getChildItem('child2', -10);
        $childThree = $this->getChildItem('child3', 10);
        $childFour = $this->getChildItem('child4');

        $menu->expects(self::any())
            ->method('getChildren')
            ->willReturn(
                [
                    $childOne->getName() => $childOne,
                    $childTwo->getName() => $childTwo,
                    $childThree->getName() => $childThree,
                    $childFour->getName() => $childFour
                ]
            );

        $topMenu->expects(self::any())
            ->method('getChildren')
            ->willReturn([$menu->getName() => $menu]);

        $this->factory->expects(self::once())
            ->method('createItem')
            ->with($menuName, $options)
            ->willReturn($topMenu);

        $menu->expects(self::once())
            ->method('reorderChildren')
            ->with(['child2', 'child1', 'child4', 'child3']);

        $chainProvider = $this->getBuilderChainProvider([], []);
        $newMenu = $chainProvider->get($menuName, $options);
        self::assertInstanceOf(ItemInterface::class, $newMenu);
    }

    private function getBuilderChainProvider(array $builders, array $builderServices): BuilderChainProvider
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($builderServices as $serviceId => $builder) {
            $containerBuilder->add($serviceId, $builder);
        }

        return new BuilderChainProvider(
            $builders,
            $containerBuilder->getContainer($this),
            $this->factory,
            $this->loader,
            $this->manipulator
        );
    }

    private function getChildItem(string $name, int $position = null): ItemInterface
    {
        return $this->createItem($name)
            ->setExtra(MenuUpdateInterface::POSITION, $position);
    }
}
