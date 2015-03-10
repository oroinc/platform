<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\EventListener\GridViewsLoadListener;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewsLoadListenerTest extends \PHPUnit_Framework_TestCase
{
    private $gridViewRepository;

    private $om;
    private $securityContext;

    private $gridViewsLoadListener;

    public function setUp()
    {
        $this->gridViewRepository = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $this->gridViewsLoadListener = new GridViewsLoadListener($this->om, $this->securityContext);

        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroDataGridBundle:GridView')
            ->will($this->returnValue($this->gridViewRepository));
    }

    public function testListenerShouldAddViewsIntoEvent()
    {
        $originalView = new View('view');
        $event = new GridViewsLoadEvent('grid', [$originalView]);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $token
            ->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(new User()));

        $this->securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $view1 = new GridView();
        $view1->setName('view1');
        $view2 = new GridView();
        $view2->setName('view2');
        $gridViews = [
            $view1,
            $view2,
        ];

        $this->gridViewRepository
            ->expects($this->once())
            ->method('findGridViews')
            ->will($this->returnValue($gridViews));

        $expectedViews = [
            $originalView,
            new View('view1'),
            new View('view2'),
        ];

        $this->gridViewsLoadListener->onViewsLoad($event);
        $this->assertEquals($expectedViews, $event->getGridViews());
    }

    public function testListenerShouldNotAddViewsIntoIfUserIsNotLoggedIn()
    {
        $originalView = new View('view');
        $event = new GridViewsLoadEvent('grid', [$originalView]);

        $this->gridViewRepository
            ->expects($this->never())
            ->method('findGridViews');

        $this->gridViewsLoadListener->onViewsLoad($event);
        $this->assertEquals([$originalView], $event->getGridViews());
    }
}
