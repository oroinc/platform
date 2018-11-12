<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;
use Knp\Menu\ItemInterface as KnpItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository;
use Oro\Bundle\NavigationBundle\Menu\NavigationItemBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\RouterInterface;

class NavigationItemBuilderTest extends \PHPUnit\Framework\TestCase
{
    const ITEM_TYPE = 'favorite';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var NavigationItemBuilder */
    protected $builder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|KnpItemInterface */
    protected $menu;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->factory = $this->createMock(ItemFactory::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->builder = new NavigationItemBuilder(
            $this->tokenAccessor,
            $this->em,
            $this->factory,
            $this->router
        );
        $this->builder->setFeatureChecker($this->featureChecker);
        $this->builder->addFeature('email');
        $this->menu = $this->createMock(KnpItemInterface::class);
    }

    public function testBuildAnonUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));
        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        $this->menu->expects($this->never())
            ->method('addChild');
        $this->menu->expects($this->once())
            ->method('setExtra')
            ->with('type', 'pinbar');

        $this->builder->build($this->menu, [], 'pinbar');
    }

    /**
     * @param array $item
     * @param $expected
     *
     * @dataProvider itemsDataProvider
     */
    public function testBuild(array $item, $expected)
    {
        $this->configure([$item]);

        $this->router->expects($this->once())
            ->method('match')
            ->with($expected)
            ->willReturn(['_route' => 'route']);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);

        $this->menu->expects($this->once())->method('addChild');
        $this->menu->expects($this->once())->method('setExtra')->with('type', self::ITEM_TYPE);

        $this->builder->build($this->menu, array(), self::ITEM_TYPE);
    }

    /**
     * @param array $item
     * @param $expected
     *
     * @dataProvider itemsDataProvider
     */
    public function testBuildDisabledFeature(array $item, $expected)
    {
        $this->configure([$item]);

        $this->router->expects($this->once())
            ->method('match')
            ->with($expected)
            ->willReturn(['_route' => 'route']);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(false);

        $this->menu->expects($this->never())->method('addChild');
        $this->menu->expects($this->once())->method('setExtra')->with('type', self::ITEM_TYPE);

        $this->builder->build($this->menu, array(), self::ITEM_TYPE);
    }

    /**
     * @param array $item
     * @param $expected
     *
     * @dataProvider itemsDataProvider
     */
    public function testBuildWithMissedRoute(array $item, $expected)
    {
        $this->configure([$item]);

        $this->router->expects($this->exactly(1))
            ->method('match')
            ->with($expected)
            ->willReturn(null);

        $this->featureChecker->expects($this->never())
            ->method('isResourceEnabled');

        $this->menu->expects($this->never())->method('addChild');
        $this->menu->expects($this->once())->method('setExtra')->with('type', self::ITEM_TYPE);

        $this->builder->build($this->menu, array(), self::ITEM_TYPE);
    }

    /**
     * @return \Generator
     */
    public function itemsDataProvider()
    {
        yield [
            'item' => ['id' => 1, 'title' => 'test1', 'url' => null, 'type' => self::ITEM_TYPE],
            'expected' => '',
        ];
        yield [
            'item' => ['id' => 1, 'title' => 'test1', 'url' => '/', 'type' => self::ITEM_TYPE],
            'expected' => '/',
        ];
        yield [
            'item' => ['id' => 2, 'title' => 'test2', 'url' => '/home', 'type' => self::ITEM_TYPE],
            'expected' => '/home',
        ];
        yield [
            'item' => ['id' => 3, 'title' => 'test2', 'url' => '/test?s=123', 'type' => self::ITEM_TYPE],
            'expected' => '/test',
        ];
        yield [
            'item' => ['id' => 3, 'title' => 'test2', 'url' => '/test#s=123', 'type' => self::ITEM_TYPE],
            'expected' => '/test',
        ];
        yield [
            'item' => ['id' => 3, 'title' => 'test2', 'url' => '/test?d=123#s=123', 'type' => self::ITEM_TYPE],
            'expected' => '/test',
        ];
    }

    /**
     * @param array $items
     */
    protected function configure(array $items)
    {
        $organization = new Organization();
        $userId = 1;
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn($userId);

        $this->tokenAccessor->expects($this->once())->method('getUser')->willReturn($user);
        $this->tokenAccessor->expects($this->once())->method('getOrganization')->willReturn($organization);

        $item = $this->createMock(NavigationItemInterface::class);
        $this->factory->expects($this->once())->method('createItem')->with(self::ITEM_TYPE, [])->willReturn($item);

        $repository = $this->createMock(NavigationItemRepository::class);
        $repository->expects($this->once())
            ->method('getNavigationItems')
            ->with($userId, $organization, self::ITEM_TYPE)
            ->willReturn($items);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(get_class($item))
            ->willReturn($repository);
    }
}
