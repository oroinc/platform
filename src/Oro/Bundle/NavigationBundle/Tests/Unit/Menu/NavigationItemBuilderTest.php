<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Menu\NavigationItemBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class NavigationItemBuilderBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $featureChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var NavigationItemBuilder */
    protected $builder;

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
    }

    public function testBuildAnonUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));
        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
        $menu->expects($this->never())
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', 'pinbar');

        $this->builder->build($menu, array(), 'pinbar');
    }

    public function testBuild()
    {
        $organization   = new Organization();
        $type           = 'favorite';
        $userId         = 1;
        $user = $this->getMockBuilder('stdClass')
            ->setMethods(array('getId'))
            ->getMock();
        $user->expects($this->once($userId))
            ->method('getId')
            ->will($this->returnValue(1));

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

        $repository = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $items = array(
            array('id' => 1, 'title' => 'test1', 'url' => '/', 'type' => $type),
            array('id' => 2, 'title' => 'test2', 'url' => '/home', 'type' => $type)
        );
        $repository->expects($this->once())
            ->method('getNavigationItems')
            ->with($userId, $organization, $type)
            ->will($this->returnValue($items));
        $this->router->expects($this->exactly(2))
            ->method('match')
            ->with($this->isType('string'))
            ->willReturn(['_route' => 'route']);
        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(get_class($item))
            ->will($this->returnValue($repository));

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
        $menu->expects($this->exactly(2))
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', $type);

        $this->builder->build($menu, array(), $type);
    }
}
