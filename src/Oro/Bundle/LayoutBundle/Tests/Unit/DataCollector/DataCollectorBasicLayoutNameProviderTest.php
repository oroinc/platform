<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Oro\Bundle\LayoutBundle\DataCollector\DataCollectorBasicLayoutNameProvider;
use Oro\Component\Layout\LayoutContext;

class DataCollectorBasicLayoutNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getNameByContextDataProvider
     */
    public function testGetNameByContext(LayoutContext $context, string $expected): void
    {
        $provider = new DataCollectorBasicLayoutNameProvider();

        self::assertEquals($expected, $provider->getNameByContext($context));
    }

    public function getNameByContextDataProvider(): array
    {
        return [
            ['context' => new LayoutContext(), 'expected' => 'Request'],
            ['context' => new LayoutContext(['widget_container' => 'sample']), 'expected' => 'Widget: sample'],
            ['context' => new LayoutContext(['action' => 'sample_action']), 'expected' => 'Action: sample_action'],
            [
                'context' => new LayoutContext(
                    ['route_name' => 'sample_route']
                ),
                'expected' => 'Route: sample_route',
            ],
        ];
    }
}
