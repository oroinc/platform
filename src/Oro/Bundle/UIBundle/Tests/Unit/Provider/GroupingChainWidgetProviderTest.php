<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\UIBundle\Provider\GroupingChainWidgetProvider;
use Oro\Bundle\UIBundle\Provider\LabelProviderInterface;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

class GroupingChainWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|WidgetProviderInterface */
    protected $highPriorityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|WidgetProviderInterface */
    protected $lowPriorityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|WidgetProviderInterface */
    protected $unsupportedProvider;

    protected function setUp()
    {
        $this->highPriorityProvider =
            $this->getMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');
        $this->lowPriorityProvider  =
            $this->getMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');
        $this->unsupportedProvider  =
            $this->getMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');
    }

    public function testSupports()
    {
        $chainProvider = $this->getChainProvider(false, false);
        $this->assertTrue($chainProvider->supports(new \stdClass()));
    }

    public function testGetWidgetsWithoutGroupNameProvider()
    {
        $chainProvider = $this->getChainProvider();

        $entity = new \stdClass();

        $lowPriorityProviderWidgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2', 'priority' => 100],
            ['name' => 'widget3', 'group' => 'test_group'],
            ['name' => 'widget4', 'priority' => -100],
            ['name' => 'widget5'],
        ];

        $highPriorityProviderWidgets = [
            ['name' => 'widget11'],
            ['name' => 'widget12', 'priority' => -200, 'group' => 'test_group'],
            ['name' => 'widget13', 'priority' => -100],
            ['name' => 'widget14', 'priority' => 100, 'group' => 'test_group'],
            ['name' => 'widget15', 'priority' => 200],
        ];

        $this->lowPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(true));
        $this->highPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(true));
        $this->unsupportedProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(false));

        $this->lowPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($lowPriorityProviderWidgets));
        $this->highPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($highPriorityProviderWidgets));
        $this->unsupportedProvider->expects($this->never())
            ->method('getWidgets');

        $this->assertEquals(
            [
                ''           => [
                    'widgets' => [
                        ['name' => 'widget4'],
                        ['name' => 'widget13'],
                        ['name' => 'widget1'],
                        ['name' => 'widget5'],
                        ['name' => 'widget11'],
                        ['name' => 'widget2'],
                        ['name' => 'widget15'],
                    ]
                ],
                'test_group' => [
                    'widgets' => [
                        ['name' => 'widget12'],
                        ['name' => 'widget3'],
                        ['name' => 'widget14'],
                    ]
                ],
            ],
            $chainProvider->getWidgets($entity)
        );
    }

    public function testGetWidgetsWithGroupNameProvider()
    {
        $chainProvider = $this->getChainProvider(true);

        $entity = new \stdClass();

        $lowPriorityProviderWidgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2', 'priority' => 100],
            ['name' => 'widget3', 'group' => 'test_group'],
            ['name' => 'widget4', 'priority' => -100],
            ['name' => 'widget5'],
        ];

        $highPriorityProviderWidgets = [
            ['name' => 'widget11'],
            ['name' => 'widget12', 'priority' => -200, 'group' => 'test_group'],
            ['name' => 'widget13', 'priority' => -100],
            ['name' => 'widget14', 'priority' => 100, 'group' => 'test_group'],
            ['name' => 'widget15', 'priority' => 200],
        ];

        $this->lowPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(true));
        $this->highPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(true));
        $this->unsupportedProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(false));

        $this->lowPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($lowPriorityProviderWidgets));
        $this->highPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($highPriorityProviderWidgets));
        $this->unsupportedProvider->expects($this->never())
            ->method('getWidgets');

        $this->assertEquals(
            [
                ''           => [
                    'widgets' => [
                        ['name' => 'widget4'],
                        ['name' => 'widget13'],
                        ['name' => 'widget1'],
                        ['name' => 'widget5'],
                        ['name' => 'widget11'],
                        ['name' => 'widget2'],
                        ['name' => 'widget15'],
                    ]
                ],
                'test_group' => [
                    'label'   => 'test_group - stdClass',
                    'widgets' => [
                        ['name' => 'widget12'],
                        ['name' => 'widget3'],
                        ['name' => 'widget14'],
                    ]
                ],
            ],
            $chainProvider->getWidgets($entity)
        );
    }

    /**
     * @param bool $withGroupNameProvider
     * @param bool $setEventDispatcher
     * @return GroupingChainWidgetProvider
     */
    protected function getChainProvider($withGroupNameProvider = false, $setEventDispatcher = true)
    {
        $groupNameProvider = null;
        if ($withGroupNameProvider) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|LabelProviderInterface $groupNameProvider */
            $groupNameProvider = $this->getMock('Oro\Bundle\UIBundle\Provider\LabelProviderInterface');
            $groupNameProvider->expects($this->any())
                ->method('getLabel')
                ->will(
                    $this->returnCallback(
                        function ($parameters) {
                            return $parameters['groupName'] . ' - ' . $parameters['entityClass'];
                        }
                    )
                );
        }

        if ($setEventDispatcher) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
            $eventDispatcher->expects($this->once())
                ->method('dispatch');
        }

        $chainProvider = new GroupingChainWidgetProvider(
            $groupNameProvider,
            isset($eventDispatcher) ? $eventDispatcher : null
        );

        $chainProvider->addProvider($this->lowPriorityProvider);
        $chainProvider->addProvider($this->highPriorityProvider);
        $chainProvider->addProvider($this->unsupportedProvider);

        return $chainProvider;
    }
}
