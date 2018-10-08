<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Provider\ActivityActionWidgetProvider;

class ActivityActionWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityActionWidgetProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $activityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $placeholderProvider;

    protected function setUp()
    {
        $this->activityManager     = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
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
                'button_widget'   => 'button_widget1',
                'link_widget'     => 'link_widget1',
            ],
            [
                'className'       => 'Test\Activity2',
                'associationName' => 'association2',
                'button_widget'   => 'button_widget2',
                'link_widget'     => 'link_widget2',
                'group'           => 'group2',
                'priority'        => 100,
            ],
            [
                'className'       => 'Test\Activity3',
                'associationName' => 'association3',
                'button_widget'   => 'button_widget3',
                'link_widget'     => 'link_widget3',
            ],
        ];

        $this->placeholderProvider->expects($this->any())
            ->method('getItem')
            ->will(
                $this->returnValueMap(
                    [
                        ['button_widget1', ['entity' => $entity], ['template' => 'button_template1']],
                        ['link_widget1', ['entity' => $entity], ['template' => 'link_template1']],
                        ['button_widget2', ['entity' => $entity], ['template' => 'button_template2']],
                        ['link_widget2', ['entity' => $entity], null],
                        ['button_widget3', ['entity' => $entity], null],
                    ]
                )
            );
        $this->activityManager->expects($this->once())
            ->method('getActivityActions')
            ->with($entityClass)
            ->will($this->returnValue($activities));

        $this->assertEquals(
            [
                [
                    'name'   => 'button_widget1',
                    'button' => [
                        'template' => 'button_template1'
                    ],
                    'link'   => [
                        'template' => 'link_template1'
                    ],
                ],
                [
                    'name'     => 'button_widget2',
                    'button'   => [
                        'template' => 'button_template2'
                    ],
                    'group'    => 'group2',
                    'priority' => 100,
                ],
            ],
            $this->provider->getWidgets($entity)
        );
    }
}
