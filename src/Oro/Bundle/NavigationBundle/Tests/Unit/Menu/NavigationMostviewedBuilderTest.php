<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Menu\NavigationMostviewedBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class NavigationMostviewedBuilderTest extends \PHPUnit_Framework_TestCase
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
     * @var NavigationMostviewedBuilder
     */
    protected $builder;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var FeatureChecker
     */
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
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new NavigationMostviewedBuilder(
            $this->securityContext,
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

        $this->builder->setOptions($configMock);
        $this->builder->build($menu, array(), $type);
    }
}
