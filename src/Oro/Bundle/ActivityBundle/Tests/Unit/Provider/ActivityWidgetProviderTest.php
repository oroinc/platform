<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Provider\ActivityWidgetProvider;

class ActivityWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityWidgetProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    protected function setUp()
    {
        $this->activityManager     = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator          = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->entityIdAccessor    = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityIdAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ActivityWidgetProvider(
            $this->activityManager,
            $this->securityFacade,
            $this->translator,
            $this->entityIdAccessor,
            $this->entityRoutingHelper
        );
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($isSupported)
    {
        $entity      = new \stdClass();
        $entityClass = 'stdClass';

        $this->activityManager->expects($this->once())
            ->method('hasActivityAssociations')
            ->with($entityClass)
            ->will($this->returnValue($isSupported));

        $this->assertEquals($isSupported, $this->provider->supports($entity));
    }

    public function supportsProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testGetWidgets()
    {
        $entity      = new \stdClass();
        $entityClass = 'stdClass';
        $entityId    = 123;

        $activities = [
            [
                'className'       => 'Test\Activity1',
                'associationName' => 'association1',
                'label'           => 'label1',
                'route'           => 'route1',
                'acl'             => 'acl1',
            ],
            [
                'className'       => 'Test\Activity2',
                'associationName' => 'association2',
                'label'           => 'label2',
                'route'           => 'route2',
                'acl'             => 'acl2',
                'priority'        => 100,
            ],
            [
                'className'       => 'Test\Activity3',
                'associationName' => 'association3',
                'label'           => 'label3',
                'route'           => 'route3',
            ],
        ];

        $this->entityIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($entityId));
        $this->activityManager->expects($this->once())
            ->method('getActivityAssociations')
            ->with($entityClass)
            ->will($this->returnValue($activities));

        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('acl1')
            ->will($this->returnValue(false));
        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('acl2')
            ->will($this->returnValue(true));

        $this->entityRoutingHelper->expects($this->at(0))
            ->method('generateUrl')
            ->with('route2', $entityClass, $entityId)
            ->will($this->returnValue('url2'));
        $this->entityRoutingHelper->expects($this->at(1))
            ->method('generateUrl')
            ->with('route3', $entityClass, $entityId)
            ->will($this->returnValue('url3'));

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('label2')
            ->will($this->returnValue('Label 2'));
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('label3')
            ->will($this->returnValue('Label 3'));

        $this->assertEquals(
            [
                [
                    'widgetType' => 'block',
                    'alias'      => 'activity2_b7f8a45c_association2',
                    'label'      => 'Label 2',
                    'url'        => 'url2',
                    'priority'   => 100,
                ],
                [
                    'widgetType' => 'block',
                    'alias'      => 'activity3_c0ff94ca_association3',
                    'label'      => 'Label 3',
                    'url'        => 'url3',
                ],
            ],
            $this->provider->getWidgets($entity)
        );
    }
}
