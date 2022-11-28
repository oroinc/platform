<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Oro\Bundle\LayoutBundle\DataCollector\DataCollectorLayoutNameProvider;
use Oro\Bundle\LayoutBundle\DataCollector\DataCollectorLayoutNameProviderInterface;
use Oro\Component\Layout\LayoutContext;

class DataCollectorLayoutNameProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetNameByContextWhenNoProviders(): void
    {
        $provider = new DataCollectorLayoutNameProvider([]);

        self::assertEquals('', $provider->getNameByContext(new LayoutContext()));
    }

    public function testGetNameByContext(): void
    {
        $innerProvider1 = $this->createMock(DataCollectorLayoutNameProviderInterface::class);
        $innerProvider2 = $this->createMock(DataCollectorLayoutNameProviderInterface::class);
        $provider = new DataCollectorLayoutNameProvider([$innerProvider1, $innerProvider2]);
        $context = new LayoutContext();

        $innerProvider1
            ->expects(self::once())
            ->method('getNameByContext')
            ->with($context)
            ->willReturn('');

        $name = 'sample_name';
        $innerProvider2
            ->expects(self::once())
            ->method('getNameByContext')
            ->with($context)
            ->willReturn($name);

        self::assertEquals($name, $provider->getNameByContext($context));
    }
}
