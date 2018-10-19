<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuItemStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuilderChainProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FactoryInterface
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ArrayLoader
     */
    protected $loader;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MenuManipulator
     */
    protected $manipulator;

    /**
     * @var BuilderChainProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Knp\Menu\MenuFactory')
            ->getMock();

        $this->loader = $this->getMockBuilder('Knp\Menu\Loader\ArrayLoader')
            ->setConstructorArgs([$this->factory])
            ->getMock();
        $this->manipulator = $this->getMockBuilder('Knp\Menu\Util\MenuManipulator')
            ->getMock();

        $this->provider = new BuilderChainProvider(
            $this->factory,
            $this->loader,
            $this->manipulator
        );
    }

    public function testAddBuilder()
    {
        $builder = $this->getMenuBuilderMock();
        $this->provider->addBuilder($builder, 'builder1');
        $this->provider->addBuilder($builder, 'builder1');
        $this->provider->addBuilder($builder, 'builder2');
        $this->assertAttributeCount(2, 'builders', $this->provider);
        $expectedBuilders = ['builder1' => [$builder, $builder], 'builder2' => [$builder]];
        $this->assertAttributeEquals($expectedBuilders, 'builders', $this->provider);
    }

    public function testHas()
    {
        $topMenu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
        $existingMenuName = 'test';
        $notExistingMenuName = 'unknown';

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($notExistingMenuName)
            ->will($this->returnValue($topMenu));

        $this->provider->addBuilder($this->getMenuBuilderMock(), $existingMenuName);
        $this->assertTrue($this->provider->has($existingMenuName));
        $this->assertFalse($this->provider->has($notExistingMenuName));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Menu alias was not set.
     */
    public function testGetException()
    {
        $this->provider->get('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Menu alias was not set.
     */
    public function testHasException()
    {
        $this->provider->has('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Menu alias was not set.
     */
    public function testAddBuilderException()
    {
        $this->provider->addBuilder($this->getMenuBuilderMock(), '');
    }

    /**
     * @dataProvider aliasDataProvider
     * @param string $alias
     * @param string $menuName
     */
    public function testGet($alias, $menuName)
    {
        $options = [];

        $item = new MenuItemStub();

        $menu = new MenuItemStub();
        $menu->addChild($item);

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $builder = $this->getMenuBuilderMock();
        $builder->expects($this->once())
            ->method('build')
            ->with($menu, $options, $menuName);
        $this->provider->addBuilder($builder, $alias);

        $this->assertInstanceOf('Knp\Menu\ItemInterface', $this->provider->get($menuName, $options));
        $this->assertInstanceOf('Knp\Menu\ItemInterface', $this->provider->get($menuName, $options));

        $this->assertAttributeCount(1, 'menus', $this->provider);
    }

    public function testGetOneMenuWithDifferentLocalCachePrefixes()
    {
        $menuName = 'menu_name';

        $menu = new MenuItemStub();
        $menu->addChild(new MenuItemStub());

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects($this->exactly(2))
            ->method('createItem')
            ->with($menuName)
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->getMenuBuilderMock();
        $builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, [], $menuName],
                [$rebuildMenu, [BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'], $menuName],
            ]);

        $this->provider->addBuilder($builder);

        $this->assertSame($menu, $this->provider->get($menuName, []));
        $this->assertSame($rebuildMenu, $this->provider->get($menuName, [
            BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'custom_'
        ]));

        $this->assertAttributeCount(2, 'menus', $this->provider);
    }

    public function testGetOneMenuWithDifferentOptions()
    {
        $menuName = 'menu_name';

        $menu = new MenuItemStub();
        $menu->addChild(new MenuItemStub());

        $rebuildMenu = clone $menu;
        $rebuildMenu->setAttribute('custom', true);

        $this->factory->expects($this->exactly(2))
            ->method('createItem')
            ->with($menuName)
            ->willReturn($menu, $rebuildMenu);

        $builder = $this->getMenuBuilderMock();
        $builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap([
                [$menu, [], $menuName],
                [$rebuildMenu, ['foo' => 'bar'], $menuName],
            ]);

        $this->provider->addBuilder($builder);

        $this->assertSame($menu, $this->provider->get($menuName, []));
        $this->assertSame($rebuildMenu, $this->provider->get($menuName, [
            'foo' => 'bar'
        ]));

        $this->assertAttributeCount(2, 'menus', $this->provider);
    }

    public function testGetCached()
    {
        $options = [];

        $alias = 'test_menu';
        $items = ['name' => $alias];
        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();

        $cache = $this->getMockBuilder('Doctrine\Common\Cache\ArrayCache')
            ->getMock();

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

        $builder = $this->getMenuBuilderMock();
        $builder->expects($this->never())
            ->method('build');

        $this->provider->addBuilder($builder, $alias);
        $this->provider->setCache($cache);

        $this->assertInstanceOf('Knp\Menu\ItemInterface', $this->provider->get($alias, $options));
        $this->assertAttributeCount(1, 'menus', $this->provider);
    }

    /**
     * @return array
     */
    public function aliasDataProvider()
    {
        return [
            'custom alias' => ['test', 'test'],
            'global' => [BuilderChainProvider::COMMON_BUILDER_ALIAS, 'test']
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|BuilderInterface
     */
    protected function getMenuBuilderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\NavigationBundle\Menu\BuilderInterface')
            ->getMock();
    }

    public function testSorting()
    {
        $menuName = 'test_menu';
        $options = [];

        $topMenu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();

        $topMenu->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue(true));

        $topMenu->expects($this->any())
            ->method('getDisplayChildren')
            ->will($this->returnValue(true));

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();

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
            ->with($menuName)
            ->will($this->returnValue($topMenu));

        $menu->expects($this->once())
            ->method('reorderChildren')
            ->with(['child1', 'child2', 'child3', 'child4']);

        $newMenu = $this->provider->get($menuName, $options);
        $this->assertInstanceOf('Knp\Menu\ItemInterface', $newMenu);
    }

    /**
     * @param  string                                   $name
     * @param  null|int                                 $position
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getChildItem($name, $position = null)
    {
        $child = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
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
