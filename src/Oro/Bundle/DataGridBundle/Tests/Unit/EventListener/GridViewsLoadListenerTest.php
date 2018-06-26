<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Entity\AppearanceType;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\AppearanceTypeManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\EventListener\GridViewsLoadListener;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GridViewsLoadListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $gridViewRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $appearanceTypeManager;

    /** @var GridViewsLoadListener */
    private $gridViewsLoadListener;

    public function setUp()
    {
        $this->gridViewRepository = $this->createMock(GridViewRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $aclHelper = $this->createMock(AclHelper::class);
        $gridViewManager = $this->createMock(GridViewManager::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->appearanceTypeManager = $this->createMock(AppearanceTypeManager::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroDataGridBundle:GridView')
            ->will($this->returnValue($this->gridViewRepository));

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $this->gridViewsLoadListener = new GridViewsLoadListener(
            $this->registry,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $aclHelper,
            $translator,
            $gridViewManager,
            $this->appearanceTypeManager
        );
    }

    public function testListenerShouldAddViewsIntoEvent()
    {
        $currentUser = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($currentUser));

        $systemView = new View('first');
        $view1 = new GridView();
        $view1->setId(1);
        $view1->setOwner($currentUser);
        $view1->setName('view1');
        $view1->setAppearanceType(new AppearanceType('grid'));
        $view2 = new GridView();
        $view2->setId(2);
        $view2->setName('view2');
        $view2->setAppearanceType(new AppearanceType('board'));
        $view2->setOwner($currentUser);
        $gridViews = [
            'system' => [
                $systemView
            ],
            'user' => [$view1, $view2]
        ];

        $event = new GridViewsLoadEvent('grid', $gridViews);

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
                'appearanceType' => 'grid',
                'appearanceData' => [],
                'icon' => ''
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
                'shared_by'  => null,
                'appearanceType' => 'grid',
                'appearanceData' => [],
                'icon' => ''
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
                'shared_by'  => null,
                'appearanceType' => 'board',
                'appearanceData' => [],
                'icon' => ''
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
