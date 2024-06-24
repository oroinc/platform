<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\UsageStats;

use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProviderRegistry;
use PHPUnit\Framework\TestCase;

class UsageStatsProviderRegistryTest extends TestCase
{
    /**
     * @dataProvider getProvidersDataProvider
     */
    public function testGetProviders(array $providers, array $expectedResults): void
    {
        $registry = new UsageStatsProviderRegistry($providers);

        self::assertEquals($expectedResults, $registry->getProviders());
    }

    public function getProvidersDataProvider(): array
    {
        $provider1 = $this->getProviderMock(true);
        $provider2 = $this->getProviderMock(false);
        $provider3 = $this->getProviderMock(true);

        return [
            [
                [],
                [],
            ],
            [
                [$provider1, $provider2, $provider3],
                [0 => $provider1, 2 => $provider3],
            ],
        ];
    }

    private function getProviderMock(bool $isApplicable): UsageStatsProviderInterface
    {
        $provider = $this->createMock(UsageStatsProviderInterface::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->willReturn($isApplicable);

        return $provider;
    }
}
