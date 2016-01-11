<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonActiveTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonManagedTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActivityListChainProvider */
    protected $activityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActivityListChainProvider */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $doctrine;

    /** @var PlaceholderFilter */
    protected $filter;

    /** @var ConfigProviderMock */
    protected $configProvider;

    /** @var array */
    protected $entities = [];

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->activityListProvider = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityManager = $this
            ->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityListProvider->expects($this->any())
            ->method('getTargetEntityClasses')
            ->will($this->returnValue(['Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget']));

        $this->doctrineHelper = $this
            ->getMockBuilder('\Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->setMethods(['isNewEntity', 'isManageableEntity'])
            ->setConstructorArgs([$this->doctrine])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->will($this->returnCallback(function ($entity) {
                if (method_exists($entity, 'getId')) {
                    return !(bool)$entity->getId();
                }

                throw new \RuntimeException('Something wrong');
            }));

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(function ($entity) {
                return !$entity instanceof TestNonManagedTarget;
            });

        $configManager        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = new ConfigProviderMock($configManager, 'activity');

        $this->filter = new PlaceholderFilter(
            $this->activityListProvider,
            $this->doctrine,
            $this->doctrineHelper,
            $this->configProvider,
            $this->activityManager
        );
    }

    public function testIsApplicable()
    {
        $testTarget = new TestTarget(1);

        $entityClass = get_class($testTarget);
        $this->configProvider->addEntityConfig(
            $entityClass,
            [ActivityScope::SHOW_ON_PAGE => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::VIEW_PAGE']
        );
        $this->configProvider->addFieldConfig($entityClass, 'associationField');

        $this->activityManager
            ->expects($this->once())
            ->method('getActivityAssociations')
            ->with($entityClass)
            ->willReturn(
                [
                    ['className' => $entityClass, 'associationName' => 'associationField']
                ]
            );

        $this->assertTrue($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
        $this->assertFalse($this->filter->isApplicable(null, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableWithNonManagedEntity()
    {
        $testTarget = new TestNonManagedTarget(1);
        $this->assertFalse($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableWithShowOnPageConfiguration()
    {
        $entity = new TestTarget(1);

        $entityClass = get_class($entity);
        $this->configProvider->addEntityConfig(
            $entityClass,
            [ActivityScope::SHOW_ON_PAGE => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE']
        );
        $this->configProvider->addFieldConfig($entityClass, 'associationField');
        $this->activityManager->expects($this->exactly(2))
            ->method('getActivityAssociations')
            ->with($entityClass)
            ->willReturn([
                ['className' => $entityClass, 'associationName' => 'associationField']
            ]);

        $this->assertFalse($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));

        $this->configProvider->addEntityConfig(
            $entityClass,
            [ActivityScope::SHOW_ON_PAGE => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::VIEW_UPDATE_PAGES']
        );
        $this->assertTrue($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableOnNonSupportedTarget()
    {
        $repo = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('getRecordsCountForTargetClassAndId')
            ->with('Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonActiveTarget', 123)
            ->willReturn(true);

        $entity = new TestNonActiveTarget(123);

        $entityClass = get_class($entity);
        $this->configProvider->addEntityConfig(
            $entityClass,
            [ActivityScope::SHOW_ON_PAGE => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::VIEW_PAGE']
        );
        $this->configProvider->addFieldConfig($entityClass, 'associationField');

        $this->activityManager
            ->expects($this->once())
            ->method('getActivityAssociations')
            ->with($entityClass)
            ->willReturn(
                [
                    ['className' => $entityClass, 'associationName' => 'associationField']
                ]
            );

        $this->assertTrue($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsAllowedButtonWithUnknownPageConstant()
    {
        $entity = new TestTarget(1);

        $this->configProvider->addEntityConfig(
            get_class($entity),
            [ActivityScope::SHOW_ON_PAGE => 'UNKNOWN_ORO_CONSTANT']
        );

        $event = new BeforeGroupingChainWidgetEvent(ActivityScope::SHOW_ON_PAGE, [], $entity);
        $this->filter->isAllowedButton($event);
    }

    /**
     * @dataProvider   isAllowedButtonProvider
     *
     * @param int      $pageType
     * @param array    $widgets
     * @param object   $entity
     * @param int|null $configProviderSetting
     * @param array    $expected
     */
    public function testIsAllowedButton($pageType, $widgets, $entity, $configProviderSetting, $expected)
    {
        if ($configProviderSetting !== null) {
            $this->configProvider->addEntityConfig(
                get_class($entity),
                [ActivityScope::SHOW_ON_PAGE => $configProviderSetting]
            );
        }
        $event = new BeforeGroupingChainWidgetEvent($pageType, $widgets, $entity);
        $this->filter->isAllowedButton($event);
        $this->assertEquals($expected, $event->getWidgets());
    }

    /**
     * @return array
     */
    public function isAllowedButtonProvider()
    {
        $widgets = ['array' => 'of widgets'];
        $entity  = new TestTarget(1);
        return [
            'entity with "update" activity entity config and "view" event' => [
                'groupType'             => ActivityScope::VIEW_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE',
                'expected'              => []
            ],
            'entity with "view/update" activity entity config and "view" event' => [
                'groupType'             => ActivityScope::VIEW_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::VIEW_UPDATE_PAGES',
                'expected'              => $widgets
            ],
            'entity with "view/update" activity entity config and "update" event' => [
                'groupType'             => ActivityScope::UPDATE_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => '\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::VIEW_UPDATE_PAGES',
                'expected'              => $widgets
            ],
        ];
    }
}
