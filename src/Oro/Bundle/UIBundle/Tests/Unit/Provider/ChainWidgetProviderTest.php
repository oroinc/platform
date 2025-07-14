<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ChainWidgetProvider;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainWidgetProviderTest extends TestCase
{
    private WidgetProviderInterface&MockObject $highPriorityProvider;
    private WidgetProviderInterface&MockObject $lowPriorityProvider;
    private WidgetProviderInterface&MockObject $unsupportedProvider;
    private ChainWidgetProvider $chainProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->highPriorityProvider = $this->createMock(WidgetProviderInterface::class);
        $this->lowPriorityProvider = $this->createMock(WidgetProviderInterface::class);
        $this->unsupportedProvider = $this->createMock(WidgetProviderInterface::class);

        $this->chainProvider = new ChainWidgetProvider([
            $this->lowPriorityProvider,
            $this->highPriorityProvider,
            $this->unsupportedProvider
        ]);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->chainProvider->supports(new \stdClass()));
    }

    public function testGetWidgets(): void
    {
        $entity = new \stdClass();

        $lowPriorityProviderWidgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2', 'priority' => 100],
            ['name' => 'widget3'],
            ['name' => 'widget4', 'priority' => -100],
            ['name' => 'widget5'],
        ];

        $highPriorityProviderWidgets = [
            ['name' => 'widget11'],
            ['name' => 'widget12', 'priority' => -200],
            ['name' => 'widget13', 'priority' => -100],
            ['name' => 'widget14', 'priority' => 100],
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
                ['name' => 'widget12'],
                ['name' => 'widget4'],
                ['name' => 'widget13'],
                ['name' => 'widget1'],
                ['name' => 'widget3'],
                ['name' => 'widget5'],
                ['name' => 'widget11'],
                ['name' => 'widget2'],
                ['name' => 'widget14'],
                ['name' => 'widget15'],
            ],
            $this->chainProvider->getWidgets($entity)
        );
    }
}
