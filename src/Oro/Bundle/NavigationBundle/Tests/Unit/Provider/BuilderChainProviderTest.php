<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\MenuFactory;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuItemStub;
use Oro\Component\Testing\Unit\TestContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuilderChainProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FactoryInterface */
    private $factory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ArrayLoader */
    private $loader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MenuManipulator */
    private $manipulator;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(MenuFactory::class);
        $this->loader = $this->getMockBuilder(ArrayLoader::class)
            ->setConstructorArgs([$this->factory])
            ->getMock();
        $this->manipulator = $this->createMock(MenuManipulator::class);
    }

    public function testHas()
    {
        $options = ['param' => 'value'];

        $topMenu = $this->createMock(ItemInterface::class);
        $existingMenuName = 'test';
        $notExistingMenuName = 'unknown';

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($notExistingMenuName, $options)
            ->will($this->returnValue($topMenu));

        $chainProvider = $this->getBuilderChainProvider(
            [$existingMenuName => ['builder1']],
            ['builder1' => $this->createMock(BuilderInterface::class)]
        );
        $this->assertTrue($chainProvider->has($existingMenuName, $options));
        $this->assertFalse($chainProvider->has($notExistingMenuName, $options));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Menu alias was not set.
     */
    public function testGetException()
    {
        $chainProvider = $this->getBuilderChainProvider([], []);
        $chainProvider->get('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Menu alias was not set.
     */
    public function testHasException()
    {
        $chainProvider = $this->getBuilderChainProvider([], []);
        $chainProvider->has('');
    }

    /**
     * @dataProvider aliasDataProvider
     * @param string $alias
     * @param string $menuName
     */
    public function testGet($alias, $menuName)
    {
        $options = ['param' => 'value'];

        $item = new MenuItemStub();

        $menu = new MenuItemStub();
        $menu->addChild($item);

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($menuName, $options)
            ->will($this->returnValue($menu));

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects($this->once())
            ->method('build')
            ->with($menu, $options, $menuName);

        $chainProvider = $this->getBuilderChainProvider(
            [$alias => ['builder1']],
            ['builder1' => $builder]
        );
        $this->assertInstanceOf(ItemInterface::class, $chainProvider->get($menuName, $options));
        $this->assertInstanceOf(ItemInterface::class, $chainProvider->get($menuName, $options));
        $this->assertAttributeCount(1, 'menus', $chainProvider);
    }

    public function testGetOneMenuWithDifferentLocalCachePrefixes()
    {
        $options = ['param' => 'value'];
        $menuName = 'menu_name';

        $menu = new MenuItemStub();
        $menu->addChild(new MenuItemStub());

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects($this->exactly(2))
            ->method('createItem')
            ->withConsecutive(
                [$menuName, $options],
                [$menuName, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_']]
            )
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, $options, $menuName],
                [$rebuildMenu, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'], $menuName],
            ]);

        $chainProvider = $this->getBuilderChainProvider(
            [BuilderChainProvider::COMMON_BUILDER_ALIAS => ['builder1']],
            ['builder1' => $builder]
        );
        $this->assertSame($menu, $chainProvider->get($menuName, $options));
        $this->assertSame(
            $rebuildMenu,
            $chainProvider->get($menuName, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'])
        );
        $this->assertAttributeCount(2, 'menus', $chainProvider);
    }

    public function testGetOneMenuWithDifferentOptions()
    {
        $options = ['param' => 'value'];
        $menuName = 'menu_name';

        $menu = new MenuItemStub();
        $menu->addChild(new MenuItemStub());

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects($this->exactly(2))
            ->method('createItem')
            ->withConsecutive([$menuName, $options], [$menuName, ['foo' => 'bar']])
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, $options, $menuName],
                [$rebuildMenu, ['foo' => 'bar'], $menuName],
            ]);

        $chainProvider = $this->getBuilderChainProvider(
            [BuilderChainProvider::COMMON_BUILDER_ALIAS => ['builder1']],
            ['builder1' => $builder]
        );
        $this->assertSame($menu, $chainProvider->get($menuName, $options));
        $this->assertSame($rebuildMenu, $chainProvider->get($menuName, ['foo' => 'bar']));
        $this->assertAttributeCount(2, 'menus', $chainProvider);
    }

    public function testGetCached()
    {
        $options = ['param' => 'value'];

        $alias = 'test_menu';
        $items = ['name' => $alias];
        $menu = $this->createMock(ItemInterface::class);

        $cache = $this->createMock(ArrayCache::class);
        $cache->expects($this->once())
            ->method('contains')
            ->with($alias)
            ->will($this->returnValue(true));
        $cache->expects($this->once())
            ->method('fetch')
            ->with($alias)
            ->will($this->returnValue($items));

        $this->loader->expects($this->once())
            ->method('load')
            ->with($items)
            ->will($this->returnValue($menu));

        $this->factory->expects($this->never())
            ->method('createItem');

        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects($this->never())
            ->method('build');

        $chainProvider = $this->getBuilderChainProvider(
            [$alias => ['builder1']],
            ['builder1' => $builder]
        );
        $chainProvider->setCache($cache);

        $this->assertInstanceOf(ItemInterface::class, $chainProvider->get($alias, $options));
        $this->assertAttributeCount(1, 'menus', $chainProvider);
    }

    /**
     * @return array
     */
    public function aliasDataProvider()
    {
        return [
            'custom alias' => ['test', 'test'],
            'global' => ['_common_builder', 'test']
        ];
    }

    public function testSorting()
    {
        $menuName = 'test_menu';
        $options = ['param' => 'value'];

        $topMenu = $this->createMock(ItemInterface::class);
        $topMenu->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue(true));
        $topMenu->expects($this->any())
            ->method('getDisplayChildren')
            ->will($this->returnValue(true));

        $menu = $this->createMock(ItemInterface::class);
        $menu->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue(true));
        $menu->expects($this->any())
            ->method('getDisplayChildren')
            ->will($this->returnValue(true));

        $childOne = $this->getChildItem('child1', 5);
        $childTwo = $this->getChildItem('child2', 10);
        $childThree = $this->getChildItem('child3');
        $childFour = $this->getChildItem('child4');

        $menu->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$childThree, $childFour, $childTwo, $childOne]));

        $topMenu->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$menu]));

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($menuName, $options)
            ->will($this->returnValue($topMenu));

        $menu->expects($this->once())
            ->method('reorderChildren')
            ->with(['child1', 'child2', 'child3', 'child4']);

        $chainProvider = $this->getBuilderChainProvider([], []);
        $newMenu = $chainProvider->get($menuName, $options);
        $this->assertInstanceOf(ItemInterface::class, $newMenu);
    }

    /**
     * @param array $builders
     * @param array $builderServices
     *
     * @return BuilderChainProvider
     */
    private function getBuilderChainProvider(array $builders, array $builderServices)
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

    /**
     * @param string   $name
     * @param int|null $position
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getChildItem($name, $position = null)
    {
        $child = $this->createMock(ItemInterface::class);
        $child->expects($this->exactly(1))
            ->method('getExtra')
            ->will($this->returnValueMap([
                ['position', null, $position]
            ]));
        $child->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $child;
    }
}
