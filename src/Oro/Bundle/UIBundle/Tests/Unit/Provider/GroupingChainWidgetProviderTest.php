<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\GroupingChainWidgetProvider;
use Oro\Bundle\UIBundle\Provider\LabelProviderInterface;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupingChainWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $highPriorityProvider;

    /** @var WidgetProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lowPriorityProvider;

    /** @var WidgetProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $unsupportedProvider;

    protected function setUp(): void
    {
        $this->highPriorityProvider = $this->createMock(WidgetProviderInterface::class);
        $this->lowPriorityProvider = $this->createMock(WidgetProviderInterface::class);
        $this->unsupportedProvider = $this->createMock(WidgetProviderInterface::class);
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
            ->willReturn(true);
        $this->highPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn(true);
        $this->unsupportedProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn(false);

        $this->lowPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->willReturn($lowPriorityProviderWidgets);
        $this->highPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->willReturn($highPriorityProviderWidgets);
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
            ->willReturn(true);
        $this->highPriorityProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn(true);
        $this->unsupportedProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn(false);

        $this->lowPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->willReturn($lowPriorityProviderWidgets);
        $this->highPriorityProvider->expects($this->once())
            ->method('getWidgets')
            ->with($this->identicalTo($entity))
            ->willReturn($highPriorityProviderWidgets);
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

    private function getChainProvider(
        bool $withGroupNameProvider = false,
        bool $setEventDispatcher = true
    ): GroupingChainWidgetProvider {
        $groupNameProvider = null;
        if ($withGroupNameProvider) {
            $groupNameProvider = $this->createMock(LabelProviderInterface::class);
            $groupNameProvider->expects($this->any())
                ->method('getLabel')
                ->willReturnCallback(function ($parameters) {
                    return $parameters['groupName'] . ' - ' . $parameters['entityClass'];
                });
        }

        $pageType = null;
        $eventDispatcher = null;
        if ($setEventDispatcher) {
            $pageType = 1;
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher->expects($this->once())
                ->method('dispatch');
        }

        $providerContainer = TestContainerBuilder::create()
            ->add('low_priority_provider', $this->lowPriorityProvider)
            ->add('high_priority_provider', $this->highPriorityProvider)
            ->add('unsupported_provider', $this->unsupportedProvider)
            ->getContainer($this);

        return new GroupingChainWidgetProvider(
            [['low_priority_provider', null], ['high_priority_provider', null], ['unsupported_provider', null]],
            $providerContainer,
            $groupNameProvider,
            $eventDispatcher,
            $pageType
        );
    }
}
