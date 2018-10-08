<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\GridViews;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GridViewsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject */
    protected $serviceLink;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var GridViewsExtension */
    private $gridViewsExtension;

    public function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->authorizationChecker->expects($this->any())->method('isGranted')->willReturn(true);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->createMock(EntityRepository::class));

        $aclHelper = $this->createMock(AclHelper::class);

        $this->serviceLink = $this->createMock(ServiceLink::class);

        $this->gridViewsExtension = new GridViewsExtension(
            $this->eventDispatcher,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $translator,
            $registry,
            $aclHelper,
            $this->serviceLink
        );
    }

    public function testVisitMetadataShouldAddGridViewsFromEvent()
    {
        $data   = MetadataObject::create([]);
        $config = DatagridConfiguration::create(
            [
                DatagridConfiguration::NAME_KEY => 'grid',
            ]
        );

        $this->eventDispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->with(GridViewsLoadEvent::EVENT_NAME)
            ->will($this->returnValue(true));

        $views          = [(new View('name', ['k' => 'v'], ['k2' => 'v2']))->getMetadata()];
        $expectedViews  = [
            'views' => $views,
            'permissions' => [
                'CREATE' => true,
                'EDIT' => true,
                'VIEW' => true,
                'DELETE' => true,
                'SHARE' => true,
            ],
            'gridName' => 'grid',
        ];

        $this->serviceLink->expects($this->any())->method('getService')->willReturn(new GridViewManagerStub());

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(GridViewsLoadEvent::EVENT_NAME)
            ->will(
                $this->returnCallback(
                    function ($eventName, GridViewsLoadEvent $event) use ($views) {
                        $event->setGridViews($views);

                        return $event;
                    }
                )
            );

        $this->assertFalse($data->offsetExists('gridViews'));
        $this->gridViewsExtension->setParameters(new ParameterBag());
        $this->gridViewsExtension->visitMetadata($config, $data);
        $this->assertTrue($data->offsetExists('gridViews'));
        $this->assertEquals($expectedViews, $data->offsetGet('gridViews'));
    }

    public function testVisitMetadataForCachedDefaultView()
    {
        $user = new User();
        $grid1 = 'test_grid_1';
        $grid2 = 'test_grid_2';
        $view1 = $this->getEntity(GridView::class, ['id' => 'view1']);
        $view2 = $this->getEntity(GridView::class, ['id' => 'view2']);

        $this->tokenAccessor->expects($this->any())->method('getUser')->willReturn($user);

        /** @var GridViewManager|\PHPUnit\Framework\MockObject\MockObject $gridViewManager */
        $gridViewManager = $this->createMock(GridViewManager::class);
        $gridViewManager->expects($this->any())
            ->method('getDefaultView')
            ->willReturnMap(
                [
                    [$user, $grid1, $view1],
                    [$user, $grid2, $view2]
                ]
            );

        $this->serviceLink->expects($this->any())->method('getService')->willReturn($gridViewManager);

        $this->assertGridStateView($grid1, 'view1');

        // check local cache of grid view
        $this->assertGridStateView($grid2, 'view2');
    }

    /**
     * @param string $grid
     * @param string $expectedGridView
     */
    protected function assertGridStateView($grid, $expectedGridView = null)
    {
        $data = MetadataObject::create([]);
        $config = DatagridConfiguration::create([DatagridConfiguration::NAME_KEY => $grid]);

        $this->assertFalse($data->offsetExists('state'));
        $this->gridViewsExtension->setParameters(new ParameterBag());
        $this->gridViewsExtension->visitMetadata($config, $data);
        $this->assertTrue($data->offsetExists('state'));
        $this->assertEquals(['gridView' => $expectedGridView], $data->offsetGet('state'));
    }

    /**
     * @param array $input
     * @param bool  $expected
     *
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable($input, $expected)
    {
        $this->gridViewsExtension->setParameters(new ParameterBag($input));
        $config = DatagridConfiguration::create(
            [
                DatagridConfiguration::NAME_KEY => 'grid',
            ]
        );
        $this->assertEquals($expected, $this->gridViewsExtension->isApplicable($config));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            'Default'            => [
                'input'    => [],
                'expected' => true,
            ],
            'Extension disabled' => [
                'input'    => [
                    '_grid_view' => [
                        '_disabled' => true
                    ]
                ],
                'expected' => false,
            ],
            'Extension enabled'  => [
                'input'    => [
                    '_grid_view' => [
                        '_disabled' => false
                    ]
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->gridViewsExtension->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->gridViewsExtension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return array(
            'empty' => array(
                'input' => array(),
                'expected' => array(),
            ),
            'regular' => array(
                'input' => array(
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                ),
                'expected' => array(
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                )
            ),
            'minified' => array(
                'input' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        GridViewsExtension::MINIFIED_VIEWS_PARAM_KEY => 'view'
                    )
                ),
                'expected' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        GridViewsExtension::MINIFIED_VIEWS_PARAM_KEY => 'view'
                    ),
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                )
            ),
        );
    }
}
