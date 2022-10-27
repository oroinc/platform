<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class BreadcrumbManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Matcher|\PHPUnit\Framework\MockObject\MockObject */
    private $matcher;

    /** @var BuilderChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var MenuFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var BreadcrumbManager */
    private $manager;

    protected function setUp(): void
    {
        $this->matcher = $this->createMock(Matcher::class);
        $this->provider = $this->createMock(BuilderChainProvider::class);
        $this->factory = $this->createMock(MenuFactory::class);

        $this->manager = new BreadcrumbManager($this->provider, $this->matcher);
    }

    public function testGetBreadcrumbs()
    {
        $item = new MenuItem('test', $this->factory);
        $subItem = new MenuItem('sub_item_test', $this->factory);
        $subItem->setCurrent(true);
        $item->addChild($subItem);

        $this->provider->expects($this->once())
            ->method('get')
            ->with('test', ['check_access_not_logged_in' => true])
            ->willReturn($item);

        $this->matcher->expects($this->any())
            ->method('isCurrent')
            ->with($subItem)
            ->willReturn(true);

        $breadcrumbs = $this->manager->getBreadcrumbs('test', false);
        $this->assertEquals('sub_item_test', $breadcrumbs[0]['label']);
    }

    public function testGetBreadcrumbsWithoutItem()
    {
        $item = new MenuItem('test', $this->factory);

        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn($item);

        $this->assertNull($this->manager->getBreadcrumbs('nullable'));
    }

    public function testGetBreadcrumbsWithRoute()
    {
        $item = new MenuItem('test', $this->factory);
        $item->setExtra('routes', [
            'test_route',
        ]);
        $item1 = new MenuItem('test1', $this->factory);
        $item2 = new MenuItem('sub_item', $this->factory);
        $item1->addChild($item2);
        $item1->setExtra('routes', []);
        $item2->addChild($item);

        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn($item1);

        $this->assertEquals(
            [
                [
                    'label' => $item->getLabel(),
                    'uri' => $item->getUri(),
                    'item' => $item
                ],
                [
                    'label' => $item2->getLabel(),
                    'uri' => $item2->getUri(),
                    'item' => $item2
                ],
                [
                    'label' => $item1->getLabel(),
                    'uri' => $item1->getUri(),
                    'item' => $item1
                ],
            ],
            $this->manager->getBreadcrumbs('test_menu', false, 'test_route')
        );
    }

    public function testGetBreadcrumbLabels()
    {
        $item = new MenuItem('test', $this->factory);
        $item->setExtra('routes', [
            'another_route',
            '/another_route/',
            'another*route',
            'test_route',
        ]);
        $item1 = new MenuItem('test1', $this->factory);
        $item2 = new MenuItem('sub_item', $this->factory);
        $item1->addChild($item2);
        $item1->setExtra('routes', []);
        $item2->addChild($item);

        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn($item1);

        $this->assertEquals(
            ['test', 'sub_item', 'test1'],
            $this->manager->getBreadcrumbLabels('test_menu', 'test_route')
        );
    }

    public function testGetMenu()
    {
        $item = new MenuItem('testItem', $this->factory);
        $subItem = new MenuItem('subItem', $this->factory);
        $item->addChild($subItem);
        $this->provider->expects($this->any())
            ->method('get')
            ->willReturn($item);

        $resultMenu = $this->manager->getMenu('test', ['subItem']);
        $this->assertEquals($subItem, $resultMenu);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->getMenu('test', ['bad_item']);
    }

    public function testGetCurrentMenuItem()
    {
        $item = new MenuItem('testItem', $this->factory);
        $goodItem = new MenuItem('goodItem', $this->factory);
        $subItem = new MenuItem('subItem', $this->factory);
        $goodItem->addChild($subItem);

        $params = [
            'testItem' => false,
            'goodItem' => false,
            'subItem' => true,
        ];

        $this->matcher->expects($this->any())
            ->method('isCurrent')
            ->with(
                $this->logicalOr(
                    $this->equalTo($item),
                    $this->equalTo($goodItem),
                    $this->equalTo($subItem)
                )
            )
            ->willReturnCallback(function ($param) use (&$params) {
                /** @var MenuItem $param */
                return $params[$param->getLabel()];
            });

        $this->assertEquals($subItem, $this->manager->getCurrentMenuItem([$item, $goodItem]));
    }
}
