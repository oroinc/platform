<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class NavigationHistoryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var NavigationHistoryBuilder
     */
    protected $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manipulator;

    /** @var  Router */
    protected $router;

    /** @var  FeatureChecker */
    protected $featureChecker;

    /**
     * @var \Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = $this->getMock('Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory');
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder')
            ->setConstructorArgs(array($this->securityContext, $this->em, $this->factory, $this->router))
            ->setMethods(array('getMenuManipulator', 'set', 'isRouteEnabled'))
            ->getMock();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manipulator = $this->getMock('Knp\Menu\Util\MenuManipulator');
        $this->builder->expects($this->any())->method('getMenuManipulator')
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

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->securityContext->expects($this->atLeastOnce())
            ->method('getToken')
            ->will($this->returnValue($token));

        $item = $this->getMock('Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface');
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

        $childMock = $this->getMock('Knp\Menu\ItemInterface');
        $childMock2 = clone $childMock;
        $children = array($childMock, $childMock2);

        $matcher = $this->getMock('\Knp\Menu\Matcher\Matcher');
        $matcher->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue(true));

        $this->builder->setMatcher($matcher);

        $this->router->expects($this->exactly(2))
            ->method('match')
            ->with($this->isType('string'))
            ->willReturn(['_route' => 'route']);

        $this->builder->expects($this->exactly(2))
            ->method('isRouteEnabled')
            ->with($this->anything())
            ->willReturn(true);
        $menu->expects($this->exactly(2))
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

        $this->builder->setOptions($configMock);
        $this->builder->build($menu, array(), $type);
    }
}
