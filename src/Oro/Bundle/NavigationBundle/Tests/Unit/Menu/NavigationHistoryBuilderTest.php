<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

class NavigationHistoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $manipulator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NavigationHistoryBuilder */
    protected $builder;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->factory = $this->createMock(ItemFactory::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->builder = $this->getMockBuilder(NavigationHistoryBuilder::class)
            ->setConstructorArgs(array($this->tokenAccessor, $this->em, $this->factory, $this->router))
            ->setMethods(array('getMenuManipulator', 'set'))
            ->getMock();
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->manipulator = $this->createMock(MenuManipulator::class);

        $this->builder->expects($this->any())
            ->method('getMenuManipulator')
            ->will($this->returnValue($this->manipulator));
        $this->builder->setFeatureChecker($this->featureChecker);
        $this->builder->addFeature('email');
    }

    public function testBuild()
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
