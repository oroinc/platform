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

    private $registry;
    private $securityFacade;

    private $gridViewsLoadListener;

    public function setUp()
    {
        $this->gridViewRepository = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $gridViewManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager')
            ->disableOriginalConstructor()
            ->getMock();
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroDataGridBundle:GridView')
            ->will($this->returnValue($this->gridViewRepository));
        $this->securityFacade
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $this->gridViewsLoadListener = new GridViewsLoadListener(
            $this->registry,
            $this->securityFacade,
            $aclHelper,
            $translator,
            $gridViewManager
        );
    }

    public function testListenerShouldAddViewsIntoEvent()
    {
        $currentUser = new User();

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($currentUser));


        $systemView = new View('first');
        $view1 = new GridView();
        $view1->setId(1);
        $view1->setOwner($currentUser);
        $view1->setName('view1');
        $view2 = new GridView();
        $view2->setId(2);
        $view2->setName('view2');
        $view2->setOwner($currentUser);
        $gridViews = [
            'system' => [
                $systemView
            ],
            'user' => [$view1, $view2]
        ];

        $event = new GridViewsLoadEvent('grid', $gridViews);

//        $this->gridViewRepository
//            ->expects($this->once())
//            ->method('findGridViews')
//            ->will($this->returnValue($gridViews));

        $expectedViews = [
            [
                'name'       => 'first',
                'label'      => 'first',
                'type'       => 'system',
                'filters'    => [],
                'sorters'    => [],
                'columns'    => [],
                'editable'   => false,
                'deletable'  => false,
                'is_default' => false,
                'shared_by'  => null,
            ],
            [
                'label'     => 'view1',
                'name'      => 1,
                'filters'   => [],
                'sorters'   => [],
                'type'      => GridView::TYPE_PRIVATE,
                'deletable' => true,
                'editable'  => true,
                'columns'   => [],
                'is_default' => false,
                'shared_by'  => null
            ],
            [
                'label'     => 'view2',
                'name'      => 2,
                'filters'   => [],
                'sorters'   => [],
                'type'      => GridView::TYPE_PRIVATE,
                'deletable' => true,
                'editable'  => true,
                'columns'   => [],
                'is_default' => false,
                'shared_by'  => null
            ],
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
