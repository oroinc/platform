<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ChainWidgetProvider;

class ChainWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainWidgetProvider */
    protected $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $highPriorityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $lowPriorityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $unsupportedProvider;

    protected function setUp()
    {
        $this->chainProvider = new ChainWidgetProvider();

        $this->highPriorityProvider =
            $this->createMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');
        $this->lowPriorityProvider  =
            $this->createMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');
        $this->unsupportedProvider  =
            $this->createMock('Oro\Bundle\UIBundle\Provider\WidgetProviderInterface');

        $this->chainProvider->addProvider($this->lowPriorityProvider);
        $this->chainProvider->addProvider($this->highPriorityProvider);
        $this->chainProvider->addProvider($this->unsupportedProvider);
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
