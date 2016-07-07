<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Provider\ActivityWidgetProvider;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ActivityWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldImplementWidgetProviderInterface()
    {
        $rc = new \ReflectionClass(ActivityWidgetProvider::class);

        $this->assertTrue($rc->implementsInterface(WidgetProviderInterface::class));
    }

    public function testCouldBeConstructedWithExpectedSetOfArguments()
    {
        new ActivityWidgetProvider(
            $this->createActivityManagerMock(),
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );
    }

    public function testShouldReturnTrueOnSupportsIfActivityHasAssociations()
    {
        $entity = new \stdClass;

        $activityManagerMock = $this->createActivityManagerMock();
        $activityManagerMock
            ->expects($this->once())
            ->method('hasActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn(true)
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $this->assertTrue($provider->supports($entity));
    }

    public function testShouldReturnFalseOnSupportsIfActivityHasNoAssociations()
    {
        $entity = new \stdClass;

        $activityManagerMock = $this->createActivityManagerMock();
        $activityManagerMock
            ->expects($this->once())
            ->method('hasActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn(false)
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $this->assertFalse($provider->supports($entity));
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

        $activityManagerMock = $this->createActivityManagerMock();
        $activityManagerMock
            ->expects($this->once())
            ->method('getActivityAssociations')
            ->with(\stdClass::class)
            ->willReturn($activities)
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $securityFacadeMock = $this->createSecurityFacadeMock();
        $securityFacadeMock
            ->expects($this->at(0))
            ->method('isGranted')
            ->with('theFooAcl')
            ->willReturn(true)
        ;
        $securityFacadeMock
            ->expects($this->at(1))
            ->method('isGranted')
            ->with('theBarAcl')
            ->willReturn(true)
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $securityFacadeMock,
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $securityFacadeMock = $this->createSecurityFacadeMock();
        $securityFacadeMock
            ->expects($this->at(0))
            ->method('isGranted')
            ->with('theFooAcl')
            ->willReturn(false)
        ;
        $securityFacadeMock
            ->expects($this->at(1))
            ->method('isGranted')
            ->with('theBarAcl')
            ->willReturn(false)
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $securityFacadeMock,
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $entityIdAccessorMock = $this->createEntityIdAccessorMock();
        $entityIdAccessorMock
            ->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('theEntityId')
        ;

        $entityRoutingHelperMock = $this->createEntityRoutingHelperMock();
        $entityRoutingHelperMock
            ->expects($this->at(0))
            ->method('generateUrl')
            ->with('theFooRoute', \stdClass::class, 'theEntityId')
            ->willReturn('theFooUrl')
        ;
        $entityRoutingHelperMock
            ->expects($this->at(1))
            ->method('generateUrl')
            ->with('theBarRoute', \stdClass::class, 'theEntityId')
            ->willReturn('theBarUrl')
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $entityIdAccessorMock,
            $entityRoutingHelperMock
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $translatorMock = $this->createTranslatorMock();
        $translatorMock
            ->expects($this->at(0))
            ->method('trans')
            ->with('theFooLabel')
            ->willReturn('theFooLabelTranslated')
        ;
        $translatorMock
            ->expects($this->at(1))
            ->method('trans')
            ->with('theBarLabel')
            ->willReturn('theBarLabelTranslated')
        ;

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $translatorMock,
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

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

        $activityManagerMock = $this->createActivityManagerStub($activities);

        $provider = new ActivityWidgetProvider(
            $activityManagerMock,
            $this->createSecurityFacadeMock(),
            $this->createTranslatorMock(),
            $this->createEntityIdAccessorMock(),
            $this->createEntityRoutingHelperMock()
        );

        $widgets = $provider->getWidgets($entity);

        //guard
        $this->assertInternalType('array', $widgets);
        $this->assertCount(2, $widgets);

        $this->assertEquals('thefooclassname_6a3e4d5a_theFooAssociationName', $widgets[0]['alias']);
        $this->assertEquals('thebarclassname_e91e577b_theBarAssociationName', $widgets[1]['alias']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActivityManager
     */
    private function createActivityManagerMock()
    {
        return $this->getMock(ActivityManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActivityManager
     */
    private function createActivityManagerStub($activities)
    {
        $activityManagerMock = $this->createActivityManagerMock();
        $activityManagerMock
            ->expects($this->any())
            ->method('getActivityAssociations')
            ->willReturn($activities)
        ;
        
        return $activityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private function createSecurityFacadeMock()
    {
        return $this->getMock(SecurityFacade::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function createTranslatorMock()
    {
        return $this->getMock(TranslatorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityIdAccessor
     */
    private function createEntityIdAccessorMock()
    {
        return $this->getMock(EntityIdAccessor::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRoutingHelper
     */
    private function createEntityRoutingHelperMock()
    {
        return $this->getMock(EntityRoutingHelper::class, [], [], '', false);
    }
}
