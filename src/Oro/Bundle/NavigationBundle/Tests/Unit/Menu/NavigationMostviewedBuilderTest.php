<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Menu\NavigationMostviewedBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

class NavigationMostviewedBuilderTest extends \PHPUnit\Framework\TestCase
{
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

    /** @var NavigationMostviewedBuilder */
    protected $builder;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->factory = $this->createMock(ItemFactory::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->builder = new NavigationMostviewedBuilder(
            $this->tokenAccessor,
            $this->em,
            $this->factory,
            $this->router
        );
        $this->builder->setFeatureChecker($this->featureChecker);
        $this->builder->addFeature('email');
    }

    public function testBuild()
    {
        $organization   = new Organization();
        $type           = 'mostviewed';
        $maxItems       = 20;
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

        $repository->expects($this->once())
            ->method('getNavigationItems')
            ->with(
                $userId,
                $organization,
                $type,
                array(
                    'max_items' => $maxItems,
                    'order_by' => array(array('field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISIT_COUNT))
                )
            )
            ->will($this->returnValue(array()));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(get_class($item))
            ->will($this->returnValue($repository));

        $configMock = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('oro_navigation.max_items'))
            ->will($this->returnValue($maxItems));

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')->getMock();

        $this->builder->setConfigManager($configMock);
        $this->builder->build($menu, array(), $type);
    }
}
