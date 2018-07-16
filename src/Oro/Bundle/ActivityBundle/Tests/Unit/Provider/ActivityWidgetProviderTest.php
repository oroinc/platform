<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Provider\ActivityWidgetProvider;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ActivityWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityIdAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var ActivityWidgetProvider */
    private $provider;

    protected function setUp()
    {
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->entityIdAccessor = $this->createMock(EntityIdAccessor::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);

        $this->provider = new ActivityWidgetProvider(
            $this->activityManager,
            $this->authorizationChecker,
            $this->translator,
            $this->entityIdAccessor,
            $this->entityRoutingHelper
        );
    }

    public function testShouldReturnTrueOnSupportsIfActivityHasAssociations()
    {
        $entity = new \stdClass;

        $this->activityManager->expects($this->once())
            ->method('hasActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn(true);

        $this->assertTrue($this->provider->supports($entity));
    }

    public function testShouldReturnFalseOnSupportsIfActivityHasNoAssociations()
    {
        $entity = new \stdClass;

        $this->activityManager->expects($this->once())
            ->method('hasActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn(false);

        $this->assertFalse($this->provider->supports($entity));
    }

    public function testShouldFindActivitiesAssociatedWithEntityClassAndConvertThemToWidgets()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->once())
            ->method('getActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn($activities);

        $widgets = $this->provider->getWidgets($entity);

        $this->assertInternalType('array', $widgets);
        $this->assertCount(3, $widgets);
    }

    public function testShouldKeepWidgetsWithAclIfAccessGranted()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'acl' => 'theFooAcl',
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'acl' => 'theBarAcl',
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('theFooAcl')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with('theBarAcl')
            ->willReturn(true);

        $widgets = $this->provider->getWidgets($entity);

        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);
    }

    public function testShouldSkipWidgetsWithAclIfAccessDenied()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'acl' => 'theFooAcl',
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'acl' => 'theBarAcl',
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('theFooAcl')
            ->willReturn(false);
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with('theBarAcl')
            ->willReturn(false);

        $widgets = $this->provider->getWidgets($entity);

        $this->assertInternalType('array', $widgets);
        $this->assertCount(0, $widgets);
    }

    public function testShouldGenerateEntityUrlAndSetItToEachWidget()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'theFooRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'theBarRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $this->entityIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('theEntityId');

        $this->entityRoutingHelper->expects($this->at(0))
            ->method('generateUrl')
            ->with('theFooRoute', \stdClass::class, 'theEntityId')
            ->willReturn('theFooUrl');
        $this->entityRoutingHelper->expects($this->at(1))
            ->method('generateUrl')
            ->with('theBarRoute', \stdClass::class, 'theEntityId')
            ->willReturn('theBarUrl');

        $widgets = $this->provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertEquals('theFooUrl', $widgets[0]['url']);
        $this->assertEquals('theBarUrl', $widgets[1]['url']);
    }

    public function testShouldTranslateActivityLabelAndSetItToEachWidget()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'theFooLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'theBarLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('theFooLabel')
            ->willReturn('theFooLabelTranslated');
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('theBarLabel')
            ->willReturn('theBarLabelTranslated');

        $widgets = $this->provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertEquals('theFooLabelTranslated', $widgets[0]['label']);
        $this->assertEquals('theBarLabelTranslated', $widgets[1]['label']);
    }

    public function testMustAlwaysSetBlockWidgetTypeToEachWidget()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $widgets = $this->provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertEquals('block', $widgets[0]['widgetType']);
        $this->assertEquals('block', $widgets[1]['widgetType']);
    }

    public function testShouldAddPriorityToWidgetIfSetInActivity()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'aClassName',
                'associationName' => 'aAssociationName',
                'label' => 'aLabel',
                'priority' => 12,
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $widgets = $this->provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertFalse(isset($widgets[0]['priority']));

        $this->assertTrue(isset($widgets[1]['priority']));
        $this->assertEquals(12, $widgets[1]['priority']);
    }

    public function testShouldAddWidgetAliasBasedOnEntityClassAndAssociationNameToEachWidget()
    {
        $entity = new \stdClass;

        $activities = [
            [
                'route' => 'aRoute',
                'className' => 'theFooClassName',
                'associationName' => 'theFooAssociationName',
                'label' => 'aLabel',
            ],
            [
                'route' => 'aRoute',
                'className' => 'theBarClassName',
                'associationName' => 'theBarAssociationName',
                'label' => 'aLabel',
            ]
        ];

        $this->activityManager->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities);

        $widgets = $this->provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertEquals('thefooclassname_6a3e4d5a_theFooAssociationName', $widgets[0]['alias']);
        $this->assertEquals('thebarclassname_e91e577b_theBarAssociationName', $widgets[1]['alias']);
    }
}
