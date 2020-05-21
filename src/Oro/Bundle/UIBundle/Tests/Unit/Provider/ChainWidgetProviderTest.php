<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ChainWidgetProvider;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

class ChainWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $highPriorityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $lowPriorityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $unsupportedProvider;

    /** @var ChainWidgetProvider */
    private $chainProvider;

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

    public function testSupports()
    {
        $this->assertTrue($this->chainProvider->supports(new \stdClass()));
    }

    public function testGetWidgets()
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
