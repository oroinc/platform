<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\UsageStats;

use Oro\Bundle\PlatformBundle\Model\UsageStat;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProvider;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProviderRegistry;
use PHPUnit\Framework\TestCase;

class UsageStatsProviderTest extends TestCase
{
    /**
     * @dataProvider getUsageStatsDataProvider
     */
    public function testGetUsageStats(array $providers, array $expectedResults): void
    {
        $registry = new UsageStatsProviderRegistry($providers);
        $usageStatsProvider = new UsageStatsProvider($registry);

        self::assertEquals($expectedResults, $usageStatsProvider->getUsageStats());
    }

    public function getUsageStatsDataProvider(): array
    {
        $title1 = 'title1';
        $title2 = 'title2';
        $title3 = 'title3';
        $tooltip3 = 'tooltip3';
        $value3 = 'value3';

        $provider1 = $this->getProviderMock(true, $title1);
        $provider2 = $this->getProviderMock(false, $title2);
        $provider3 = $this->getProviderMock(true, $title3, $tooltip3, $value3);

        return [
            [
                [],
                [],
            ],
            [
                [$provider1, $provider2, $provider3],
                [
                    UsageStat::create($title1),
                    UsageStat::create($title3, $tooltip3, $value3),
                ],
            ],
        ];
    }

    private function getProviderMock(
        bool $isApplicable,
        string $title,
        ?string $tooltip = null,
        ?string $value = null
    ): UsageStatsProviderInterface {
        $provider = $this->createMock(UsageStatsProviderInterface::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->willReturn($isApplicable);
        $provider->expects(self::any())
            ->method('getTitle')
            ->willReturn($title);
        $provider->expects(self::any())
            ->method('getTooltip')
            ->willReturn($tooltip);
        $provider->expects(self::any())
            ->method('getValue')
            ->willReturn($value);

        return $provider;
    }
}
