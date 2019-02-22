<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\RouterInterface;

class NavigationHistoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var MenuManipulator|\PHPUnit\Framework\MockObject\MockObject */
    protected $manipulator;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var NavigationHistoryBuilder */
    protected $builder;

    /** @var NavigationItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $navigationItemsProvider;

    /** @var \Knp\Menu\Matcher\Matcher|\PHPUnit\Framework\MockObject\MockObject */
    private $matcher;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->factory = $this->createMock(ItemFactory::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->navigationItemsProvider = $this->createMock(NavigationItemsProviderInterface::class);
        $this->matcher = $this->createMock(\Knp\Menu\Matcher\Matcher::class);
        $this->manipulator = $this->createMock(MenuManipulator::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->builder = new NavigationHistoryBuilder($this->tokenAccessor, $this->em, $this->factory, $this->router);
        $this->builder->setConfigManager($this->configManager);
        $this->builder->setManipulator($this->manipulator);
        $this->builder->setMatcher($this->matcher);
        $this->builder->setFeatureChecker($this->featureChecker);
        $this->builder->addFeature('email');
    }

    public function testBuild(): void
    {
        $this->builder->setNavigationItemsProvider($this->navigationItemsProvider);

        $organization = new Organization();
        $type = 'history';

        $user = $this->createMock(User::class);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->navigationItemsProvider
            ->expects(self::once())
            ->method('getNavigationItems')
            ->with($user, $organization, $type)
            ->willReturn($items = [
                ['id' => 1, 'title' => 'sample-title-1', 'url' => '', 'route' => 'sample_route_1', 'type' => $type],
                ['id' => 2, 'title' => 'sample-title-2', 'url' => '', 'route' => 'sample_route_2', 'type' => $type],
            ]);

        $menu = $this->createMock(\Knp\Menu\MenuItem::class);

        $childMock = $this->createMock(\Knp\Menu\ItemInterface::class);
        $childMock2 = clone $childMock;
        $children = array($childMock, $childMock2);

        $this->matcher
            ->expects($this->once())
            ->method('isCurrent')
            ->willReturn(true);

        $menu
            ->expects($this->exactly(2))
            ->method('addChild');

        $menu
            ->expects($this->once())
            ->method('setExtra')
            ->with('type', $type);

        $menu
            ->expects($this->once())
            ->method('getChildren')
            ->willReturn($children);

        $menu
            ->expects($this->once())
            ->method('removeChild');

        $n = random_int(1, 10);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_navigation.max_items')
            ->willReturn($n);

        $this->manipulator
            ->expects($this->once())
            ->method('slice')
            ->with($menu, 0, $n);

        $this->builder->build($menu, [], $type);
    }


    public function testBuildWhenNoNavigationItemsProvider()
    {
        $organization   = new Organization();
        $type           = 'history';
        $userId         = 1;

        $user = $this->getMockBuilder('stdClass')
            ->setMethods(array('getId'))
            ->getMock();
        $user->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($userId));

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $item = $this->createMock('Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface');
        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($type, array())
            ->will($this->returnValue($item));

        $repository = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $items = array(
            array('id' => 1, 'title' => 'test1', 'url' => '/'),
            array('id' => 2, 'title' => 'test2', 'url' => '/home'),
        );

        $repository->expects($this->once())
            ->method('getNavigationItems')
            ->with($userId, $organization, $type)
            ->will($this->returnValue($items));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(get_class($item))
            ->will($this->returnValue($repository));

        $menu = $this->getMockBuilder('Knp\Menu\MenuItem')->disableOriginalConstructor()->getMock();

        $childMock = $this->createMock('Knp\Menu\ItemInterface');
        $childMock2 = clone $childMock;
        $children = array($childMock, $childMock2);

        $matcher = $this->createMock('\Knp\Menu\Matcher\Matcher');
        $matcher->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue(true));
        $this->builder->setMatcher($matcher);
        $this->router->expects($this->exactly(0))
            ->method('match')
            ->with($this->isType('string'))
            ->willReturn(['_route' => 'route']);
        $this->featureChecker->expects($this->exactly(0))
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);
        $menu->expects($this->never())
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', $type);
        $menu->expects($this->once())
            ->method('getChildren')
            ->will($this->returnValue($children));
        $menu->expects($this->once())
            ->method('removeChild');

        $n = rand(1, 10);

        $configMock = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
                        ->disableOriginalConstructor()
                        ->getMock();
        $configMock->expects($this->once())
                        ->method('get')
                        ->with($this->equalTo('oro_navigation.max_items'))
                        ->will($this->returnValue($n));
        $this->manipulator->expects($this->once())
            ->method('slice')
            ->with($menu, 0, $n);

        $this->builder->setConfigManager($configMock);
        $this->builder->build($menu, array(), $type);
    }
}
