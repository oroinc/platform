<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Provider\ActivityActionWidgetProvider;

class ActivityActionWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityActionWidgetProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $placeholderProvider;

    protected function setUp()
    {
        $this->activityManager     = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->placeholderProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ActivityActionWidgetProvider(
            $this->activityManager,
            $this->placeholderProvider
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
                'widget'          => 'widget1',
            ],
            [
                'className'       => 'Test\Activity2',
                'associationName' => 'association2',
                'widget'          => 'widget2',
                'group'           => 'group2',
                'priority'        => 100,
            ],
            [
                'className'       => 'Test\Activity3',
                'associationName' => 'association3',
                'widget'          => 'widget3',
            ],
        ];

        $this->placeholderProvider->expects($this->at(0))
            ->method('getItem')
            ->with('widget1', ['entity' => $entity])
            ->will($this->returnValue(['template' => 'template1']));
        $this->placeholderProvider->expects($this->at(1))
            ->method('getItem')
            ->with('widget2', ['entity' => $entity])
            ->will($this->returnValue(['template' => 'template2']));
        $this->placeholderProvider->expects($this->at(2))
            ->method('getItem')
            ->with('widget3', ['entity' => $entity])
            ->will($this->returnValue(null));
        $this->activityManager->expects($this->once())
            ->method('getActivityActions')
            ->with($entityClass)
            ->will($this->returnValue($activities));

        $this->assertEquals(
            [
                [
                    'name'     => 'widget1',
                    'template' => 'template1',
                ],
                [
                    'name'     => 'widget2',
                    'template' => 'template2',
                    'group'    => 'group2',
                    'priority' => 100,
                ],
            ],
            $this->provider->getWidgets($entity)
        );
    }
}
